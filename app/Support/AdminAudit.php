<?php

namespace App\Support;

use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\Auth;

class AdminAudit
{
    public static function log(
        string $action,
        ?int $targetUserId = null,
        ?string $targetType = null,
        ?int $targetId = null,
        array $meta = []
    ): void {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return;

        AdminAuditLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_user_id' => $targetUserId,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'ip' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
            'meta' => $meta,
        ]);
    }
}
