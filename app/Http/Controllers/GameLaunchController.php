<?php

namespace App\Http\Controllers;

use App\Models\DBOXGame;
use App\Services\DBOX\DBOXClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GameLaunchController extends Controller
{
    public function launch(Request $request)
    {
        $v = $request->validate([
            'game_id' => ['required', 'integer'],
        ]);

        $user = $request->user();

        $rawUsername = $user->username ?? 'user';
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $rawUsername);
        $username = strtolower($username);

        $merPlyId = 'LMH_' . $user->id . '_' . $username;
        $merPlyId = substr($merPlyId, 0, 200);

        $currency = $user->currency ?? 'MYR';
        $curCode = strtoupper(substr($currency, 0, 3));

        $game = DBOXGame::with('provider')
            ->where('is_active', true)
            ->where('supports_launch', true)
            ->findOrFail((int)$v['game_id']);

        $okCurrency = $game->currencies()
            ->where('currency', $currency)
            ->where('is_active', true)
            ->exists();

        if (!$okCurrency) {
            return response()->json(['ok' => false, 'message' => 'Currency not supported.'], 422);
        }

        $prvCode = strtoupper(substr((string)($game->provider?->code ?? ''), 0, 3));
        if (!$prvCode) {
            return response()->json(['ok' => false, 'message' => 'Provider code missing.'], 422);
        }

        Log::info('DBOX launch request', [
            'user_id' => $user->id,
            'game_id' => (int)$game->id,
            'prvCode' => $prvCode,
            'curCode' => $curCode,
            'merPlyId' => $merPlyId,
        ]);

        $dbox = DBOXClient::makeFromConfig();

        $launchBody = [
            'merPlyId'   => $merPlyId,
            'curCode'    => $curCode,
            'prvCode'    => $prvCode,
            'gmeCodeVal' => (string) $game->code,
            'langCode'   => 'en_US',
            'rdrtUrl'    => route('games.index'),
            'pltfmCode'  => 'DSK',
        ];

        $launchRes = $dbox->post('/mer/eai/cmo/launch-game', $launchBody);
        $launchJson = $launchRes->json();

        $url = data_get($launchJson, 'data.url');
        $code = (int)($launchJson['code'] ?? -1);

        Log::info('DBOX launch response', [
            'http' => $launchRes->status(),
            'code' => $code,
            'msg'  => $launchJson['msg'] ?? null,
        ]);

        if (!$launchRes->ok() || $code !== 0 || !$url) {
            return response()->json([
                'ok' => false,
                'message' => $launchJson['msg'] ?? 'Launch failed',
                'dbox_code' => $launchJson['code'] ?? null,
            ], 422);
        }

        return response()->json(['ok' => true, 'url' => $url]);
    }
}
