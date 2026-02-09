<?php

// /home/lmh/app/app/Http/Controllers/WalletBonusController.php

namespace App\Http\Controllers;

use App\Models\DepositRequest;
use Illuminate\Http\Request;

class WalletBonusController extends Controller
{
    public function records(Request $request)
    {
        $user = $request->user();

        $rows = DepositRequest::query()
            ->with('promotion:id,title,turnover_multiplier')
            ->where('user_id', $user->id)
            ->where('status', DepositRequest::STATUS_APPROVED)
            ->whereNotNull('promotion_id')
            ->whereIn('bonus_status', ['in_progress', 'done'])
            ->orderByDesc('paid_at')
            ->limit(50)
            ->get()
            ->map(function ($r) use ($user) {
                $req = (float) ($r->turnover_required ?? 0);
                $prog = (float) ($r->turnover_progress ?? 0);
                $pct = $req > 0 ? min(100, max(0, ($prog / $req) * 100)) : 0;

                return [
                    'id' => $r->id,
                    'status' => $r->bonus_status,
                    'reference' => $r->reference,
                    'title' => $r->promotion?->title ?? 'Promotion',
                    'currency' => $user->currency ?? 'MYR',
                    'required' => $req,
                    'progress' => $prog,
                    'pct' => $pct,
                    'bonus_amount' => (float) ($r->bonus_amount ?? 0),
                    'done_at' => optional($r->bonus_done_at)->toDateTimeString(),
                ];
            });

        return response()->json([
            'ok' => true,
            'records' => $rows,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}