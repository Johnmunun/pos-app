<?php

namespace Src\Infrastructure\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportStatusController extends Controller
{
    public function index(Request $request): Response
    {
        // Données statiques simples pour commencer ; pourront être reliées à des checks réels plus tard.
        $services = [
            [
                'name' => 'API Backend',
                'status' => 'operational',
                'uptime' => '99.98%',
            ],
            [
                'name' => 'Base de données',
                'status' => 'operational',
                'uptime' => '99.95%',
            ],
            [
                'name' => 'Notifications email',
                'status' => 'degraded',
                'uptime' => '98.50%',
            ],
        ];

        $modules = [
            ['name' => 'Pharmacy', 'active' => true],
            ['name' => 'Hardware', 'active' => true],
            ['name' => 'Global Commerce', 'active' => true],
            ['name' => 'E-commerce', 'active' => true],
        ];

        return Inertia::render('Support/SystemStatus', [
            'services' => $services,
            'modules' => $modules,
        ]);
    }
}

