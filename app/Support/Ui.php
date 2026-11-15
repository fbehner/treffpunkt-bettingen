<?php

namespace App\Support;

/**
 * Kleine Helfer für die Vorlagen (Farben, Bild-Adressen).
 */
class Ui
{
    /** Tailwind-Klassen je Farbton: Fläche, Text, Akzent (kleine Überschrift/Icon). */
    public static function tone(?string $tone): array
    {
        return match ($tone) {
            'senf' => ['bg' => 'bg-senf', 'text' => 'text-dunkel', 'accent' => 'text-dunkel'],
            'gruen' => ['bg' => 'bg-gruen', 'text' => 'text-white', 'accent' => 'text-white'],
            'dunkel' => ['bg' => 'bg-dunkel', 'text' => 'text-white', 'accent' => 'text-senf'],
            'beige' => ['bg' => 'bg-beige', 'text' => 'text-dunkel', 'accent' => 'text-rost-dunkel'],
            'weiss' => ['bg' => 'bg-white', 'text' => 'text-dunkel', 'accent' => 'text-rost'],
            default => ['bg' => 'bg-rost', 'text' => 'text-white', 'accent' => 'text-white'],
        };
    }

    /** Hintergrund eines ganzen Abschnitts. */
    public static function section(?string $background): string
    {
        return match ($background) {
            'beige' => 'bg-beige',
            'dunkel' => 'bg-dunkel text-creme [&_h2]:text-white',
            default => '',
        };
    }

    /** Adresse eines hochgeladenen Bildes oder Dokuments. */
    public static function file(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    /** Interne Links relativ lassen, alles andere unverändert. */
    public static function link(?string $url): ?string
    {
        return blank($url) ? null : $url;
    }

    public static function isExternal(?string $url): bool
    {
        return filled($url) && str_starts_with($url, 'http') && ! str_starts_with($url, config('app.url'));
    }
}
