<?php

use App\Models\SentMail;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Alte Einträge im E-Mail-Protokoll löschen (nach 12 Monaten).
Schedule::command('model:prune', ['--model' => [SentMail::class]])->daily();
