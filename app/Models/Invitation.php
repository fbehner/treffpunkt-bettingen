<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Einladung, ein Konto anzulegen. Der Link enthält ein zufälliges Token;
 * gespeichert wird nur dessen Hash.
 */
class Invitation extends Model
{
    /** Gültigkeit eines Einladungslinks in Tagen. */
    public const VALID_DAYS = 14;

    protected $fillable = ['email', 'name', 'token_hash', 'invited_by', 'user_id', 'expires_at', 'accepted_at', 'sent_at'];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Neues Token erzeugen; gibt das Klartext-Token zurück (nur für den Link). */
    public function issueToken(): string
    {
        $token = Str::random(48);
        $this->forceFill([
            'token_hash' => self::hash($token),
            'expires_at' => now()->addDays(self::VALID_DAYS),
        ])->save();

        return $token;
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    public static function findByToken(string $token): ?self
    {
        return static::query()->where('token_hash', self::hash($token))->first();
    }

    public function isOpen(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public function status(): string
    {
        return match (true) {
            $this->accepted_at !== null => 'angenommen',
            $this->expires_at->isPast() => 'abgelaufen',
            default => 'offen',
        };
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }
}
