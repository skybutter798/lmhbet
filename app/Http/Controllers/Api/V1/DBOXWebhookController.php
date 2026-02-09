<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DBOXWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::info('DBOX webhook received', [
            'headers' => [
                'mkey' => $request->header('mkey'),
                'ts'   => $request->header('ts'),
                'hash' => $request->header('hash'),
            ],
            'payload' => $payload,
        ]);

        // TODO: route by event type, update wallet, etc.
        // Example:
        // if (($payload['event'] ?? null) === 'deposit_success') { ... }

        return response()->json([
            'success' => true,
        ]);
    }
}
