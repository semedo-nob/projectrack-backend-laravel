<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $projects = $user->projects()
            ->withCount([
                'expenses',
                'tasks',
                'tasks as open_tasks_count' => fn ($q) => $q->whereIn('status', ['pending', 'in_progress', 'blocked']),
            ])
            ->orderByDesc('updated_at')
            ->get();

        $projectIds = $projects->pluck('id');

        $totalSpent = (float) $projects->sum('spent');
        $totalBudget = (float) $projects->sum('budget');
        $activeCount = $projects->filter(fn ($p) => strcasecmp((string) $p->status, 'Active') === 0)->count();
        $openTasks = (int) $projects->sum('open_tasks_count');

        $trend = collect();
        if ($projectIds->isNotEmpty()) {
            $from = now()->subDays(29)->startOfDay();
            $rows = Expense::query()
                ->whereIn('project_id', $projectIds)
                ->where('date', '>=', $from)
                ->selectRaw('DATE(date) as day, SUM(amount) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day');

            $running = 0.0;
            for ($i = 0; $i < 30; $i++) {
                $day = $from->copy()->addDays($i)->toDateString();
                $running += (float) ($rows[$day] ?? 0);
                $trend->push(['day' => $day, 'total' => $running]);
            }
        }

        $maxTrend = max(1.0, (float) ($trend->last()['total'] ?? 1));
        $hour = (int) now()->format('G');
        $greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

        return view('dashboard.index', [
            'projects' => $projects,
            'totalSpent' => $totalSpent,
            'totalBudget' => $totalBudget,
            'remainingBudget' => $totalBudget - $totalSpent,
            'activeCount' => $activeCount,
            'openTasks' => $openTasks,
            'trend' => $trend,
            'maxTrend' => $maxTrend,
            'greeting' => $greeting,
        ]);
    }
}
