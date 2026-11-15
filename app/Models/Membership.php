<?php

namespace App\Models;

use App\Support\MembershipType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Eine Mitgliedschaft: Rechnungseinheit mit einer oder mehreren Personen
 * (Einzel, Paar, Familie, Kind, Jugend) bzw. ein Verein / eine Institution.
 */
class Membership extends Model
{
    public const EXIT_REASONS = [
        'austritt' => 'Austritt',
        'ausschluss' => 'Ausschluss',
        'verstorben' => 'Verstorben',
        'wechsel' => 'Wechsel in andere Mitgliedschaft',
    ];

    protected $fillable = [
        'number', 'type', 'fee', 'organisation', 'street', 'zip_city', 'email', 'phone',
        'joined_at', 'left_at', 'exit_reason', 'notes',
    ];

    protected $casts = [
        'joined_at' => 'date',
        'left_at' => 'date',
        'fee' => 'integer',
        'number' => 'integer',
    ];

    public function people(): HasMany
    {
        return $this->hasMany(MembershipPerson::class)->orderBy('sort')->orderBy('id');
    }

    public function fees(): HasMany
    {
        return $this->hasMany(MembershipFee::class)->orderByDesc('year');
    }

    public function application(): HasOne
    {
        return $this->hasOne(MembershipApplication::class);
    }

    public function typeInfo(): MembershipType
    {
        return MembershipType::from($this->type);
    }

    /** Aktiv = nicht ausgetreten (Austrittsdatum fehlt oder liegt in der Zukunft). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('left_at')->orWhere('left_at', '>', now()->toDateString()));
    }

    /** Im Kalenderjahr beitragspflichtig: vor Jahresende eingetreten, nicht vor Jahresbeginn ausgetreten. */
    public function scopeLiableIn(Builder $query, int $year): Builder
    {
        return $query->whereDate('joined_at', '<=', "{$year}-12-31")
            ->where(fn (Builder $q) => $q->whereNull('left_at')->orWhereDate('left_at', '>=', "{$year}-01-01"));
    }

    public function isActive(): bool
    {
        return $this->left_at === null || $this->left_at->isFuture();
    }

    public static function nextNumber(): int
    {
        return ((int) static::query()->max('number')) + 1;
    }

    public function contact(): ?MembershipPerson
    {
        return $this->people->first();
    }

    public function displayName(): string
    {
        $names = $this->people
            ->whereIn('relation', ['haupt', 'partner', 'kontakt'])
            ->map(fn (MembershipPerson $p) => $p->fullName())
            ->join(' und ');

        if ($this->organisation) {
            return $this->organisation.($names ? ' ('.$names.')' : '');
        }

        return $names ?: '–';
    }

    /** Stimmberechtigte Personen an der GV (ab 16; Erwachsene ohne Geburtsdatum zählen mit). */
    public function votingCount(): int
    {
        if ($this->typeInfo()->isOrganisation()) {
            return 1;
        }

        return $this->people->filter(function (MembershipPerson $p) {
            $age = $p->age();

            return $age === null ? $p->relation !== 'kind' : $age >= 16;
        })->count();
    }

    /** Hinweis, wenn die Mitgliedschaftsart wegen des Alters wechseln sollte. */
    public function transitionHint(): ?string
    {
        $age = $this->people->firstWhere('relation', 'haupt')?->age();

        return match (true) {
            $age === null => null,
            $this->type === 'kind' && $age >= 16 => 'Jugendliche/r (16 Jahre erreicht)',
            $this->type === 'jugend' && $age >= 18 => 'Einzelperson (18 Jahre erreicht)',
            default => null,
        };
    }

    public function joinedLabel(): string
    {
        return $this->joined_at instanceof Carbon ? $this->joined_at->format('d.m.Y') : '';
    }
}
