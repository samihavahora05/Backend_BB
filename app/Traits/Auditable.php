<?php

namespace App\Traits;

use App\Models\AdminLog;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    public static function bootAuditable()
    {
        static::created(function ($model) {
            self::logAction('Created', $model);
        });

        static::updated(function ($model) {
            self::logAction('Updated', $model);
        });

        static::deleted(function ($model) {
            self::logAction('Deleted', $model);
        });
    }

    protected static function logAction($action, $model)
    {
        $user = Auth::user();
        if (!$user) return; // Only log authenticated actions, ideally admin actions

        AdminLog::create([
            'user_id' => $user->id,
            'action' => "{$action} " . class_basename($model),
            'details' => json_encode([
                'table' => $model->getTable(),
                'id' => $model->id,
                'changes' => $action === 'Updated' ? $model->getChanges() : $model->toArray(),
            ]),
            'ip_address' => request()->ip() ?? '127.0.0.1',
        ]);
    }
}
