<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jahresbeitrag einer Mitgliedschaft.
 */
class MembershipFee extends Model
{
    protected $fillable = ['membership_id', 'year', 'amount', 'donation', 'paid_at', 'notes'];

    protected $casts = [
        'year' => 'integer',
        'amount' => 'integer',
        'donation' => 'integer',
        'paid_at' => 'date',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('paid_at');
    }

    public function total(): int
    {
        return $this->amount + (int) $this->donation;
    }

    /**
     * Beiträge für ein Jahr anlegen – für alle beitragspflichtigen Mitgliedschaften,
     * die noch keinen Eintrag haben. Gibt die Anzahl neuer Einträge zurück.
     */
    public static function generateFor(int $year): int
    {
        $created = 0;

        Membership::query()->liableIn($year)
            ->whereDoesntHave('fees', fn (Builder $q) => $q->where('year', $year))
            ->with(['application', 'fees'])
            ->each(function (Membership $membership) use ($year, &$created) {
                // Gönnerbeitrag aus dem Gesuch beim ersten Beitrag übernehmen.
                $donation = $membership->fees->isEmpty() ? $membership->application?->donation : null;

                $membership->fees()->create([
                    'year' => $year,
                    'amount' => $membership->fee,
                    'donation' => $donation,
                ]);
                $created++;
            });

        return $created;
    }
}
