<?php
// /home/lmh/app/routes/web.php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\GamesController;
use App\Http\Controllers\GameLaunchController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\History\TransactionHistoryController;
use App\Http\Controllers\History\GameHistoryController;
use App\Http\Controllers\DBOXImgUploadController;
use App\Http\Controllers\Admin\DBOXGameImgController;
use App\Http\Controllers\Admin\DBOXProviderImgController;
use App\Http\Controllers\WalletBalanceController;
use App\Http\Controllers\WithdrawalBankAccountController;
use App\Http\Controllers\Support\SupportTicketController;

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminKycController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminBetRecordController;
use App\Http\Controllers\Admin\AdminDBOXProviderController;
use App\Http\Controllers\Admin\AdminDBOXGameController;
use App\Http\Controllers\Admin\AdminDBOXGameSortController;
use App\Http\Controllers\Admin\AdminDBOXImgUploadController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\WalletBonusController;
use App\Http\Controllers\Admin\AdminSupportController;


Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/register', [RegisterController::class, 'show'])->name('register.show');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::get('/login', function () {
    return redirect()->route('home', ['auth' => 'login']);
})->name('login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');

    Route::post('/wallet/internal-transfer', [WalletController::class, 'transferInternal'])
        ->name('wallet.transfer.internal');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

    Route::post('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.email.update');
    Route::post('/profile/phone', [ProfileController::class, 'updatePhone'])->name('profile.phone.update');

    Route::get('/referral', [ReferralController::class, 'index'])->name('referral.index');

    Route::get('/transfer', [TransferController::class, 'index'])->name('transfer.index');
    Route::post('/transfer', [TransferController::class, 'store'])->name('transfer.store');

    Route::post('/games/launch', [GameLaunchController::class, 'launch'])->name('games.launch');

    Route::get('/play/{game}', [GamesController::class, 'play'])->name('games.play');

    Route::post('/kyc/submit', [ProfileController::class, 'submitKyc'])->name('kyc.submit');
    Route::post('/kyc/cancel', [ProfileController::class, 'cancelKyc'])->name('kyc.cancel');

    Route::get('/withdraw', [WithdrawalController::class, 'index'])->name('withdraw.index');
    Route::post('/withdraw', [WithdrawalController::class, 'store'])->name('withdraw.store');

    Route::get('/deposit', [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');

    Route::get('/transaction-history', [TransactionHistoryController::class, 'index'])
        ->name('history.transactions');

    Route::get('/game-history', [GameHistoryController::class, 'index'])
        ->name('history.games');

    Route::get('/wallet/chips/balance', [WalletBalanceController::class, 'chips'])
        ->name('wallet.chips.balance');

    Route::get('/wallet/main/balance', [WalletBalanceController::class, 'main'])
        ->name('wallet.main.balance');
        
    Route::get('/wallet/balances', [WalletBalanceController::class, 'all'])
        ->name('wallet.balances');
        
    Route::get('/wallet/bonus/records', [WalletBonusController::class, 'records'])
        ->name('wallet.bonus.records');
        
    // Bank Details + Security
    Route::get('/bank-details', [ProfileController::class, 'bankDetails'])
        ->name('profile.bank');

    Route::get('/security/password', [ProfileController::class, 'showPasswordForm'])
        ->name('profile.password.form');
    Route::post('/security/password', [ProfileController::class, 'updatePassword'])
        ->name('profile.password.update');

    Route::get('/security/pin', [ProfileController::class, 'showPinForm'])
        ->name('profile.pin.form');
    Route::post('/security/pin', [ProfileController::class, 'updatePin'])
        ->name('profile.pin.update');
        
    // Bank Details (Withdrawal Accounts)
    Route::get('/bank-details', [WithdrawalBankAccountController::class, 'index'])
        ->name('profile.bank');
    
    Route::post('/bank-details', [WithdrawalBankAccountController::class, 'store'])
        ->name('profile.bank.store');
    
    Route::post('/bank-details/{bankAccount}/default', [WithdrawalBankAccountController::class, 'setDefault'])
        ->name('profile.bank.default');
    
    Route::delete('/bank-details/{bankAccount}', [WithdrawalBankAccountController::class, 'destroy'])
        ->name('profile.bank.destroy');
        
    Route::get('/message', [SupportTicketController::class, 'index'])->name('support.index');
    Route::post('/message', [SupportTicketController::class, 'store'])->name('support.store');
    Route::get('/message/{ticket}', [SupportTicketController::class, 'show'])->name('support.show');
    Route::post('/message/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('support.reply');
});

Route::get('/games', [GamesController::class, 'index'])->name('games.index');

Route::middleware(['auth'])->prefix('admin/dbox')->name('admin.dbox.')->group(function () {

    Route::post('games/{game}/images', [DBOXGameImgController::class, 'store'])
        ->name('games.images.store');

    Route::delete('games/images/{img}', [DBOXGameImgController::class, 'destroy'])
        ->name('games.images.destroy');

    Route::post('games/images/{img}/primary', [DBOXGameImgController::class, 'setPrimary'])
        ->name('games.images.primary');

    Route::post('providers/{provider}/images', [DBOXProviderImgController::class, 'store'])
        ->name('providers.images.store');

    Route::delete('providers/images/{img}', [DBOXProviderImgController::class, 'destroy'])
        ->name('providers.images.destroy');

    Route::post('providers/images/{img}/primary', [DBOXProviderImgController::class, 'setPrimary'])
        ->name('providers.images.primary');
});

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.store');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/search', [AdminUserController::class, 'search'])->name('users.search');

        Route::get('/users/{user}', [AdminUserController::class, 'edit'])->name('users.edit');
        Route::post('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');

        Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggleActive');
        Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');
        Route::post('/users/{user}/lock', [AdminUserController::class, 'lock'])->name('users.lock');
        Route::post('/users/{user}/unlock', [AdminUserController::class, 'unlock'])->name('users.unlock');

        Route::get('/users/{user}/modal', [AdminUserController::class, 'modal'])->name('users.modal');

        Route::get('/users/export/csv', [AdminUserController::class, 'exportCsv'])->name('users.export.csv');

        Route::post('/users/{user}/wallet-adjust', [AdminUserController::class, 'walletAdjust'])->name('users.walletAdjust');

        Route::get('/users/{user}/tx', [AdminUserController::class, 'txPage'])->name('users.txPage');
        Route::get('/users/{user}/bets', [AdminUserController::class, 'betsPage'])->name('users.betsPage');

        Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
        Route::get('/kyc/search', [AdminKycController::class, 'search'])->name('kyc.search');
        Route::post('/kyc/{submission}/approve', [AdminKycController::class, 'approve'])->name('kyc.approve');
        Route::post('/kyc/{submission}/reject', [AdminKycController::class, 'reject'])->name('kyc.reject');

        Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit.index');

        Route::get('/bet-records', [AdminBetRecordController::class, 'index'])->name('betrecords.index');
        Route::get('/bet-records/search', [AdminBetRecordController::class, 'search'])->name('betrecords.search');
        Route::get('/bet-records/export/csv', [AdminBetRecordController::class, 'exportCsv'])->name('betrecords.export.csv');
        Route::get('/bet-records/{betRecord}/modal', [AdminBetRecordController::class, 'modal'])->name('betrecords.modal');

        Route::prefix('dbox')->name('dbox.')->group(function () {

            Route::get('/providers', [AdminDBOXProviderController::class, 'index'])->name('providers.index');
            Route::get('/providers/search', [AdminDBOXProviderController::class, 'search'])->name('providers.search');
            Route::get('/providers/export/csv', [AdminDBOXProviderController::class, 'exportCsv'])->name('providers.export.csv');
            Route::get('/providers/{provider}/modal', [AdminDBOXProviderController::class, 'modal'])->name('providers.modal');

            Route::post('/providers/{provider}', [AdminDBOXProviderController::class, 'update'])->name('providers.update');
            Route::post('/providers/{provider}/toggle-active', [AdminDBOXProviderController::class, 'toggleActive'])->name('providers.toggleActive');

            Route::post('/providers/sort/bulk', [AdminDBOXProviderController::class, 'bulkSort'])->name('providers.sort.bulk');

            Route::get('/games', [AdminDBOXGameController::class, 'index'])->name('games.index');
            Route::get('/games/search', [AdminDBOXGameController::class, 'search'])->name('games.search');
            Route::get('/games/export/csv', [AdminDBOXGameController::class, 'exportCsv'])->name('games.export.csv');
            Route::get('/games/{game}/modal', [AdminDBOXGameController::class, 'modal'])->name('games.modal');

            Route::post('/games/{game}', [AdminDBOXGameController::class, 'update'])->name('games.update');
            Route::post('/games/{game}/toggle-active', [AdminDBOXGameController::class, 'toggleActive'])->name('games.toggleActive');

            Route::post('/games/sort/bulk', [AdminDBOXGameController::class, 'bulkSort'])->name('games.sort.bulk');

            Route::post('/games/{game}/currencies', [AdminDBOXGameController::class, 'updateCurrencies'])
                ->name('games.currencies.update');

            Route::get('/images/upload', [AdminDBOXImgUploadController::class, 'form'])->name('images.upload.form');
            Route::get('/images/upload/search', [AdminDBOXImgUploadController::class, 'search'])->name('images.upload.search');
            Route::get('/images/upload/preview', [AdminDBOXImgUploadController::class, 'preview'])->name('images.upload.preview');
            Route::post('/images/upload', [AdminDBOXImgUploadController::class, 'store'])->name('images.upload.store');
        });

        Route::get('/dbox/games/sort', [AdminDBOXGameSortController::class, 'index'])->name('dbox.games.sort');
        Route::get('/dbox/games/sort/list', [AdminDBOXGameSortController::class, 'list'])->name('dbox.games.sort.list');
        Route::get('/dbox/games/sort/autocomplete', [AdminDBOXGameSortController::class, 'autocomplete'])->name('dbox.games.sort.autocomplete');
        Route::post('/dbox/games/sort/move', [AdminDBOXGameSortController::class, 'move'])->name('dbox.games.sort.move');
        Route::post('/dbox/games/sort/renumber', [AdminDBOXGameSortController::class, 'renumber'])->name('dbox.games.sort.renumber');
        Route::post('/dbox/games/sort/reorder', [AdminDBOXGameSortController::class, 'reorder']) ->name('dbox.games.sort.reorder');

        Route::get('/wallet-transactions', [\App\Http\Controllers\Admin\AdminWalletTransactionController::class, 'index'])
            ->name('wallettx.index');

        Route::get('/wallet-transactions/search', [\App\Http\Controllers\Admin\AdminWalletTransactionController::class, 'search'])
            ->name('wallettx.search');

        Route::get('/wallet-transactions/export/csv', [\App\Http\Controllers\Admin\AdminWalletTransactionController::class, 'exportCsv'])
            ->name('wallettx.export.csv');

        Route::get('/wallet-transactions/{tx}/modal', [\App\Http\Controllers\Admin\AdminWalletTransactionController::class, 'modal'])
            ->name('wallettx.modal');

        Route::post('/wallet-transactions/{tx}/update', [\App\Http\Controllers\Admin\AdminWalletTransactionController::class, 'updateTx'])
            ->name('wallettx.update');

        Route::post('/wallet-transactions/adjust', [\App\Http\Controllers\Admin\AdminWalletTransactionController::class, 'adminAdjust'])
            ->name('wallettx.adjust');

        Route::post('/wallet-transactions/{tx}/reverse', [\App\Http\Controllers\Admin\AdminWalletTransactionController::class, 'reverse'])
            ->name('wallettx.reverse');

        Route::get('/profile/modal', [AdminProfileController::class, 'modal'])->name('profile.modal');

        Route::post('/profile/password', [AdminProfileController::class, 'updatePassword'])
            ->name('profile.password.update');

        Route::post('/profile/pin', [AdminProfileController::class, 'updatePin'])
            ->name('profile.pin.update');

        Route::post('/profile/2fa', [AdminProfileController::class, 'updateTwoFaSecret'])
            ->name('profile.2fa.update');
        
        Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
        Route::get('/support/{ticket}', [AdminSupportController::class, 'show'])->name('support.show');
        Route::post('/support/{ticket}/reply', [AdminSupportController::class, 'reply'])->name('support.reply');
        Route::post('/support/{ticket}/close', [AdminSupportController::class, 'close'])->name('support.close');
        Route::post('/support/{ticket}/reopen', [AdminSupportController::class, 'reopen'])->name('support.reopen');
    });
});
