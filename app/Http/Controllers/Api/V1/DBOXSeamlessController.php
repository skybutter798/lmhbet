<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DBOXSeamlessController extends Controller
{
    public function getBalance(Request $request)
    {
        $validated = $request->validate([
            'merPlyId' => ['required', 'string', 'max:200'],
            'curCode'  => ['required', 'string', 'size:3'],
            'prvCode'  => ['required', 'string', 'size:3'],
        ]);

        // TODO: map merPlyId -> user, return wallet balance (single wallet)
        return response()->json([
            'code' => 0,
            'msg'  => 'Success',
            'data' => ['blc' => 0.0],
        ]);
    }

    public function bet(Request $request)
    {
        // TODO: implement idempotent debit based on txns[*].unqTxnId
        return response()->json([
            'code' => 0,
            'msg'  => 'Success',
            'data' => ['blc' => 0.0],
        ]);
    }

    public function settle(Request $request)
    {
        // TODO: implement idempotent credit/debit based on txns[*].unqTxnId
        return response()->json([
            'code' => 0,
            'msg'  => 'Success',
            'data' => ['blc' => 0.0],
        ]);
    }
}
