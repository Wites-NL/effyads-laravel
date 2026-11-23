<?php

namespace EffyAds\Laravel\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Advertisement extends Model
{
    protected $table = 'effyads_advertisements';

    /**
     * Standard IAB display sizes (width, height), the same list as the
     * platform's convex/adFormats.ts.
     */
    public const FORMATS = [
        'leaderboard' => [728, 90],
        'billboard' => [970, 250],
        'medium-rectangle' => [300, 250],
        'large-rectangle' => [336, 280],
        'small-rectangle' => [180, 150],
        'square' => [250, 250],
        'wide-skyscraper' => [160, 600],
        'half-page' => [300, 600],
        'mobile-banner' => [320, 50],
        'large-mobile-banner' => [320, 100],
    ];

    protected $fillable = [
        'site',
        'platform_id',
        'title',
        'description',
        'link',
        'format',
        'media_url',
        'media_width',
        'media_height',
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
        'media_width' => 'integer',
        'media_height' => 'integer',
    ];

    /**
     * Single-site installs store site = '' (see the add_site migration).
     */
    public static function siteColumnValue(?string $site): string
    {
        return $site ?? '';
    }

    public function scopeForSite(Builder $query, ?string $site): Builder
    {
        return $query->where($this->qualifyColumn('site'), static::siteColumnValue($site));
    }

    /**
     * Ads that may be shown right now: "active", or "scheduled" once the
     * start date has passed, and in both cases not past the end date.
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function (Builder $q) use ($now) {
                $q->where($this->qualifyColumn('status'), 'active')
                    ->orWhere(function (Builder $q) use ($now) {
                        $q->where($this->qualifyColumn('status'), 'scheduled')
                            ->whereNotNull($this->qualifyColumn('start_date'));
                    });
            })
            ->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('start_date'))->orWhere($this->qualifyColumn('start_date'), '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('end_date'))->orWhere($this->qualifyColumn('end_date'), '>=', $now));
    }

    /**
     * Relative weight for random rotation (targeting.weight, default 1).
     */
    public function rotationWeight(): float
    {
        $weight = $this->targeting['weight'] ?? 1;

        return is_numeric($weight) ? max(0.0, (float) $weight) : 1.0;
    }

    /**
     * A slot that asks for a format only takes ads of exactly that format; a
     * slot without one takes any ad. Ads without a format ("any size")
     * therefore only appear in slots without a format.
     */
    public function matchesFormat(?string $format): bool
    {
        return $format === null || $format === '' || $this->format === $format;
    }

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
        $isScheduled = $this->status === 'scheduled' && $this->start_date !== null;

        if ($this->status !== 'active' && ! $isScheduled) {
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
