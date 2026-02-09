<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DBOX\DBOXClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DBOXCmoController extends Controller
{
    /**
     * GET {API Server Domain}/mer/eai/cmo/provider-list
     * Headers: mkey, ts
     */
    public function providerList()
    {
        try {
            $dbox = \App\Services\DBOX\DBOXClient::makeFromConfig();
    
            $res = $dbox->get('/mer/eai/cmo/provider-list');

            if (!$res->ok()) {
                return response()->json([
                    'code' => -1,
                    'msg' => 'DBOX HTTP error',
                    'http_status' => $res->status(),
                    'content_type' => $res->header('Content-Type'),
                    'body_first_300' => mb_substr($res->body(), 0, 300),
                ], 502);
            }
            
            $json = $res->json();
            
            if (!is_array($json)) {
                return response()->json([
                    'code' => -1,
                    'msg' => 'DBOX returned non-JSON response',
                    'http_status' => $res->status(),
                    'content_type' => $res->header('Content-Type'),
                    'body_first_300' => mb_substr($res->body(), 0, 300),
                ], 502);
            }
            
            return response()->json($json, 200);

        } catch (\Throwable $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
                'dbox_base_url' => config('services.dbox.base_url'),
                'dbox_mkey_set' => config('services.dbox.mkey') ? true : false,
            ], 500);
        }
    }


    /**
     * GET {API Server Domain}/mer/eai/cmo/game-list?prvCode=...&curCode=...&lnchGme=...
     * Headers: mkey, ts
     */
    public function gameList(Request $request)
    {
        $validated = $request->validate([
            'prvCode'  => ['required', 'string', 'max:50'],
            'curCode'  => ['required', 'string', 'max:50'],
            'lnchGme'  => ['nullable'], // accept "true"/"false"/1/0
        ]);

        $query = [
            'prvCode' => $validated['prvCode'],
            'curCode' => $validated['curCode'],
        ];

        if ($request->has('lnchGme')) {
            // normalize to "true"/"false" because some APIs care
            $bool = filter_var($request->query('lnchGme'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($bool !== null) {
                $query['lnchGme'] = $bool ? 'true' : 'false';
            }
        }

        try {
            $dbox = DBOXClient::makeFromConfig();

            $res = $dbox->get('/mer/eai/cmo/game-list', $query);

            if (!$res->ok()) {
                return response()->json([
                    'code' => -1,
                    'msg' => 'DBOX HTTP error',
                    'http_status' => $res->status(),
                    'content_type' => $res->header('Content-Type'),
                    'body_first_300' => mb_substr($res->body(), 0, 300),
                ], 502);
            }
            
            $json = $res->json();
            
            if (!is_array($json)) {
                return response()->json([
                    'code' => -1,
                    'msg' => 'DBOX returned non-JSON response',
                    'http_status' => $res->status(),
                    'content_type' => $res->header('Content-Type'),
                    'body_first_300' => mb_substr($res->body(), 0, 300),
                ], 502);
            }
            
            return response()->json($json, 200);

        } catch (\Throwable $e) {
            Log::error('DBOX gameList error', ['err' => $e->getMessage(), 'query' => $query]);
            return response()->json([
                'code' => -1,
                'msg' => 'DBOX gameList failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST {API Server Domain}/mer/eai/cmo/launch-game
     * Headers: mkey, ts, hash
     */
    public function launchGame(Request $request)
    {
        $validated = $request->validate([
            'merPlyId'   => ['required', 'string', 'max:200'],
            'curCode'    => ['required', 'string', 'size:3'],
            'gmeCodeVal' => ['required', 'string', 'max:50'],
            'langCode'   => ['required', 'string', 'max:50'],

            'prvCode'    => ['nullable', 'string', 'size:3'],
            'rdrtUrl'    => ['nullable', 'string'],
            'pltfmCode'  => ['nullable', 'string', 'max:50'],
        ]);

        // Build body with only allowed keys (keeps hashing deterministic)
        $body = array_filter([
            'merPlyId'   => $validated['merPlyId'],
            'curCode'    => $validated['curCode'],
            'prvCode'    => $validated['prvCode'] ?? null,
            'gmeCodeVal' => $validated['gmeCodeVal'],
            'langCode'   => $validated['langCode'],
            'rdrtUrl'    => $validated['rdrtUrl'] ?? null,
            'pltfmCode'  => $validated['pltfmCode'] ?? null,
        ], fn ($v) => $v !== null);

        try {
            $dbox = DBOXClient::makeFromConfig();

            $res = $dbox->post('/mer/eai/cmo/launch-game', $body);

            return response()->json($res->json(), $res->status());
        } catch (\Throwable $e) {
            Log::error('DBOX launchGame error', ['err' => $e->getMessage(), 'body' => $body]);
            return response()->json([
                'code' => -1,
                'msg' => 'DBOX launchGame failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
