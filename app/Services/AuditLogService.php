<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Record an action in audit logs.
     */
    public static function log(?int $userId, string $action, array $payload = []): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $userId,
                'action' => $action,
                'ip_address' => Request::ip() ?? '127.0.0.1',
                'user_agent' => Request::userAgent() ?? 'System',
                'payload' => json_encode($payload),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Failed to record audit log for action [{$action}]: " . $e->getMessage());
        }
    }
}