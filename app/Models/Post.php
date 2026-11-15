<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Ein Beitrag unter „Aktuelles“: Neuigkeit oder Termin.
 */
class Post extends Model
{
    public const KINDS = [
        'news' => 'Neuigkeit',
        'event' => 'Termin',
    ];

    protected $fillable = [
        'title', 'slug', 'kind', 'excerpt', 'body', 'image', 'image_alt',
        'event_starts_at', 'event_location', 'published_at', 'is_published',
    ];

    protected $casts = [
        'event_starts_at' => 'datetime',
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    /** Kommende Termine, nächster zuerst. */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->visible()
            ->where('kind', 'event')
            ->where('event_starts_at', '>=', now()->startOfDay())
            ->orderBy('event_starts_at');
    }

    public function isEvent(): bool
    {
        return $this->kind === 'event' && $this->event_starts_at !== null;
    }

    /** Datum für Listen: Termin-Datum oder Veröffentlichung. */
    public function displayDate(): ?Carbon
    {
        return $this->isEvent() ? $this->event_starts_at : ($this->published_at ?? $this->created_at);
    }
}
