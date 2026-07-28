<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoomSetting extends Model
{
    protected $fillable = [
        'account_id', 'client_id', 'client_secret',
        'auto_recording', 'audio_options', 'host_video',
        'participant_video', 'join_before_host', 'waiting_room',
        'mute_upon_entry', 'class_join_approval',
    ];

    /**
     * Get the singleton settings row (creates a default if none exists).
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'auto_recording'       => 'none',
            'audio_options'        => 'both',
            'host_video'           => 'disable',
            'participant_video'    => 'disable',
            'join_before_host'     => 'disable',
            'waiting_room'         => 'enable',
            'mute_upon_entry'      => 'enable',
            'class_join_approval'  => 'automatically',
        ]);
    }

    /**
     * Check if Zoom OAuth credentials are configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->account_id) && !empty($this->client_id) && !empty($this->client_secret);
    }
}
