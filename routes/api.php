<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Api\V1\StatusController;
use App\Http\Controllers\Api\V1\DBOXTestController;
use App\Http\Controllers\Api\V1\DBOXWebhookController;
use App\Http\Controllers\Api\V1\DBOXCmoController;
use App\Http\Controllers\Api\V1\DBOXTrfController;
use App\Http\Controllers\Api\V1\DBOXSeamlessWalletController;
use App\Http\Controllers\Api\V1\VPayNotifyController;
use App\Http\Controllers\Api\V1\DBOXSeamlessWalletExtController;
use App\Http\Controllers\Api\V1\WinPayNotifyController;

Route::prefix('v1')->group(function () {
    Route::get('/status', [StatusController::class, 'status']);
    Route::get('/dbox/ping', [DBOXTestController::class, 'ping']);

    Route::post('/dbox/webhook', [DBOXWebhookController::class, 'handle'])
        ->middleware('dbox.sig');

    Route::get('/dbox/provider-list', [DBOXCmoController::class, 'providerList']);
    Route::get('/dbox/game-list', [DBOXCmoController::class, 'gameList']);
    Route::post('/dbox/launch-game', [DBOXCmoController::class, 'launchGame']);

    // keep if you still use TRF APIs elsewhere
    Route::post('/dbox/trf/deposit', [DBOXTrfController::class, 'deposit']);
    Route::post('/dbox/trf/withdraw', [DBOXTrfController::class, 'withdraw']);
    
    Route::post('/winpay/notify', [WinPayNotifyController::class, 'handle']);
    
    Route::post('/payments/winpay/notify', [\App\Http\Controllers\Api\V1\WinPayNotifyController::class, 'handle'])
        ->middleware('log.incoming')
        ->name('winpay.notify');
});

/*
|--------------------------------------------------------------------------
| DBOX Seamless Wallet CALLBACK BASE = /api/response
| DBOX should call:
|   POST /api/response/get-balance
|   POST /api/response/bet
|   POST /api/response/settle
|--------------------------------------------------------------------------
*/
Route::prefix('response')->group(function () {

    // Base ping (useful for your own curl)
    Route::match(['GET','POST'], '/', function (Request $request) {
        Log::info('DBOX /api/response BASE HIT', [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);
        return response()->json(['ok' => true]);
    });

    // If DBOX does GET reachability checks, answer safely (no balance exposed)
    Route::get('/get-balance', function (Request $request) {
        Log::warning('DBOX GET get-balance (should be POST)', [
            'ip' => $request->ip(),
            'headers' => [
                'mkey' => $request->header('mkey'),
                'ts'   => $request->header('ts'),
                'hash' => $request->header('hash') ? substr((string)$request->header('hash'), 0, 12).'...' : null,
                'ua'   => $request->userAgent(),
            ],
        ]);

        return response()->json([
            'code' => -1,
            'msg'  => 'Use POST',
            'data' => null,
        ], 200);
    })->middleware('dbox.inlog');

    // REAL Seamless endpoints
    Route::post('/get-balance', [DBOXSeamlessWalletController::class, 'getBalance'])
        ->middleware(['dbox.inlog', 'dbox.sig']);

    Route::post('/bet', [DBOXSeamlessWalletController::class, 'bet'])
        ->middleware(['dbox.inlog', 'dbox.sig']);

    Route::post('/settle', [DBOXSeamlessWalletController::class, 'settle'])
        ->middleware(['dbox.inlog', 'dbox.sig']);

    // Catch-all (log whatever DBOX sends that you didn't implement yet)
    Route::any('/{any}', function (Request $request, string $any) {
        $raw = (string) $request->getContent();
    
        Log::warning('DBOX /api/response UNHANDLED CALLBACK', [
            'ip'     => $request->ip(),
            'method' => $request->method(),
            'path'   => '/' . ltrim($request->path(), '/'),
            'any'    => $any,
    
            'headers' => [
                'mkey'        => $request->header('mkey'),
                'ts'          => $request->header('ts'),
                'hash_prefix' => $request->header('hash')
                    ? substr((string) $request->header('hash'), 0, 12) . '...'
                    : null,
                'content_type' => $request->header('content-type'),
                'user_agent'   => $request->userAgent(),
            ],
    
            'body_first_3000' => mb_substr($raw, 0, 3000),
        ]);
    
        // ✅ Return DBOX JSON shape, 200 OK, so certification won't fail on 404
        // Use 0 if you want to "pretend supported"; use -1 if you want to indicate not supported.
        return response()->json([
            'code' => -1,
            'msg'  => 'Unsupported callback endpoint',
            'data' => null,
        ], 200);
    
    })->where('any', '.*')->middleware('dbox.inlog');

});
