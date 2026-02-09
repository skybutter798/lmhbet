<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\WalletTransaction;
use App\Services\DBOX\DBOXClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DBOXTrfController extends Controller
{
    /**
     * POST /api/v1/dbox/trf/deposit
     * body: prvCode, curCode, merPlyId(optional), amt, refId(optional)
     */
    public function deposit(Request $request)
    {
        $validated = $request->validate([
            'prvCode'  => ['required', 'string', 'size:3'],
            'curCode'  => ['required', 'string', 'size:3'],
            'amt'      => ['required', 'numeric', 'min:0.01'],
            'merPlyId' => ['nullable', 'string', 'max:200'],
            'refId'    => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user(); // if this is not an authenticated route, adjust
        $merPlyId = $validated['merPlyId'] ?? $this->makeMerPlyId($user);
        $refId = $validated['refId'] ?? $this->makeRefId('DEP', $user?->id);

        $body = [
            'prvCode'  => strtoupper($validated['prvCode']),
            'merPlyId' => $merPlyId,
            'curCode'  => strtoupper($validated['curCode']),
            'amt'      => (float) $validated['amt'],
            'refId'    => $refId,
        ];

        // ✅ Optional: create a local PENDING transaction for idempotency & auditing
        // If you don't have wallet_id/wallet_type handy here, store minimal fields.
        // Recommended: wrap balance changes in DB transaction and lock wallet row.
        $localTxn = null;

        try {
            $dbox = DBOXClient::makeFromConfig();

            // Idempotency: if we already created a local txn for this ref, return it (or re-check DBOX if you have a status API)
            $existing = WalletTransaction::where('reference', $refId)->first();
            if ($existing) {
                return response()->json([
                    'code' => 0,
                    'msg'  => 'Already processed (local ref exists)',
                    'data' => [
                        'refId' => $refId,
                        'local_txn_id' => $existing->id,
                        'external_id' => $existing->external_id,
                        'status' => $existing->status,
                    ],
                ], 200);
            }

            // Create local pending record (no wallet movements here unless you implement it)
            $localTxn = WalletTransaction::create([
                'user_id'        => $user?->id,
                'wallet_id'      => null,
                'wallet_type'    => 'provider',
                'direction'      => WalletTransaction::DIR_DEBIT, // merchant wallet decreases
                'amount'         => $body['amt'],
                'balance_before' => null,
                'balance_after'  => null,
                'status'         => WalletTransaction::STATUS_PENDING,
                'reference'      => $refId,
                'external_id'    => null,
                'tx_hash'        => null,
                'title'          => 'DBOX Deposit',
                'description'    => 'Transfer merchant -> provider',
                'created_by'     => $user?->id,
                'approved_by'    => null,
                'ip'             => $request->ip(),
                'user_agent'     => (string) $request->userAgent(),
                'meta'           => [
                    'prvCode' => $body['prvCode'],
                    'curCode' => $body['curCode'],
                    'merPlyId' => $body['merPlyId'],
                ],
                'occurred_at'    => now(),
            ]);

            $res = $dbox->post('/mer/eai/trf/deposit', $body);
            $json = $res->json();

            if (!$res->ok() || !is_array($json)) {
                $localTxn?->update([
                    'status' => WalletTransaction::STATUS_FAILED,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'http_status' => $res->status(),
                        'body_first_300' => mb_substr((string) $res->body(), 0, 300),
                    ]),
                ]);

                return response()->json([
                    'code' => -1,
                    'msg'  => 'DBOX HTTP error',
                    'http_status' => $res->status(),
                    'body_first_300' => mb_substr((string) $res->body(), 0, 300),
                ], 502);
            }

            // Mark local txn by DBOX result
            $dboxCode = (int) ($json['code'] ?? -1);
            $txnId = data_get($json, 'data.txnId');

            if ($dboxCode === 0) {
                $localTxn?->update([
                    'status' => WalletTransaction::STATUS_COMPLETED, // or keep PENDING and wait for webhook if DBOX is async
                    'external_id' => $txnId,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'dbox' => $json,
                    ]),
                ]);
            } else {
                $localTxn?->update([
                    'status' => WalletTransaction::STATUS_FAILED,
                    'external_id' => $txnId,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'dbox' => $json,
                    ]),
                ]);
            }

            return response()->json($json, $res->status());
        } catch (\Throwable $e) {
            Log::error('DBOX deposit failed', [
                'err' => $e->getMessage(),
                'body' => $body ?? null,
            ]);

            if ($localTxn) {
                $localTxn->update([
                    'status' => WalletTransaction::STATUS_FAILED,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'exception' => $e->getMessage(),
                    ]),
                ]);
            }

            return response()->json([
                'code' => -1,
                'msg'  => 'DBOX deposit failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/dbox/trf/withdraw
     */
    public function withdraw(Request $request)
    {
        $validated = $request->validate([
            'prvCode'  => ['required', 'string', 'size:3'],
            'curCode'  => ['required', 'string', 'size:3'],
            'amt'      => ['required', 'numeric', 'min:0.01'],
            'merPlyId' => ['nullable', 'string', 'max:200'],
            'refId'    => ['nullable', 'string', 'max:200'],
        ]);

        $user = $request->user();
        $merPlyId = $validated['merPlyId'] ?? $this->makeMerPlyId($user);
        $refId = $validated['refId'] ?? $this->makeRefId('WDR', $user?->id);

        $body = [
            'prvCode'  => strtoupper($validated['prvCode']),
            'merPlyId' => $merPlyId,
            'curCode'  => strtoupper($validated['curCode']),
            'amt'      => (float) $validated['amt'],
            'refId'    => $refId,
        ];

        $localTxn = null;

        try {
            $dbox = DBOXClient::makeFromConfig();

            $existing = WalletTransaction::where('reference', $refId)->first();
            if ($existing) {
                return response()->json([
                    'code' => 0,
                    'msg'  => 'Already processed (local ref exists)',
                    'data' => [
                        'refId' => $refId,
                        'local_txn_id' => $existing->id,
                        'external_id' => $existing->external_id,
                        'status' => $existing->status,
                    ],
                ], 200);
            }

            $localTxn = WalletTransaction::create([
                'user_id'        => $user?->id,
                'wallet_id'      => null,
                'wallet_type'    => 'provider',
                'direction'      => WalletTransaction::DIR_CREDIT, // merchant wallet increases
                'amount'         => $body['amt'],
                'balance_before' => null,
                'balance_after'  => null,
                'status'         => WalletTransaction::STATUS_PENDING,
                'reference'      => $refId,
                'external_id'    => null,
                'tx_hash'        => null,
                'title'          => 'DBOX Withdraw',
                'description'    => 'Transfer provider -> merchant',
                'created_by'     => $user?->id,
                'approved_by'    => null,
                'ip'             => $request->ip(),
                'user_agent'     => (string) $request->userAgent(),
                'meta'           => [
                    'prvCode' => $body['prvCode'],
                    'curCode' => $body['curCode'],
                    'merPlyId' => $body['merPlyId'],
                ],
                'occurred_at'    => now(),
            ]);

            $res = $dbox->post('/mer/eai/trf/withdraw', $body);
            $json = $res->json();

            if (!$res->ok() || !is_array($json)) {
                $localTxn?->update([
                    'status' => WalletTransaction::STATUS_FAILED,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'http_status' => $res->status(),
                        'body_first_300' => mb_substr((string) $res->body(), 0, 300),
                    ]),
                ]);

                return response()->json([
                    'code' => -1,
                    'msg'  => 'DBOX HTTP error',
                    'http_status' => $res->status(),
                    'body_first_300' => mb_substr((string) $res->body(), 0, 300),
                ], 502);
            }

            $dboxCode = (int) ($json['code'] ?? -1);
            $txnId = data_get($json, 'data.txnId');

            if ($dboxCode === 0) {
                $localTxn?->update([
                    'status' => WalletTransaction::STATUS_COMPLETED,
                    'external_id' => $txnId,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'dbox' => $json,
                    ]),
                ]);
            } else {
                $localTxn?->update([
                    'status' => WalletTransaction::STATUS_FAILED,
                    'external_id' => $txnId,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'dbox' => $json,
                    ]),
                ]);
            }

            return response()->json($json, $res->status());
        } catch (\Throwable $e) {
            Log::error('DBOX withdraw failed', [
                'err' => $e->getMessage(),
                'body' => $body ?? null,
            ]);

            if ($localTxn) {
                $localTxn->update([
                    'status' => WalletTransaction::STATUS_FAILED,
                    'meta' => array_merge($localTxn->meta ?? [], [
                        'exception' => $e->getMessage(),
                    ]),
                ]);
            }

            return response()->json([
                'code' => -1,
                'msg'  => 'DBOX withdraw failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function makeMerPlyId($user): string
    {
        $id = $user?->id ?? 0;
        $rawUsername = $user?->username ?? 'user';
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', (string) $rawUsername);
        $username = strtolower($username);
        $merPlyId = 'LMH_' . $id . '_' . $username;

        return substr($merPlyId, 0, 200);
    }

    private function makeRefId(string $prefix, ?int $userId): string
    {
        // keep <= 200 chars
        $uid = $userId ?? 0;
        return substr($prefix . '_' . $uid . '_' . now()->format('YmdHis') . '_' . Str::random(10), 0, 200);
    }
}
