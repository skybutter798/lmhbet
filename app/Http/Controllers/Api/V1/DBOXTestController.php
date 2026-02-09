<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\DBOX\DBOXClient;

class DBOXTestController extends Controller
{
    public function ping()
    {
        $dbox = DBOXClient::makeFromConfig();

        // Replace with a real DBOX endpoint path once you know it.
        // Example:
        // $res = $dbox->get('/api/v1/some-status');

        return response()->json([
            'ok' => true,
            'message' => 'DBOX client ready. Replace endpoint path with real DBOX API method.',
        ]);
    }
}
