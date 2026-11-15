<?php

namespace App\Http\Requests;

use App\Support\MembershipType;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = MembershipType::tryFrom((string) $this->input('type'));
        $minor = $type?->needsGuardian() ?? false;

        return [
            'type' => ['required', Rule::enum(MembershipType::class)],
            'organisation' => [Rule::requiredIf($type === MembershipType::Verein), 'nullable', 'string', 'max:160'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'birthdate' => [Rule::requiredIf($minor), 'nullable', 'date', 'before:today', $this->ageRule($type)],
            'partner_first_name' => [Rule::requiredIf($type === MembershipType::Paar), 'nullable', 'string', 'max:80'],
            'partner_last_name' => [Rule::requiredIf($type === MembershipType::Paar), 'nullable', 'string', 'max:80'],
            'children' => ['nullable', 'string', 'max:1000'],
            'street' => ['required', 'string', 'max:120'],
            'zip_city' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:160'],
            'guardian_name' => [Rule::requiredIf($minor), 'nullable', 'string', 'max:160'],
            'guardian_email' => [Rule::requiredIf($minor), 'nullable', 'email', 'max:160'],
            'donation' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'message' => ['nullable', 'string', 'max:2000'],
            'statutes' => ['accepted'],
            'privacy' => ['accepted'],
            // Falle für Spam-Programme: muss leer bleiben.
            'website' => ['prohibited'],
        ];
    }

    /** Alter muss zur gewählten Kinder- oder Jugendmitgliedschaft passen. */
    private function ageRule(?MembershipType $type): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($type) {
            $range = $type?->ageRange();
            if (! $range || blank($value)) {
                return;
            }

            try {
                $age = Carbon::parse($value)->age;
            } catch (\Throwable) {
                return;
            }

            [$min, $max] = $range;
            if ($age < $min || $age > $max) {
                $fail("Für „{$type->label()}“ gilt ein Alter von {$min} bis {$max} Jahren.");
            }
        };
    }

    public function attributes(): array
    {
        return [
            'type' => 'Art der Mitgliedschaft',
            'organisation' => 'Verein / Institution',
            'first_name' => 'Vorname',
            'last_name' => 'Name',
            'birthdate' => 'Geburtsdatum',
            'partner_first_name' => 'Vorname der zweiten Person',
            'partner_last_name' => 'Name der zweiten Person',
            'children' => 'Kinder',
            'street' => 'Strasse und Nr.',
            'zip_city' => 'PLZ und Ort',
            'phone' => 'Telefon',
            'email' => 'E-Mail',
            'guardian_name' => 'Name der erziehungsberechtigten Person',
            'guardian_email' => 'E-Mail der erziehungsberechtigten Person',
            'donation' => 'Gönnerbeitrag',
            'message' => 'Mitteilung',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Bitte :attribute angeben.',
            'required_if' => 'Bitte :attribute angeben.',
            'email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'date' => 'Bitte ein gültiges Datum angeben.',
            'before' => 'Das Geburtsdatum muss in der Vergangenheit liegen.',
            'integer' => ':attribute bitte als ganze Zahl angeben.',
            'min' => ':attribute darf nicht negativ sein.',
            'max' => ':attribute ist zu lang.',
            'donation.max' => 'Der Gönnerbeitrag ist zu hoch – bitte nehmt direkt Kontakt mit uns auf.',
            'statutes.accepted' => 'Bitte bestätigt, dass ihr die Statuten gelesen habt.',
            'privacy.accepted' => 'Bitte stimmt der Speicherung eurer Angaben zu.',
            'website.prohibited' => 'Das Formular konnte nicht gesendet werden.',
            'type.enum' => 'Bitte eine Art der Mitgliedschaft wählen.',
        ];
    }
}
