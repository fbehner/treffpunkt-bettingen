<?php

namespace App\Http\Requests;

use App\Support\ReservationRules;
use App\Support\RoomPackage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $times = ReservationRules::timeOptions();

        return [
            'package' => ['required', Rule::enum(RoomPackage::class)],
            'date' => ['required', 'date_format:Y-m-d'],
            'start' => ['required', Rule::in($times)],
            'end' => ['required', Rule::in($times)],
            'purpose' => ['required', 'string', 'max:200'],
            'public_title' => ['nullable', 'string', 'max:80'],
            'participants' => ['nullable', 'integer', 'min:1', 'max:500'],
            'organisation' => ['nullable', 'string', 'max:160'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'street' => ['required', 'string', 'max:120'],
            'zip_city' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160'],
            'phone' => ['required', 'string', 'max:40'],
            'is_member' => ['nullable', 'boolean'],
            'member_number' => ['nullable', 'string', 'max:20'],
            'message' => ['nullable', 'string', 'max:2000'],
            'house_rules' => ['accepted'],
            'privacy' => ['accepted'],
            // Falle für Spam-Programme: muss leer bleiben.
            'website' => ['prohibited'],
        ];
    }

    /** Zeitregeln und Belegung erst prüfen, wenn die Einzelfelder stimmen. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['package', 'date', 'start', 'end'])) {
                    return;
                }

                [$start, $end] = $this->period();
                $error = ReservationRules::check(RoomPackage::from($this->input('package')), $start, $end);

                if ($error !== null) {
                    $validator->errors()->add('date', $error);
                }
            },
        ];
    }

    /** @return array{0: Carbon, 1: Carbon} */
    public function period(): array
    {
        $date = $this->input('date');

        return [
            Carbon::parse($date.' '.$this->input('start')),
            Carbon::parse($date.' '.$this->input('end')),
        ];
    }

    public function attributes(): array
    {
        return [
            'package' => 'Raum',
            'date' => 'Datum',
            'start' => 'Beginn',
            'end' => 'Ende',
            'purpose' => 'Anlass',
            'public_title' => 'Titel im Kalender',
            'participants' => 'Anzahl Personen',
            'organisation' => 'Verein / Organisation',
            'first_name' => 'Vorname',
            'last_name' => 'Name',
            'street' => 'Strasse und Nr.',
            'zip_city' => 'PLZ und Ort',
            'email' => 'E-Mail',
            'phone' => 'Telefon',
            'member_number' => 'Mitgliedernummer',
            'message' => 'Mitteilung',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Bitte :attribute angeben.',
            'package.required' => 'Bitte einen Raum wählen.',
            'package.enum' => 'Bitte einen Raum wählen.',
            'date_format' => 'Bitte ein gültiges Datum angeben.',
            'in' => 'Bitte eine Uhrzeit aus der Liste wählen.',
            'email' => 'Bitte eine gültige E-Mail-Adresse angeben.',
            'integer' => ':attribute bitte als ganze Zahl angeben.',
            'min' => ':attribute muss mindestens :min sein.',
            'max' => ':attribute ist zu lang.',
            'participants.max' => 'Für so viele Personen nehmt bitte direkt Kontakt mit uns auf.',
            'house_rules.accepted' => 'Bitte bestätigt, dass ihr die Hausordnung einhaltet.',
            'privacy.accepted' => 'Bitte stimmt der Speicherung eurer Angaben zu.',
            'website.prohibited' => 'Das Formular konnte nicht gesendet werden.',
        ];
    }
}
