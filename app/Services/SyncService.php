<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Expense;
use App\Models\InventoryItem;
use App\Models\MaterialUsage;
use App\Models\Media;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectTag;
use App\Models\Receipt;
use App\Models\SyncTombstone;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncService
{
    /** @var array<string, class-string> */
    public const ENTITIES = [
        'project' => Project::class,
        'expense' => Expense::class,
        'receipt' => Receipt::class,
        'task' => Task::class,
        'inventory_item' => InventoryItem::class,
        'material_usage' => MaterialUsage::class,
        'activity_log' => ActivityLog::class,
        'milestone' => ProjectMilestone::class,
        'media' => Media::class,
    ];

    /**
     * @param  array<int, array{entity: string, id: string, op: string, payload?: array, client_updated_at?: string}>  $items
     * @return array{results: list<array<string, mixed>>}
     */
    public function push(User $user, array $items): array
    {
        $results = [];

        foreach ($items as $item) {
            $results[] = $this->applyItem($user, $item);
        }

        return ['results' => $results];
    }

    /**
     * @param  array{entity: string, id: string, op: string, payload?: array, client_updated_at?: string}  $item
     * @return array<string, mixed>
     */
    protected function applyItem(User $user, array $item): array
    {
        $entity = $item['entity'] ?? '';
        $id = $item['id'] ?? '';
        $op = $item['op'] ?? '';
        $payload = $item['payload'] ?? [];
        $clientUpdatedAt = isset($item['client_updated_at'])
            ? Carbon::parse($item['client_updated_at'])
            : now();

        if ($entity === '' || $id === '' || $op === '') {
            return ['id' => $id, 'entity' => $entity, 'status' => 'error', 'message' => 'Invalid item'];
        }

        try {
            return DB::transaction(function () use ($user, $entity, $id, $op, $payload, $clientUpdatedAt) {
                if ($op === 'delete') {
                    return $this->deleteEntity($user, $entity, $id, $payload);
                }

                if ($entity === 'project') {
                    return $this->upsertProject($user, $id, $payload, $clientUpdatedAt);
                }

                if ($entity === 'project_tag') {
                    return $this->upsertTag($user, $payload);
                }

                if (! isset(self::ENTITIES[$entity])) {
                    return ['id' => $id, 'entity' => $entity, 'status' => 'error', 'message' => 'Unknown entity'];
                }

                return $this->upsertChild($user, $entity, $id, $payload, $clientUpdatedAt);
            });
        } catch (\Throwable $e) {
            return [
                'id' => $id,
                'entity' => $entity,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function upsertProject(User $user, string $id, array $payload, Carbon $clientUpdatedAt): array
    {
        $existing = Project::find($id);

        if ($existing) {
            ProjectAccess::ensureWrite($user, $existing);
            if ($existing->updated_at && $clientUpdatedAt->lt($existing->updated_at)) {
                return ['id' => $id, 'entity' => 'project', 'status' => 'conflict', 'server' => $existing];
            }
            $existing->fill($this->projectAttrs($payload));
            $existing->updated_at = $clientUpdatedAt;
            $existing->save();

            return ['id' => $id, 'entity' => 'project', 'status' => 'updated'];
        }

        $attrs = $this->projectAttrs($payload);
        $attrs['id'] = $id;
        $attrs['owner_id'] = $attrs['owner_id'] ?? $user->id;
        $attrs['created_at'] = isset($payload['created_at']) ? Carbon::parse($payload['created_at']) : now();
        $attrs['updated_at'] = $clientUpdatedAt;

        $project = Project::create($attrs);

        ProjectMember::firstOrCreate(
            ['project_id' => $project->id, 'user_id' => $user->id],
            ['role' => ProjectAccess::ROLE_SUPERVISOR, 'invited_by' => $user->id]
        );

        return ['id' => $id, 'entity' => 'project', 'status' => 'created'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function upsertChild(User $user, string $entity, string $id, array $payload, Carbon $clientUpdatedAt): array
    {
        $class = self::ENTITIES[$entity];
        $projectId = $payload['project_id'] ?? null;

        if ($entity === 'receipt') {
            $expense = Expense::find($payload['expense_id'] ?? null);
            abort_unless($expense, 422, 'expense_id required for receipt');
            $projectId = $expense->project_id;
        }

        abort_unless($projectId, 422, 'project_id required');
        ProjectAccess::ensureWrite($user, $projectId);

        /** @var \Illuminate\Database\Eloquent\Model|null $existing */
        $existing = $class::find($id);

        $attrs = $this->normalizePayload($entity, $payload);
        $attrs['id'] = $id;

        if ($existing) {
            if (isset($existing->updated_at) && $clientUpdatedAt->lt($existing->updated_at)) {
                return ['id' => $id, 'entity' => $entity, 'status' => 'conflict', 'server' => $existing];
            }
            $existing->fill($attrs);
            if ($existing->isFillable('updated_at') || array_key_exists('updated_at', $existing->getAttributes())) {
                $existing->updated_at = $clientUpdatedAt;
            }
            $existing->save();

            return ['id' => $id, 'entity' => $entity, 'status' => 'updated'];
        }

        if (! isset($attrs['created_at'])) {
            $attrs['created_at'] = now();
        }
        if ($this->modelHasUpdatedAt($class)) {
            $attrs['updated_at'] = $clientUpdatedAt;
        }

        $class::create($attrs);

        return ['id' => $id, 'entity' => $entity, 'status' => 'created'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function upsertTag(User $user, array $payload): array
    {
        $projectId = $payload['project_id'] ?? null;
        $tag = $payload['tag'] ?? null;
        abort_unless($projectId && $tag, 422, 'project_id and tag required');
        ProjectAccess::ensureWrite($user, $projectId);

        ProjectTag::firstOrCreate(['project_id' => $projectId, 'tag' => $tag]);

        return ['id' => $projectId.':'.$tag, 'entity' => 'project_tag', 'status' => 'created'];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function deleteEntity(User $user, string $entity, string $id, array $payload): array
    {
        if ($entity === 'project_tag') {
            $projectId = $payload['project_id'] ?? null;
            $tag = $payload['tag'] ?? null;
            abort_unless($projectId && $tag, 422, 'project_id and tag required');
            ProjectAccess::ensureWrite($user, $projectId);
            ProjectTag::where('project_id', $projectId)->where('tag', $tag)->delete();
            SyncTombstone::create([
                'entity_type' => 'project_tag',
                'entity_id' => $projectId.':'.$tag,
                'project_id' => $projectId,
                'deleted_at' => now(),
            ]);

            return ['id' => $id, 'entity' => $entity, 'status' => 'deleted'];
        }

        if ($entity === 'project') {
            $project = Project::find($id);
            if (! $project) {
                return ['id' => $id, 'entity' => $entity, 'status' => 'deleted'];
            }
            ProjectAccess::ensureWrite($user, $project);
            $projectId = $project->id;
            $project->delete();
            SyncTombstone::create([
                'entity_type' => 'project',
                'entity_id' => $id,
                'project_id' => $projectId,
                'deleted_at' => now(),
            ]);

            return ['id' => $id, 'entity' => $entity, 'status' => 'deleted'];
        }

        if (! isset(self::ENTITIES[$entity])) {
            return ['id' => $id, 'entity' => $entity, 'status' => 'error', 'message' => 'Unknown entity'];
        }

        $class = self::ENTITIES[$entity];
        $row = $class::find($id);
        if (! $row) {
            return ['id' => $id, 'entity' => $entity, 'status' => 'deleted'];
        }

        $projectId = $row->project_id ?? null;
        if ($entity === 'receipt') {
            $expense = Expense::find($row->expense_id);
            $projectId = $expense?->project_id;
        }
        abort_unless($projectId, 422, 'Cannot resolve project for delete');
        ProjectAccess::ensureWrite($user, $projectId);

        $row->delete();
        SyncTombstone::create([
            'entity_type' => $entity,
            'entity_id' => $id,
            'project_id' => $projectId,
            'deleted_at' => now(),
        ]);

        return ['id' => $id, 'entity' => $entity, 'status' => 'deleted'];
    }

    /**
     * @return array{cursor: string, changes: array<string, mixed>, tombstones: list<array<string, mixed>>}
     */
    public function pull(User $user, ?Carbon $since): array
    {
        $projectIds = ProjectMember::where('user_id', $user->id)->pluck('project_id');
        $cursor = now()->toIso8601String();

        $changes = [
            'projects' => $this->changed(Project::class, $projectIds, 'id', $since),
            'expenses' => $this->changed(Expense::class, $projectIds, 'project_id', $since),
            'tasks' => $this->changed(Task::class, $projectIds, 'project_id', $since),
            'inventory_items' => $this->changed(InventoryItem::class, $projectIds, 'project_id', $since),
            'material_usages' => $this->changedCreatedOnly(MaterialUsage::class, $projectIds, $since),
            'activity_logs' => $this->changed(ActivityLog::class, $projectIds, 'project_id', $since),
            'milestones' => $this->changed(ProjectMilestone::class, $projectIds, 'project_id', $since),
            'project_tags' => ProjectTag::whereIn('project_id', $projectIds)->get(),
            'receipts' => Receipt::query()
                ->whereIn('expense_id', Expense::whereIn('project_id', $projectIds)->select('id'))
                ->when($since, fn ($q) => $q->where('created_at', '>', $since))
                ->get(),
            'media' => $this->changed(Media::class, $projectIds, 'project_id', $since),
        ];

        $tombstones = SyncTombstone::query()
            ->where(function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds)->orWhereNull('project_id');
            })
            ->when($since, fn ($q) => $q->where('deleted_at', '>', $since))
            ->orderBy('deleted_at')
            ->get(['entity_type', 'entity_id', 'project_id', 'deleted_at']);

        return [
            'cursor' => $cursor,
            'changes' => $changes,
            'tombstones' => $tombstones,
        ];
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $class
     * @param  \Illuminate\Support\Collection<int, string>  $projectIds
     */
    protected function changed(string $class, $projectIds, string $projectColumn, ?Carbon $since)
    {
        $q = $class::query();
        if ($projectColumn === 'id') {
            $q->whereIn('id', $projectIds);
        } else {
            $q->whereIn($projectColumn, $projectIds);
        }
        if ($since) {
            $q->where('updated_at', '>', $since);
        }

        return $q->get();
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $class
     * @param  \Illuminate\Support\Collection<int, string>  $projectIds
     */
    protected function changedCreatedOnly(string $class, $projectIds, ?Carbon $since)
    {
        $q = $class::query()->whereIn('project_id', $projectIds);
        if ($since) {
            $q->where('created_at', '>', $since);
        }

        return $q->get();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function projectAttrs(array $payload): array
    {
        return array_filter([
            'owner_id' => $payload['owner_id'] ?? $payload['ownerId'] ?? null,
            'name' => $payload['name'] ?? null,
            'description' => $payload['description'] ?? '',
            'start_date' => isset($payload['start_date']) || isset($payload['startDate'])
                ? Carbon::parse($payload['start_date'] ?? $payload['startDate'])
                : null,
            'end_date' => isset($payload['end_date']) || isset($payload['endDate'])
                ? (($payload['end_date'] ?? $payload['endDate']) ? Carbon::parse($payload['end_date'] ?? $payload['endDate']) : null)
                : null,
            'budget' => $payload['budget'] ?? null,
            'spent' => $payload['spent'] ?? null,
            'status' => $payload['status'] ?? null,
            'category' => $payload['category'] ?? null,
            'progress' => $payload['progress'] ?? null,
            'image_url' => $payload['image_url'] ?? $payload['imageUrl'] ?? null,
        ], fn ($v) => $v !== null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function normalizePayload(string $entity, array $payload): array
    {
        $map = [];
        foreach ($payload as $key => $value) {
            $snake = Str::snake($key);
            $map[$snake] = $value;
        }

        foreach (['date', 'due_date', 'occurred_at', 'processed_at', 'completed_at', 'created_at', 'updated_at', 'start_date', 'end_date'] as $dt) {
            if (isset($map[$dt]) && $map[$dt]) {
                $map[$dt] = Carbon::parse($map[$dt]);
            }
        }

        return $map;
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $class
     */
    protected function modelHasUpdatedAt(string $class): bool
    {
        $model = new $class;

        return $model->usesTimestamps() || in_array('updated_at', $model->getFillable(), true);
    }
}
