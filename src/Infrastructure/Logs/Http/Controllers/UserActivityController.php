<?php

namespace Src\Infrastructure\Logs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Logs\Models\UserActivityModel;

class UserActivityController extends Controller
{
    public function index(Request $request): Response
    {
        $query = UserActivityModel::query();

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', (int) $userId);
        }

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', '%' . $search . '%')
                    ->orWhere('route', 'like', '%' . $search . '%');
            });
        }

        $activities = $query->orderByDesc('created_at')
            ->paginate(50)
            ->through(function (UserActivityModel $activity) {
                return [
                    'id' => $activity->id,
                    'user_id' => $activity->user_id,
                    'action' => $activity->action,
                    'module' => $activity->module,
                    'route' => $activity->route,
                    'ip_address' => $activity->ip_address,
                    'created_at' => $activity->created_at?->toDateTimeString(),
                ];
            });

        return Inertia::render('Logs/UserActions', [
            'activities' => $activities,
            'filters' => [
                'user_id' => $request->query('user_id'),
                'module' => $request->query('module'),
                'q' => $request->query('q'),
            ],
        ]);
    }
}

