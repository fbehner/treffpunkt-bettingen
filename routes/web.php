<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'home'])->name('home');

Route::get('/aktuelles', [SiteController::class, 'posts'])->name('posts.index');
Route::get('/aktuelles/{post:slug}', [SiteController::class, 'post'])->name('posts.show');

Route::get('/mitglied-werden', [MembershipController::class, 'create'])->name('membership.create');
Route::post('/mitglied-werden', [MembershipController::class, 'store'])
    ->middleware('throttle:5,10')
    ->name('membership.store');
Route::get('/mitglied-werden/danke', [MembershipController::class, 'thanks'])->name('membership.thanks');
Route::get('/mitglied-werden/bestaetigen/{application}', [MembershipController::class, 'confirm'])
    ->middleware('signed')
    ->name('membership.confirm');

// Registrierung über Einladungslink (Konten für Verwaltung/Backoffice)
Route::get('/einladung/fertig', [InvitationController::class, 'done'])->name('invitation.done');
Route::get('/einladung/{token}', [InvitationController::class, 'show'])->name('invitation.show');
Route::post('/einladung/{token}', [InvitationController::class, 'store'])
    ->middleware('throttle:10,10')
    ->name('invitation.store');

// Muss die letzte Route bleiben: fängt alle Seiten-Adressen ab.
Route::get('/{slug}', [SiteController::class, 'page'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('page.show');
