<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Project;
use App\Support\Labels;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProjectExportService
{
    public const KINDS = ['all', 'expenses', 'tasks', 'inventory', 'activities', 'schedule'];

    public function downloadPdf(Project $project)
    {
        $data = $this->payload($project);

        return Pdf::loadView('projects.export_pdf', $data)
            ->setPaper('a4')
            ->download($this->filename($project, 'pdf'));
    }

    public function downloadCsv(Project $project, string $kind = 'all'): StreamedResponse
    {
        $kind = in_array($kind, self::KINDS, true) ? $kind : 'all';
        $data = $this->payload($project);
        $filename = $this->filename($project, 'csv', $kind === 'all' ? null : $kind);

        return response()->streamDownload(function () use ($data, $kind) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if ($kind === 'all') {
                $this->writeAllCsv($out, $data);
            } elseif ($kind === 'expenses') {
                $this->writeExpensesCsv($out, $data['expenses']);
            } elseif ($kind === 'tasks') {
                $this->writeTasksCsv($out, $data['tasks']);
            } elseif ($kind === 'inventory') {
                $this->writeInventoryCsv($out, $data['inventory'], $data['usages']);
            } elseif ($kind === 'activities') {
                $this->writeActivitiesCsv($out, $data['activities']);
            } else {
                $this->writeScheduleCsv($out, $data['milestones']);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{
     *     project: Project,
     *     expenses: Collection,
     *     tasks: Collection,
     *     inventory: Collection,
     *     usages: Collection,
     *     activities: Collection,
     *     milestones: Collection,
     *     byCategory: Collection,
     *     exportedAt: Carbon
     * }
     */
    public function payload(Project $project): array
    {
        $project->loadMissing(['tags']);

        $expenses = $project->expenses()->orderByDesc('date')->get();
        $tasks = $project->tasks()
            ->orderByRaw("CASE priority WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END")
            ->orderByDesc('updated_at')
            ->get();
        $inventory = $project->inventoryItems()->orderBy('name')->get();
        $usages = $project->materialUsages()
            ->with('inventoryItem')
            ->orderByDesc('occurred_at')
            ->get();
        $activities = $project->activityLogs()->orderByDesc('occurred_at')->get();
        $milestones = $project->milestones()->orderBy('sort_order')->orderBy('due_date')->get();
        $byCategory = Expense::query()
            ->where('project_id', $project->id)
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        return [
            'project' => $project,
            'expenses' => $expenses,
            'tasks' => $tasks,
            'inventory' => $inventory,
            'usages' => $usages,
            'activities' => $activities,
            'milestones' => $milestones,
            'byCategory' => $byCategory,
            'exportedAt' => now(),
        ];
    }

    public function filename(Project $project, string $extension, ?string $suffix = null): string
    {
        $slug = Str::slug($project->name) ?: 'project';
        $parts = [$slug, 'export'];
        if ($suffix) {
            $parts[] = $suffix;
        }
        $parts[] = now()->format('Ymd_His');

        return implode('_', $parts).'.'.$extension;
    }

    /**
     * @param  resource  $out
     */
    protected function writeAllCsv($out, array $data): void
    {
        /** @var Project $project */
        $project = $data['project'];
        $this->row($out, ['ProjectRack project export']);
        $this->row($out, ['Exported at', $data['exportedAt']->toDateTimeString()]);
        $this->row($out, []);
        $this->row($out, ['PROJECT']);
        $this->row($out, [
            'Name', 'Description', 'Status', 'Category', 'Start Date', 'End Date',
            'Budget', 'Spent', 'Remaining', 'Progress %', 'Tags',
        ]);
        $this->row($out, [
            $project->name,
            $project->description,
            $project->status,
            $project->category,
            optional($project->start_date)?->toDateString(),
            optional($project->end_date)?->toDateString(),
            $project->budget,
            $project->spent,
            (float) $project->budget - (float) $project->spent,
            round(((float) ($project->progress ?? 0)) * 100),
            $project->tags->pluck('tag')->implode(', '),
        ]);
        $this->row($out, []);
        $this->row($out, ['SPEND BY CATEGORY']);
        $this->row($out, ['Category', 'Total']);
        foreach ($data['byCategory'] as $row) {
            $this->row($out, [Labels::expenseCategory((string) $row->category), $row->total]);
        }
        $this->row($out, []);
        $this->writeExpensesCsv($out, $data['expenses']);
        $this->row($out, []);
        $this->writeTasksCsv($out, $data['tasks']);
        $this->row($out, []);
        $this->writeInventoryCsv($out, $data['inventory'], $data['usages']);
        $this->row($out, []);
        $this->writeActivitiesCsv($out, $data['activities']);
        $this->row($out, []);
        $this->writeScheduleCsv($out, $data['milestones']);
    }

    /**
     * @param  resource  $out
     */
    protected function writeExpensesCsv($out, Collection $expenses): void
    {
        $this->row($out, ['EXPENSES']);
        $this->row($out, ['Date', 'Merchant', 'Category', 'Amount', 'Qty', 'Unit', 'Status', 'Has Receipt', 'Notes']);
        foreach ($expenses as $expense) {
            $this->row($out, [
                optional($expense->date)?->toDateString(),
                $expense->merchant,
                Labels::expenseCategory((string) $expense->category),
                $expense->amount,
                $expense->quantity_original,
                $expense->unit_original,
                Labels::expenseStatus((string) $expense->status),
                filled($expense->receipt_image) ? 'Yes' : 'No',
                $expense->notes,
            ]);
        }
    }

    /**
     * @param  resource  $out
     */
    protected function writeTasksCsv($out, Collection $tasks): void
    {
        $this->row($out, ['TASKS']);
        $this->row($out, ['Title', 'Description', 'Status', 'Priority', 'Due Date', 'Assignee']);
        foreach ($tasks as $task) {
            $this->row($out, [
                $task->title,
                $task->description,
                Labels::taskStatus((string) $task->status),
                Labels::taskPriority((string) $task->priority),
                optional($task->due_date)?->toDateString(),
                $task->assigned_to,
            ]);
        }
    }

    /**
     * @param  resource  $out
     */
    protected function writeInventoryCsv($out, Collection $items, Collection $usages): void
    {
        $this->row($out, ['INVENTORY']);
        $this->row($out, ['Item', 'On hand', 'Unit', 'Reorder level', 'Status', 'Notes']);
        foreach ($items as $item) {
            $outOfStock = $item->quantity_base <= 0;
            $low = $item->reorder_level > 0 && $item->quantity_base <= $item->reorder_level;
            $this->row($out, [
                $item->name,
                $item->quantity_base,
                $item->unit_base,
                $item->reorder_level,
                $outOfStock ? 'Out of stock' : ($low ? 'Low stock' : 'In stock'),
                $item->notes,
            ]);
        }
        $this->row($out, []);
        $this->row($out, ['MATERIAL MOVEMENTS']);
        $this->row($out, ['When', 'Item', 'Kind', 'Quantity', 'Unit', 'Notes']);
        foreach ($usages as $usage) {
            $this->row($out, [
                optional($usage->occurred_at)?->toDateTimeString(),
                $usage->inventoryItem?->name,
                $usage->kind,
                $usage->quantity_original ?? $usage->quantity_base,
                $usage->unit_original ?? $usage->unit_base,
                $usage->notes,
            ]);
        }
    }

    /**
     * @param  resource  $out
     */
    protected function writeActivitiesCsv($out, Collection $activities): void
    {
        $this->row($out, ['ACTIVITIES']);
        $this->row($out, ['When', 'Title', 'Type', 'Hours', 'Performed by', 'Location', 'Notes']);
        foreach ($activities as $activity) {
            $this->row($out, [
                optional($activity->occurred_at)?->toDateTimeString(),
                $activity->title,
                Labels::activityType((string) $activity->activity_type),
                $activity->hours_spent,
                $activity->performed_by,
                $activity->location,
                $activity->description,
            ]);
        }
    }

    /**
     * @param  resource  $out
     */
    protected function writeScheduleCsv($out, Collection $milestones): void
    {
        $this->row($out, ['SCHEDULE']);
        $this->row($out, ['Title', 'Description', 'Due Date', 'Status', 'Completed at']);
        foreach ($milestones as $milestone) {
            $this->row($out, [
                $milestone->title,
                $milestone->description,
                optional($milestone->due_date)?->toDateString(),
                Labels::milestoneStatus((string) $milestone->status),
                optional($milestone->completed_at)?->toDateTimeString(),
            ]);
        }
    }

    /**
     * @param  resource  $out
     * @param  list<mixed>  $fields
     */
    protected function row($out, array $fields): void
    {
        fputcsv($out, $fields);
    }
}
