<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Support\Invitations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Registrierung über einen Einladungslink. Das neue Konto hat keine Rolle,
 * bis ein Webadmin es freischaltet.
 */
class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = Invitation::findByToken($token);

        if (! $invitation || ! $invitation->isOpen()) {
            return view('invitation.invalid', ['invitation' => $invitation]);
        }

        return view('invitation.register', ['invitation' => $invitation, 'token' => $token]);
    }

    public function store(Request $request, string $token): View|RedirectResponse
    {
        $invitation = Invitation::findByToken($token);

        if (! $invitation || ! $invitation->isOpen()) {
            return view('invitation.invalid', ['invitation' => $invitation]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(10)],
        ], [
            'password.confirmed' => 'Die beiden Passwörter stimmen nicht überein.',
        ], [
            'name' => 'Name',
            'password' => 'Passwort',
        ]);

        if (User::query()->where('email', $invitation->email)->exists()) {
            return back()->withErrors(['name' => 'Für diese E-Mail-Adresse gibt es bereits ein Konto. Bitte melde dich an oder nutze „Passwort vergessen“.']);
        }

        $user = DB::transaction(function () use ($invitation, $data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
                'roles' => [],
                'is_active' => true,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->update(['accepted_at' => now(), 'user_id' => $user->id]);

            return $user;
        });

        Invitations::notifyAdmins($user);

        return redirect()->route('invitation.done');
    }

    public function done(): View
    {
        return view('invitation.done');
    }
}
