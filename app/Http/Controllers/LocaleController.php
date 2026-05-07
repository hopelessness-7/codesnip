<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public function switch(string $locale): RedirectResponse
    {
        $supported = config('app.supported_locales', ['en', 'ru']);
        abort_unless(in_array($locale, $supported, true), 404);

        session(['locale' => $locale]);

        return back(fallback: route('dashboard'));
    }
}
