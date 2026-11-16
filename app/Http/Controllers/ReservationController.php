<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReservationRequest;
use App\Models\RoomReservation;
use App\Support\ReservationMails;
use App\Support\ReservationRules;
use App\Support\RoomPackage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReservationController extends Controller
{
    public function create(Request $request): View
    {
        $first = now()->startOfMonth();
        $last = ReservationRules::latestDate()->startOfMonth();

        $month = $first->copy();
        if (preg_match('/^(\d{4})-(\d{2})$/', (string) $request->query('monat'), $m) && checkdate((int) $m[2], 1, (int) $m[1])) {
            $month = Carbon::create((int) $m[1], (int) $m[2], 1)->startOfDay();
        }
        $month = $month->lt($first) ? $first : ($month->gt($last) ? $last : $month);

        $reservations = RoomReservation::query()->confirmed()
            ->where('starts_at', '<', $month->copy()->addMonth())
            ->where('ends_at', '>', $month)
            ->orderBy('starts_at')
            ->get(['id', 'package', 'starts_at', 'ends_at', 'public_title']);

        return view('reservation.create', [
            'packages' => RoomPackage::cases(),
            'times' => ReservationRules::timeOptions(),
            'earliest' => ReservationRules::earliestDate(),
            'latest' => ReservationRules::latestDate(),
            'cafeFrom' => ReservationRules::cafeFrom(),
            'month' => $month,
            'prevMonth' => $month->gt($first) ? $month->copy()->subMonth() : null,
            'nextMonth' => $month->lt($last) ? $month->copy()->addMonth() : null,
            'byDay' => $reservations->groupBy(fn (RoomReservation $r) => $r->starts_at->toDateString()),
        ]);
    }

    public function store(ReservationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        [$start, $end] = $request->period();
        $package = RoomPackage::from($data['package']);
        $member = (bool) ($data['is_member'] ?? false);

        $reservation = RoomReservation::create([
            'package' => $package->value,
            'starts_at' => $start,
            'ends_at' => $end,
            'status' => 'angefragt',
            'source' => 'web',
            'purpose' => $data['purpose'],
            'public_title' => $data['public_title'] ?? null,
            'participants' => $data['participants'] ?? null,
            'organisation' => $data['organisation'] ?? null,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'street' => $data['street'],
            'zip_city' => $data['zip_city'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'is_member' => $member,
            'member_number' => $member ? ($data['member_number'] ?? null) : null,
            'price' => $package->price((int) $start->diffInMinutes($end), $member),
            'message' => $data['message'] ?? null,
            'house_rules_accepted_at' => now(),
            'privacy_accepted_at' => now(),
        ]);

        ReservationMails::received($reservation);
        ReservationMails::notifyTeam($reservation);

        return redirect()->route('reservation.thanks')->with('reservation_id', $reservation->id);
    }

    public function thanks(): View|RedirectResponse
    {
        $reservation = RoomReservation::find(session('reservation_id'));

        if (! $reservation) {
            return redirect()->route('reservation.create');
        }

        return view('reservation.thanks', ['reservation' => $reservation]);
    }
}
