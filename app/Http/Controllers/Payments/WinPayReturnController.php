<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WinPayReturnController extends Controller
{
    public function handle(Request $request)
    {
        // 这里通常只能提示用户“已提交，等待确认”
        // 你可以把 bill_number 设计成 return_url query 参数，然后在这里 query 一次
        return redirect()
            ->route('deposit.index')
            ->with('status', 'Payment submitted. Please wait for confirmation.');
    }
}
