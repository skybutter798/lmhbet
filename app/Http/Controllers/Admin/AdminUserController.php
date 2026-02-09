<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\VipTier;
use App\Support\AdminAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = $this->applyFilters($this->baseQuery(), request())
            ->orderByDesc('users.id')
            ->paginate(20)
            ->withQueryString();

        $users->withPath(route('admin.users.search'));

        return view('admins.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $user->load(['vipTier', 'kycProfile', 'referrer']);

        $wallets = $user->wallets()
            ->select('id','type','balance','status','locked_until')
            ->orderBy('type')
            ->get();

        // different page names so tx/bets pagination won't fight
        $transactions = $user->walletTransactions()
            ->latest('id')
            ->paginate(25, ['*'], 'tx_page')
            ->withQueryString();

        $betRecords = DB::table('bet_records')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'bets_page')
            ->withQueryString();

        $betStats = DB::table('bet_records')
            ->where('user_id', $user->id)
            ->selectRaw('COUNT(*) as total_bets, COALESCE(SUM(stake_amount),0) as total_stake, COALESCE(SUM(payout_amount),0) as total_payout, MAX(bet_at) as last_bet_at')
            ->first();

        $vipTiers = VipTier::query()->orderBy('level')->get(['id','name','level']);

        return view('admins.users.edit', compact(
            'user','wallets','transactions','betRecords','betStats','vipTiers'
        ));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username'      => ['required','string','max:50'],
            'name'          => ['nullable','string','max:120'],
            'email'         => ['nullable','email','max:191'],
            'phone_country' => ['nullable','string','max:6'],
            'phone'         => ['nullable','string','max:32'],
            'country'       => ['nullable','string','max:2'],
            'currency'      => ['nullable','string','max:3'],
            'vip_tier_id'   => ['nullable','integer'],
            'is_active'     => ['nullable'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        $user->update($data);

        AdminAudit::log('user.update', $user->id, 'users', $user->id, [
            'fields' => array_keys($data),
        ]);

        return redirect()
            ->route('admin.users.edit', $user->id)
            ->with('ok', 'User updated.');
    }

    public function search(Request $request)
    {
        $users = $this->applyFilters($this->baseQuery(), $request)
            ->orderByDesc('users.id')
            ->paginate(20)
            ->withQueryString();

        $users->withPath(route('admin.users.search'));

        return response()->json([
            'html' => view('admins.users.partials.table', compact('users'))->render(),
            'pagination' => $users->links('vendor.pagination.admin')->render(),
            'total' => $users->total(),
        ]);
    }

    public function modal(User $user)
    {
        try {
            $user->load(['vipTier', 'kycProfile', 'referrer']);

            $wallets = $user->wallets()->orderBy('type')->get(['id','type','balance','status','locked_until']);

            $tx = $user->walletTransactions()
                ->latest('id')
                ->paginate(25)
                ->withPath(route('admin.users.txPage', $user->id));

            $bets = DB::table('bet_records')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->paginate(25)
                ->withPath(route('admin.users.betsPage', $user->id));

            $betStats = DB::table('bet_records')
                ->where('user_id', $user->id)
                ->selectRaw('COUNT(*) as total_bets, COALESCE(SUM(stake_amount),0) as total_stake, COALESCE(SUM(payout_amount),0) as total_payout, MAX(bet_at) as last_bet_at')
                ->first();

            return response()->json([
                'html' => view('admins.users.partials.modal', compact('user','wallets','tx','bets','betStats'))->render(),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok' => false,
                'msg' => $e->getMessage(),
            ], 500);
        }
    }

    public function txPage(User $user, Request $request)
    {
        $tx = $user->walletTransactions()
            ->latest('id')
            ->paginate(25)
            ->withPath(route('admin.users.txPage', $user->id))
            ->withQueryString();

        return response()->json([
            'html' => view('admins.users.partials.tx_table', compact('tx','user'))->render(),
            'pagination' => $tx->links('vendor.pagination.admin')->render(),
        ]);
    }

    public function betsPage(User $user, Request $request)
    {
        $bets = DB::table('bet_records')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(25)
            ->withPath(route('admin.users.betsPage', $user->id))
            ->withQueryString();

        return response()->json([
            'html' => view('admins.users.partials.bets_table', compact('bets','user'))->render(),
            'pagination' => $bets->links('vendor.pagination.admin')->render(),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = $this->applyFilters($this->baseQuery(), $request)->orderByDesc('users.id');

        $filename = 'users_export_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'id','username','email','phone','country','currency','is_active','banned_at','locked_until',
                'vip','kyc_status','main_balance','chips_balance','bonus_balance','created_at'
            ]);

            $query->chunk(1000, function ($rows) use ($out) {
                foreach ($rows as $u) {
                    fputcsv($out, [
                        $u->id,
                        $u->username,
                        $u->email,
                        trim(($u->phone_country ?? '').' '.($u->phone ?? '')),
                        $u->country,
                        $u->currency,
                        $u->is_active ? 1 : 0,
                        $u->banned_at,
                        $u->locked_until,
                        $u->vip_name,
                        $u->kyc_status,
                        $u->main_balance,
                        $u->chips_balance,
                        $u->bonus_balance,
                        $u->created_at,
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function walletAdjust(Request $request, User $user)
    {
        $data = $request->validate([
            'wallet_type' => ['required','string','in:main,chips,bonus,promote,extra'],
            'direction'   => ['required','string','in:credit,debit'],
            'amount'      => ['required','numeric','gt:0'],
            'title'       => ['nullable','string','max:120'],
            'description' => ['nullable','string','max:500'],
        ]);

        $scale = 18;
        $amount = $this->decFormat((string)$data['amount'], $scale);

        return DB::transaction(function () use ($user, $data, $amount, $scale, $request) {

            $wallet = Wallet::where('user_id', $user->id)
                ->where('type', $data['wallet_type'])
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                $wallet = Wallet::create([
                    'user_id' => $user->id,
                    'type' => $data['wallet_type'],
                    'balance' => $this->decFormat('0', $scale),
                    'status' => Wallet::STATUS_ACTIVE,
                    'locked_until' => null,
                ]);

                $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            }

            $before = $this->decFormat((string)$wallet->balance, $scale);

            if ($data['direction'] === WalletTransaction::DIR_DEBIT) {
                if ($this->decCmp($before, $amount, $scale) < 0) {
                    return response()->json(['ok' => false, 'msg' => 'Insufficient balance.'], 422);
                }
                $after = $this->decSub($before, $amount, $scale);
            } else {
                $after = $this->decAdd($before, $amount, $scale);
            }

            $wallet->balance = $after;
            $wallet->save();

            $adminId = auth('admin')->id();

            $tx = WalletTransaction::create([
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'wallet_type' => $wallet->type,
                'direction' => $data['direction'],
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'status' => WalletTransaction::STATUS_COMPLETED,
                'reference' => 'admin_adjust:' . now()->format('YmdHis') . ':' . $user->id,
                'title' => $data['title'] ?? 'Admin wallet adjust',
                'description' => $data['description'] ?? null,
                'created_by' => $adminId,
                'approved_by' => $adminId,
                'ip' => $request->ip(),
                'user_agent' => substr((string)$request->userAgent(), 0, 255),
                'meta' => ['admin_adjust' => true],
                'occurred_at' => now(),
            ]);

            AdminAudit::log(
                'wallet.adjust',
                $user->id,
                'wallets',
                $wallet->id,
                [
                    'wallet_type' => $wallet->type,
                    'direction' => $data['direction'],
                    'amount' => $amount,
                    'before' => $before,
                    'after' => $after,
                    'tx_id' => $tx->id,
                ]
            );

            return response()->json([
                'ok' => true,
                'wallet_balance' => (string)$wallet->balance,
            ]);
        });
    }

    public function toggleActive(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        AdminAudit::log('user.toggle_active', $user->id, 'users', $user->id, [
            'is_active' => (bool)$user->is_active,
        ]);

        return response()->json(['ok' => true, 'is_active' => (bool)$user->is_active]);
    }

    public function ban(Request $request, User $user)
    {
        $data = $request->validate(['ban_reason' => ['nullable','string','max:255']]);

        $user->banned_at = now();
        $user->ban_reason = $data['ban_reason'] ?? 'Banned by admin';
        $user->is_active = false;
        $user->save();

        AdminAudit::log('user.ban', $user->id, 'users', $user->id, [
            'ban_reason' => $user->ban_reason,
        ]);

        return response()->json(['ok' => true]);
    }

    public function unban(User $user)
    {
        $user->banned_at = null;
        $user->ban_reason = null;
        $user->save();

        AdminAudit::log('user.unban', $user->id, 'users', $user->id);

        return response()->json(['ok' => true]);
    }

    public function lock(Request $request, User $user)
    {
        $minutes = (int) $request->input('minutes', 30);
        if ($minutes < 1) $minutes = 30;
        if ($minutes > 10080) $minutes = 10080;

        $user->locked_until = now()->addMinutes($minutes);
        $user->save();

        AdminAudit::log('user.lock', $user->id, 'users', $user->id, [
            'minutes' => $minutes,
            'locked_until' => (string)$user->locked_until,
        ]);

        return response()->json(['ok' => true]);
    }

    public function unlock(User $user)
    {
        $user->locked_until = null;
        $user->failed_login_attempts = 0;
        $user->save();

        AdminAudit::log('user.unlock', $user->id, 'users', $user->id);

        return response()->json(['ok' => true]);
    }

    private function baseQuery()
    {
        $walletAgg = DB::table('wallets')
            ->selectRaw("
                user_id,
                SUM(CASE WHEN type='main' THEN balance ELSE 0 END)  as main_balance,
                SUM(CASE WHEN type='chips' THEN balance ELSE 0 END) as chips_balance,
                SUM(CASE WHEN type='bonus' THEN balance ELSE 0 END) as bonus_balance
            ")
            ->groupBy('user_id');

        return User::query()
            ->leftJoinSub($walletAgg, 'wa', fn($j) => $j->on('wa.user_id', '=', 'users.id'))
            ->leftJoin('vip_tiers', 'vip_tiers.id', '=', 'users.vip_tier_id')
            ->leftJoin('kyc_profiles', 'kyc_profiles.user_id', '=', 'users.id')
            ->select([
                'users.*',
                DB::raw('COALESCE(wa.main_balance, 0) as main_balance'),
                DB::raw('COALESCE(wa.chips_balance, 0) as chips_balance'),
                DB::raw('COALESCE(wa.bonus_balance, 0) as bonus_balance'),
                DB::raw('vip_tiers.name as vip_name'),
                DB::raw('kyc_profiles.status as kyc_status'),
            ]);
    }

    private function applyFilters($query, Request $request)
    {
        $q        = trim((string)$request->query('q', ''));
        $status   = $request->query('status', 'all');
        $banned   = $request->query('banned', 'all');
        $locked   = $request->query('locked', 'all');
        $country  = trim((string)$request->query('country', ''));
        $currency = trim((string)$request->query('currency', ''));
        $vip      = $request->query('vip', 'all');
        $kyc      = $request->query('kyc', 'all');
        $from     = trim((string)$request->query('from', ''));
        $to       = trim((string)$request->query('to', ''));

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('users.username', 'like', "%{$q}%")
                    ->orWhere('users.name', 'like', "%{$q}%")
                    ->orWhere('users.email', 'like', "%{$q}%")
                    ->orWhere('users.phone', 'like', "%{$q}%")
                    ->orWhere('users.referral_code', 'like', "%{$q}%");
            });
        }

        if ($status === 'active') $query->where('users.is_active', true);
        if ($status === 'inactive') $query->where('users.is_active', false);

        if ($banned === 'banned') $query->whereNotNull('users.banned_at');
        if ($banned === 'not_banned') $query->whereNull('users.banned_at');

        if ($locked === 'locked') $query->where('users.locked_until', '>', now());
        if ($locked === 'not_locked') $query->where(function ($w) {
            $w->whereNull('users.locked_until')->orWhere('users.locked_until', '<=', now());
        });

        if ($country !== '') $query->where('users.country', $country);
        if ($currency !== '') $query->where('users.currency', $currency);

        if ($vip !== 'all') $query->where('users.vip_tier_id', (int)$vip);
        if ($kyc !== 'all') $query->where('kyc_profiles.status', (int)$kyc);

        if ($from !== '') $query->whereDate('users.created_at', '>=', $from);
        if ($to !== '') $query->whereDate('users.created_at', '<=', $to);

        return $query;
    }

    // ==========================
    // Decimal helpers (no bcmath)
    // ==========================

    private function decFormat(string $v, int $scale = 18): string
    {
        $v = trim($v);
        $neg = false;

        if ($v !== '' && $v[0] === '-') {
            $neg = true;
            $v = substr($v, 1);
        }

        $v = preg_replace('/[^0-9.]/', '', $v) ?? '0';
        if ($v === '' || $v === '.') $v = '0';

        $parts = explode('.', $v, 2);
        $int = ltrim($parts[0] === '' ? '0' : $parts[0], '0');
        if ($int === '') $int = '0';

        $dec = $parts[1] ?? '';
        $dec = preg_replace('/\D/', '', $dec) ?? '';
        $dec = substr($dec, 0, $scale);
        $dec = str_pad($dec, $scale, '0');

        $out = $int . '.' . $dec;

        if ($neg && preg_match('/^0\.0+$/', $out) !== 1) {
            return '-' . $out;
        }
        return $out;
    }

    private function decCmp(string $a, string $b, int $scale = 18): int
    {
        $a = $this->decFormat($a, $scale);
        $b = $this->decFormat($b, $scale);

        $aNeg = ($a[0] === '-');
        $bNeg = ($b[0] === '-');

        if ($aNeg && !$bNeg) return -1;
        if (!$aNeg && $bNeg) return 1;

        $ai = $this->decToIntStr($a, $scale);
        $bi = $this->decToIntStr($b, $scale);

        $cmp = $this->bigCmp(ltrim($ai, '-'), ltrim($bi, '-'));
        return $aNeg ? -$cmp : $cmp;
    }

    private function decAdd(string $a, string $b, int $scale = 18): string
    {
        $a = $this->decFormat($a, $scale);
        $b = $this->decFormat($b, $scale);

        $ai = $this->decToIntStr($a, $scale);
        $bi = $this->decToIntStr($b, $scale);

        if ($ai[0] === '-' || $bi[0] === '-') {
            $sum = (float)$a + (float)$b;
            return $this->decFormat((string)$sum, $scale);
        }

        $si = $this->bigAdd($ai, $bi);
        return $this->intStrToDec($si, $scale);
    }

    private function decSub(string $a, string $b, int $scale = 18): string
    {
        $a = $this->decFormat($a, $scale);
        $b = $this->decFormat($b, $scale);

        $ai = $this->decToIntStr($a, $scale);
        $bi = $this->decToIntStr($b, $scale);

        if ($ai[0] === '-' || $bi[0] === '-') {
            $diff = (float)$a - (float)$b;
            return $this->decFormat((string)$diff, $scale);
        }

        $di = $this->bigSub($ai, $bi);
        return $this->intStrToDec($di, $scale);
    }

    private function decToIntStr(string $v, int $scale): string
    {
        $v = $this->decFormat($v, $scale);
        $neg = false;

        if ($v[0] === '-') {
            $neg = true;
            $v = substr($v, 1);
        }

        [$i, $d] = array_pad(explode('.', $v, 2), 2, '');
        $d = str_pad(substr($d, 0, $scale), $scale, '0');

        $digits = ltrim($i . $d, '0');
        if ($digits === '') $digits = '0';

        return $neg && $digits !== '0' ? ('-' . $digits) : $digits;
    }

    private function intStrToDec(string $int, int $scale): string
    {
        $int = ltrim($int, '0');
        if ($int === '') $int = '0';

        if ($scale <= 0) return $int;

        if (strlen($int) <= $scale) {
            $int = str_pad($int, $scale + 1, '0', STR_PAD_LEFT);
        }

        $pos = strlen($int) - $scale;
        $i = substr($int, 0, $pos);
        $d = substr($int, $pos);

        return $i . '.' . $d;
    }

    private function bigCmp(string $a, string $b): int
    {
        $a = ltrim($a, '0'); if ($a === '') $a = '0';
        $b = ltrim($b, '0'); if ($b === '') $b = '0';

        if (strlen($a) < strlen($b)) return -1;
        if (strlen($a) > strlen($b)) return 1;
        if ($a === $b) return 0;

        return ($a < $b) ? -1 : 1;
    }

    private function bigAdd(string $a, string $b): string
    {
        $a = ltrim($a, '0'); if ($a === '') $a = '0';
        $b = ltrim($b, '0'); if ($b === '') $b = '0';

        $i = strlen($a) - 1;
        $j = strlen($b) - 1;
        $carry = 0;
        $out = '';

        while ($i >= 0 || $j >= 0 || $carry) {
            $da = ($i >= 0) ? (int)$a[$i] : 0;
            $db = ($j >= 0) ? (int)$b[$j] : 0;
            $sum = $da + $db + $carry;

            $out .= (string)($sum % 10);
            $carry = intdiv($sum, 10);

            $i--; $j--;
        }

        return strrev($out);
    }

    private function bigSub(string $a, string $b): string
    {
        $a = ltrim($a, '0'); if ($a === '') $a = '0';
        $b = ltrim($b, '0'); if ($b === '') $b = '0';

        $i = strlen($a) - 1;
        $j = strlen($b) - 1;
        $borrow = 0;
        $out = '';

        while ($i >= 0) {
            $da = (int)$a[$i] - $borrow;
            $db = ($j >= 0) ? (int)$b[$j] : 0;

            if ($da < $db) {
                $da += 10;
                $borrow = 1;
            } else {
                $borrow = 0;
            }

            $out .= (string)($da - $db);
            $i--; $j--;
        }

        $res = ltrim(strrev($out), '0');
        return $res === '' ? '0' : $res;
    }
}
