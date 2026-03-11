<?php

namespace Src\Infrastructure\Logs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Logs\Models\UserLoginHistoryModel;

class UserLoginHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = UserLoginHistoryModel::query();

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', (int) $userId);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $logins = $query->orderByDesc('logged_in_at')
            ->paginate(50)
            ->through(function (UserLoginHistoryModel $login) {
                return [
                    'id' => $login->id,
                    'user_id' => $login->user_id,
                    'logged_in_at' => $login->logged_in_at?->toDateTimeString(),
                    'ip_address' => $login->ip_address,
                    'user_agent' => $login->user_agent,
                    'device' => $login->device,
                    'status' => $login->status,
                ];
            });

        return Inertia::render('Logs/UserConnections', [
            'logins' => $logins,
            'filters' => [
                'user_id' => $request->query('user_id'),
                'status' => $request->query('status'),
            ],
        ]);
    }
}

