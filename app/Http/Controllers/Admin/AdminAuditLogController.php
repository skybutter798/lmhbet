<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;

class AdminAuditLogController extends Controller
{
    public function index()
    {
        $logs = AdminAuditLog::query()
            ->with('admin')
            ->orderByDesc('id')
            ->paginate(50);

        return view('admins.audit.index', compact('logs'));
    }
}
