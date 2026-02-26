<?php

namespace Src\Infrastructure\Hardware\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class HardwareDashboardController
{
    public function index(Request $request): Response
    {
        // Squelette de dashboard Quincaillerie – à enrichir avec un vrai service DDD plus tard
        return Inertia::render('Hardware/Dashboard', [
            'filters' => [
                'period' => (int) $request->input('period', 14),
            ],
            'stats' => [
                'products_total' => 0,
                'low_stock_count' => 0,
                'out_of_stock_count' => 0,
            ],
        ]);
    }
}

