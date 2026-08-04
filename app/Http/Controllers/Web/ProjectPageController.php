<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Project;
use App\Services\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectPageController extends Controller
{
    public function show(Request $request, string $project): View
    {
        $model = $this->load($request, $project, ['tags', 'members.user']);

        return view('projects.show', ['project' => $model]);
    }

    public function expenses(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $expenses = $model->expenses()->with('receipts')->orderByDesc('date')->paginate(25);

        return view('projects.expenses', ['project' => $model, 'expenses' => $expenses]);
    }

    public function tasks(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $tasks = $model->tasks()->orderByDesc('updated_at')->get();

        return view('projects.tasks', ['project' => $model, 'tasks' => $tasks]);
    }

    public function inventory(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $items = $model->inventoryItems()->orderBy('name')->get();

        return view('projects.inventory', ['project' => $model, 'items' => $items]);
    }

    public function activities(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $activities = $model->activityLogs()->orderByDesc('occurred_at')->paginate(25);

        return view('projects.activities', ['project' => $model, 'activities' => $activities]);
    }

    public function schedule(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $milestones = $model->milestones()->orderBy('sort_order')->orderBy('due_date')->get();

        return view('projects.schedule', ['project' => $model, 'milestones' => $milestones]);
    }

    public function reports(Request $request, string $project): View
    {
        $model = $this->load($request, $project);
        $byCategory = Expense::where('project_id', $model->id)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return view('projects.reports', [
            'project' => $model,
            'byCategory' => $byCategory,
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
