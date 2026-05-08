<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(DashboardService $dashboard): View
    {
        $payload = $dashboard->buildForUser((int) auth()->id());

        return view('dashboard', $payload);
    }
}
