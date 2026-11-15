<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembershipRequest;
use App\Mail\GuardianConfirmation;
use App\Mail\MembershipReceived;
use App\Mail\NewMembershipApplication;
use App\Models\MembershipApplication;
use App\Models\Setting;
use App\Support\MembershipType;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MembershipController extends Controller
{
    public function create(): View
    {
        return view('membership.create', [
            'types' => MembershipType::cases(),
        ]);
    }

    public function store(MembershipRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = MembershipType::from($data['type']);

        // Nur Felder übernehmen, die zur gewählten Art gehören.
        $application = MembershipApplication::create([
            'type' => $type->value,
            'fee' => $type->fee(),
            'organisation' => $type->isOrganisation() ? $data['organisation'] : null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'birthdate' => $type->needsGuardian() ? $data['birthdate'] : null,
            'partner_first_name' => $type->hasPartner() ? ($data['partner_first_name'] ?? null) : null,
            'partner_last_name' => $type->hasPartner() ? ($data['partner_last_name'] ?? null) : null,
            'children' => $type->hasChildren() ? ($data['children'] ?? null) : null,
            'street' => $data['street'],
            'zip_city' => $data['zip_city'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'guardian_name' => $type->needsGuardian() ? $data['guardian_name'] : null,
            'guardian_email' => $type->needsGuardian() ? $data['guardian_email'] : null,
            'donation' => $data['donation'] ?? null,
            'message' => $data['message'] ?? null,
            'statutes_accepted_at' => now(),
            'privacy_accepted_at' => now(),
            'status' => $type->needsGuardian() ? 'bestaetigung' : 'eingegangen',
        ]);

        $this->send($application->email, new MembershipReceived($application));

        if ($type->needsGuardian()) {
            $link = URL::temporarySignedRoute('membership.confirm', now()->addDays(30), ['application' => $application->id]);
            $this->send($application->guardian_email, new GuardianConfirmation($application, $link));
        } else {
            $this->notifyBoard($application);
        }

        return redirect()->route('membership.thanks')->with('application_type', $type->value);
    }

    public function thanks(): View|RedirectResponse
    {
        if (! session()->has('application_type')) {
            return redirect()->route('membership.create');
        }

        return view('membership.thanks', [
            'type' => MembershipType::from(session('application_type')),
        ]);
    }

    /** Link aus der E-Mail an die erziehungsberechtigte Person. */
    public function confirm(MembershipApplication $application): View
    {
        if ($application->guardian_confirmed_at === null) {
            $application->update([
                'guardian_confirmed_at' => now(),
                'status' => $application->status === 'bestaetigung' ? 'eingegangen' : $application->status,
            ]);
            $this->notifyBoard($application);
        }

        return view('membership.confirmed', ['application' => $application]);
    }

    private function notifyBoard(MembershipApplication $application): void
    {
        $this->send(Setting::get('contact_email'), new NewMembershipApplication($application));
    }

    /** E-Mail senden – ein Fehler beim Versand darf das Gesuch nicht verlieren. */
    private function send(?string $to, Mailable $mail): void
    {
        if (blank($to)) {
            return;
        }

        try {
            Mail::to($to)->send($mail);
        } catch (\Throwable $e) {
            Log::error('E-Mail zum Beitrittsgesuch konnte nicht gesendet werden', [
                'mail' => $mail::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
