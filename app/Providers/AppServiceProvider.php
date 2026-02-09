<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('partials.header', function ($view) {
            if (!auth()->check()) {
                return;
            }

            $wallets = auth()->user()
                ->wallets()
                ->whereIn('type', ['main', 'chips', 'bonus'])
                ->get()
                ->keyBy('type');

            $view->with('walletBalances', [
                'main'  => $wallets->get('main')?->balance ?? 0,
                'chips' => $wallets->get('chips')?->balance ?? 0,
                'bonus' => $wallets->get('bonus')?->balance ?? 0,
            ]);
        });
    }
}
