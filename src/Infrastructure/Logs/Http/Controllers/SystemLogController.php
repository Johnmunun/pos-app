<?php

namespace Src\Infrastructure\Logs\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Src\Infrastructure\Logs\Models\SystemLogModel;

class SystemLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = SystemLogModel::query();

        if ($level = $request->query('level')) {
            $query->where('level', $level);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', '%' . $search . '%')
                    ->orWhere('module', 'like', '%' . $search . '%');
            });
        }

        /** @var \Illuminate\Pagination\LengthAwarePaginator $logs */
        $logs = $query->orderByDesc('logged_at')->paginate(50);

        $logs = $logs->through(function (SystemLogModel $log) {
                return [
                    'id' => $log->id,
                    'logged_at' => $log->logged_at?->toDateTimeString(),
                    'level' => $log->level,
                    'message' => $log->message,
                    'module' => $log->module,
                    'user_id' => $log->user_id,
                    'ip_address' => $log->ip_address,
                ];
            });

        return Inertia::render('Logs/SystemLogs', [
            'logs' => $logs,
            'filters' => [
                'level' => $request->query('level'),
                'q' => $request->query('q'),
            ],
        ]);
    }

    public function download(Request $request): HttpResponse
    {
        $query = SystemLogModel::query();

        if ($level = $request->query('level')) {
            $query->where('level', $level);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', '%' . $search . '%')
                    ->orWhere('module', 'like', '%' . $search . '%');
            });
        }

        $logs = $query->orderByDesc('logged_at')->limit(1000)->get();

        $lines = [];
        $lines[] = 'date,level,module,user_id,ip,message';
        foreach ($logs as $log) {
            $lines[] = sprintf(
                '"%s","%s","%s","%s","%s","%s"',
                $log->logged_at?->toDateTimeString(),
                $log->level,
                $log->module,
                $log->user_id,
                $log->ip_address,
                str_replace('"', '""', $log->message)
            );
        }
        $content = implode("\n", $lines);

        $filename = 'system-logs-' . now()->format('Ymd-His') . '.csv';

        return new HttpResponse($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}

