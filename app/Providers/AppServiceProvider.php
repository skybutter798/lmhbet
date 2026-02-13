<?php
// /home/lmh/app/app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\BetRecord;
use App\Observers\BetRecordObserver;
use App\Models\DepositRequest;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WinPayClient::class, function () {
            return WinPayClient::make();
        });
    }

    public function boot(): void
    {
        View::composer('partials.header', function ($view) {
            if (!auth()->check()) return;

            $user = auth()->user();

            $wallets = $user->wallets()
                ->whereIn('type', ['main', 'chips'])
                ->get()
                ->keyBy('type');

            $pendingBonus = (float) DepositRequest::query()
                ->where('user_id', $user->id)
                ->whereNotNull('promotion_id')
                ->where('status', DepositRequest::STATUS_APPROVED)
                ->where('bonus_status', 'in_progress')
                ->sum('bonus_amount');

            $view->with('walletBalances', [
                'main'  => (float) ($wallets->get('main')?->balance ?? 0),
                'chips' => (float) ($wallets->get('chips')?->balance ?? 0),
                'bonus' => $pendingBonus,
            ]);
        });

        BetRecord::observe(BetRecordObserver::class);
    }
}