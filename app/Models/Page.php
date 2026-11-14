<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eine frei bearbeitbare Unterseite (z. B. Über uns, Impressum, Datenschutz).
 */
class Page extends Model
{
    protected $fillable = ['title', 'slug', 'body', 'is_published', 'show_in_nav', 'show_in_footer', 'sort'];

    protected $casts = [
        'is_published' => 'boolean',
        'show_in_nav' => 'boolean',
        'show_in_footer' => 'boolean',
        'sort' => 'integer',
    ];

    /** Slugs, die von festen Routen belegt sind. */
    public const RESERVED_SLUGS = ['admin', 'login', 'logout', 'build', 'storage', 'up', 'livewire', 'filament'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort')->orderBy('title');
    }
}
