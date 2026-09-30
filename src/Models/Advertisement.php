<?php

namespace EffyAds\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $table = 'effyads_advertisements';

    protected $fillable = [
        'platform_id',
        'title',
        'description',
        'link',
        'media_url',
        'start_date',
        'end_date',
        'targeting',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'targeting' => 'array',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function matchesZone(string $zone): bool
    {
        $zones = $this->targeting['zones'] ?? null;

        if (empty($zones)) {
            return true;
        }

        $include = $zones['include'] ?? [];
        $exclude = $zones['exclude'] ?? [];

        if (! empty($exclude) && in_array($zone, $exclude, true)) {
            return false;
        }

        if (! empty($include)) {
            return in_array($zone, $include, true);
        }

        return true;
    }

    public function isCurrentlyActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }
}
