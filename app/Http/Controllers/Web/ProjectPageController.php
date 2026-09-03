<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\MaterialUsage;
use App\Models\Project;
use App\Services\ProjectAccess;
use App\Support\BudgetForecast;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectPageController extends Controller
{
    public function show(Request $request, string $project): View
    {
        $model = $this->load($request, $project, ['tags', 'members.user']);
        $expenseCount = $model->expenses()->count();
        $openTasks = $model->tasks()->whereIn('status', ['pending', 'in_progress', 'blocked'])->count();
        $lowStock = $model->inventoryItems()
            ->where('reorder_level', '>', 0)
            ->whereColumn('quantity_base', '<=', 'reorder_level')
            ->count();
        $recentExpenses = $model->expenses()->with('receipts')->orderByDesc('date')->limit(5)->get();
        $dailyLogs = $model->expenses()
            ->selectRaw('DATE(date) as day, COUNT(*) as items, SUM(amount) as total')
            ->groupBy('day')
            ->orderByDesc('day')
            ->limit(5)
            ->get();

        return view('projects.show', [
            'project' => $model,
            'expenseCount' => $expenseCount,
            'openTasks' => $openTasks,
            'lowStock' => $lowStock,
            'recentExpenses' => $recentExpenses,
            'dailyLogs' => $dailyLogs,
        ]);
    }

    public function expenses(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $expenses = $model->expenses()->with('receipts')->orderByDesc('date')->paginate(25);
        $pending = $model->expenses()->whereIn('status', ['pending', 'pending_review', 'missing'])->count();

        return view('projects.expenses', [
            'project' => $model,
            'expenses' => $expenses,
            'pending' => $pending,
            'totalSpent' => (float) $model->expenses()->sum('amount'),
        ]);
    }

    public function tasks(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $tasks = $model->tasks()->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->get();

        return view('projects.tasks', [
            'project' => $model,
            'tasks' => $tasks,
            'counts' => [
                'pending' => $tasks->where('status', 'pending')->count(),
                'in_progress' => $tasks->where('status', 'in_progress')->count(),
                'done' => $tasks->where('status', 'done')->count(),
                'blocked' => $tasks->where('status', 'blocked')->count(),
            ],
        ]);
    }

    public function inventory(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $items = $model->inventoryItems()->orderBy('name')->get();
        $usages = MaterialUsage::where('project_id', $model->id)
            ->with('inventoryItem')
            ->orderByDesc('occurred_at')
            ->limit(20)
            ->get();

        return view('projects.inventory', [
            'project' => $model,
            'items' => $items,
            'usages' => $usages,
            'lowStockCount' => $items->filter(fn ($i) => $i->reorder_level > 0 && $i->quantity_base <= $i->reorder_level)->count(),
        ]);
    }

    public function activities(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $activities = $model->activityLogs()->orderByDesc('occurred_at')->paginate(25);
        $hours = (float) $model->activityLogs()->sum('hours_spent');

        return view('projects.activities', [
            'project' => $model,
            'activities' => $activities,
            'totalHours' => $hours,
        ]);
    }

    public function schedule(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $milestones = $model->milestones()->orderBy('sort_order')->orderBy('due_date')->get();
        $now = now();

        return view('projects.schedule', [
            'project' => $model,
            'milestones' => $milestones,
            'counts' => [
                'total' => $milestones->count(),
                'done' => $milestones->where('status', 'done')->count(),
                'overdue' => $milestones->filter(fn ($m) => $m->status === 'pending' && $m->due_date && $m->due_date->lt($now))->count(),
            ],
        ]);
    }

    public function reports(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $byCategory = Expense::where('project_id', $model->id)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $byStatus = Expense::where('project_id', $model->id)
            ->selectRaw('status, COUNT(*) as c, SUM(amount) as total')
            ->groupBy('status')
            ->get();

        $forecast = BudgetForecast::compute(
            (float) $model->budget,
            $model->start_date,
            $model->end_date,
            Expense::where('project_id', $model->id)->get(['amount', 'category', 'date']),
        );

        return view('projects.reports', [
            'project' => $model,
            'byCategory' => $byCategory,
            'byStatus' => $byStatus,
            'forecast' => $forecast,
        ]);
    }

    /**
     * @param  list<string>  $with
     */
    protected function load(Request $request, string $project, array $with = []): Project
    {
        $model = Project::with($with)->findOrFail($project);
        ProjectAccess::ensureView($request->user(), $model);

        return $model;
    }
}
