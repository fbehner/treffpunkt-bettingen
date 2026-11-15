<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eine Seite der Website. Inhalt aus Kopf (Titel, Einleitung), optionalem
 * Freitext (body) und Bausteinen (blocks) – siehe resources/views/blocks.
 */
class Page extends Model
{
    /** Adresse der Startseite. */
    public const HOME_SLUG = 'startseite';

    protected $fillable = [
        'title', 'slug', 'kicker', 'headline', 'headline_accent', 'lead',
        'body', 'blocks', 'meta_description',
        'is_published', 'show_in_nav', 'show_in_footer', 'sort',
    ];

    protected $casts = [
        'blocks' => 'array',
        'is_published' => 'boolean',
        'show_in_nav' => 'boolean',
        'show_in_footer' => 'boolean',
        'sort' => 'integer',
    ];

    /** Slugs, die von festen Routen belegt sind. */
    public const RESERVED_SLUGS = [
        'admin', 'login', 'logout', 'build', 'storage', 'up', 'livewire', 'filament',
        'aktuelles', 'mitglied-werden', 'fonts', 'css', 'js',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort')->orderBy('title');
    }

    public function isHome(): bool
    {
        return $this->slug === self::HOME_SLUG;
    }

    public function url(): string
    {
        return $this->isHome() ? route('home') : route('page.show', $this->slug);
    }

    /** Bausteine mit Sprungmarke – für die Leiste „Auf dieser Seite“. */
    public function anchors(): array
    {
        return collect($this->blocks ?? [])
            ->map(fn (array $block) => $block['data'] ?? [])
            ->filter(fn (array $data) => filled($data['anchor'] ?? null) && filled($data['anchor_label'] ?? $data['heading'] ?? null))
            ->map(fn (array $data) => ['id' => $data['anchor'], 'label' => $data['anchor_label'] ?? $data['heading']])
            ->values()
            ->all();
    }
}
