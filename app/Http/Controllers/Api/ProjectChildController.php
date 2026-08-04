<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\MaterialUsage;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\Receipt;
use App\Models\SyncTombstone;
use App\Models\Task;
use App\Services\ProjectAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectChildController extends Controller
{
    public function expensesIndex(Request $request, string $project): JsonResponse
    {
        return $this->index($request, $project, Expense::class);
    }

    public function expensesStore(Request $request, string $project): JsonResponse
    {
        return $this->store($request, $project, Expense::class, [
            'id' => ['nullable', 'string', 'max:50'],
            'merchant' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'date' => ['required', 'date'],
            'category' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'receipt_image' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'quantity_original' => ['nullable', 'numeric'],
            'unit_original' => ['nullable', 'string'],
            'quantity_base' => ['nullable', 'numeric'],
            'unit_base' => ['nullable', 'string'],
        ]);
    }

    public function tasksIndex(Request $request, string $project): JsonResponse
    {
        return $this->index($request, $project, Task::class);
    }

    public function tasksStore(Request $request, string $project): JsonResponse
    {
        return $this->store($request, $project, Task::class, [
            'id' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'status' => ['nullable', 'string'],
            'priority' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'string'],
        ]);
    }

    public function inventoryIndex(Request $request, string $project): JsonResponse
    {
        return $this->index($request, $project, InventoryItem::class);
    }

    public function inventoryStore(Request $request, string $project): JsonResponse
    {
        return $this->store($request, $project, InventoryItem::class, [
            'id' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string'],
            'name_key' => ['required', 'string'],
            'quantity_base' => ['nullable', 'numeric'],
            'unit_base' => ['required', 'string'],
            'preferred_unit_id' => ['nullable', 'string'],
            'reorder_level' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    public function usagesIndex(Request $request, string $project): JsonResponse
    {
        return $this->index($request, $project, MaterialUsage::class);
    }

    public function usagesStore(Request $request, string $project): JsonResponse
    {
        return $this->store($request, $project, MaterialUsage::class, [
            'id' => ['nullable', 'string', 'max:50'],
            'inventory_item_id' => ['required', 'string'],
            'kind' => ['required', 'string'],
            'quantity_base' => ['required', 'numeric'],
            'unit_base' => ['required', 'string'],
            'quantity_original' => ['nullable', 'numeric'],
            'unit_original' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'expense_id' => ['nullable', 'string'],
            'occurred_at' => ['required', 'date'],
        ], timestamps: false);
    }

    public function activitiesIndex(Request $request, string $project): JsonResponse
    {
        return $this->index($request, $project, ActivityLog::class);
    }

    public function activitiesStore(Request $request, string $project): JsonResponse
    {
        return $this->store($request, $project, ActivityLog::class, [
            'id' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['nullable', 'string'],
            'occurred_at' => ['required', 'date'],
            'hours_spent' => ['nullable', 'numeric'],
            'performed_by' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
            'linked_expense_id' => ['nullable', 'string'],
        ]);
    }

    public function milestonesIndex(Request $request, string $project): JsonResponse
    {
        return $this->index($request, $project, ProjectMilestone::class);
    }

    public function milestonesStore(Request $request, string $project): JsonResponse
    {
        return $this->store($request, $project, ProjectMilestone::class, [
            'id' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'completed_at' => ['nullable', 'date'],
        ]);
    }

    public function receiptsStore(Request $request, string $project): JsonResponse
    {
        ProjectAccess::ensureWrite($request->user(), $project);

        $data = $request->validate([
            'id' => ['nullable', 'string', 'max:50'],
            'expense_id' => ['required', 'string'],
            'image_url' => ['required', 'string'],
            'ocr_data' => ['nullable', 'string'],
            'confidence' => ['nullable', 'numeric'],
            'processed_at' => ['nullable', 'date'],
        ]);

        $expense = Expense::where('project_id', $project)->findOrFail($data['expense_id']);

        $receipt = Receipt::create([
            'id' => $data['id'] ?? ('rcpt_'.Str::lower(Str::ulid())),
            'expense_id' => $expense->id,
            'image_url' => $data['image_url'],
            'ocr_data' => $data['ocr_data'] ?? null,
            'confidence' => $data['confidence'] ?? null,
            'processed_at' => $data['processed_at'] ?? null,
            'created_at' => now(),
        ]);

        return response()->json(['data' => $receipt], 201);
    }

    /**
     * @param  class-string<Model>  $class
     */
    protected function index(Request $request, string $project, string $class): JsonResponse
    {
        ProjectAccess::ensureView($request->user(), $project);
        Project::findOrFail($project);

        $rows = $class::where('project_id', $project)->orderByDesc('created_at')->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * @param  class-string<Model>  $class
     * @param  array<string, mixed>  $rules
     */
    protected function store(Request $request, string $project, string $class, array $rules, bool $timestamps = true): JsonResponse
    {
        ProjectAccess::ensureWrite($request->user(), $project);
        Project::findOrFail($project);

        $data = $request->validate($rules);
        $id = $data['id'] ?? (Str::lower(class_basename($class)).'_'.Str::lower(Str::ulid()));
        unset($data['id']);

        $attrs = array_merge($data, [
            'id' => $id,
            'project_id' => $project,
        ]);

        if (! $timestamps) {
            $attrs['created_at'] = $attrs['created_at'] ?? now();
        }

        /** @var Model $row */
        $row = $class::create($attrs);

        return response()->json(['data' => $row], 201);
    }

    public function destroyChild(Request $request, string $project, string $entity, string $id): JsonResponse
    {
        ProjectAccess::ensureWrite($request->user(), $project);

        $map = [
            'expenses' => Expense::class,
            'tasks' => Task::class,
            'inventory' => InventoryItem::class,
            'usages' => MaterialUsage::class,
            'activities' => ActivityLog::class,
            'milestones' => ProjectMilestone::class,
            'receipts' => Receipt::class,
        ];

        abort_unless(isset($map[$entity]), 404);

        $class = $map[$entity];
        if ($entity === 'receipts') {
            $row = Receipt::whereIn('expense_id', Expense::where('project_id', $project)->select('id'))
                ->where('id', $id)
                ->firstOrFail();
        } else {
            $row = $class::where('project_id', $project)->findOrFail($id);
        }

        SyncTombstone::create([
            'entity_type' => Str::singular($entity === 'inventory' ? 'inventory_item' : ($entity === 'usages' ? 'material_usage' : ($entity === 'activities' ? 'activity_log' : ($entity === 'milestones' ? 'milestone' : Str::singular($entity))))),
            'entity_id' => $id,
            'project_id' => $project,
            'deleted_at' => now(),
        ]);

        $row->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
