<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Ein Angebot – eine Kachel im Raster auf der Startseite.
 */
class Offer extends Model
{
    protected $fillable = ['title', 'description', 'icon', 'color', 'sort', 'is_visible'];

    protected $casts = [
        'is_visible' => 'boolean',
        'sort' => 'integer',
    ];

    /** Kachelfarben aus dem Plakat. */
    public const COLORS = [
        'rost' => 'Rostrot',
        'senf' => 'Senfgelb',
        'gruen' => 'Dunkelgrün',
        'dunkel' => 'Dunkelbraun',
        'beige' => 'Hellbeige',
    ];

    /** Verfügbare Linien-Icons (siehe resources/views/components/symbol.blade.php). */
    public const ICONS = [
        'book' => 'Buch',
        'mic' => 'Mikrofon',
        'puppet' => 'Puppe',
        'flask' => 'Kolben (Wissenschaft)',
        'chat' => 'Sprechblasen',
        'film' => 'Filmstreifen',
        'egg' => 'Osterei',
        'music' => 'Noten',
        'dance' => 'Tänzer',
        'cup' => 'Kaffeetasse',
        'calendar' => 'Kalender',
        'users' => 'Menschen',
        'heart' => 'Herz',
        'star' => 'Stern',
    ];

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true)->orderBy('sort')->orderBy('id');
    }
}
