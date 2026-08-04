<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
use App\Services\ProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $projects = $request->user()->projects()->with('tags')->orderByDesc('updated_at')->get();

        return response()->json(['data' => $projects]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'id' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric'],
            'spent' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'progress' => ['nullable', 'numeric'],
            'image_url' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:100'],
        ]);

        $id = $data['id'] ?? ('proj_'.Str::lower(Str::ulid()));

        $project = Project::create([
            'id' => $id,
            'owner_id' => $request->user()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'budget' => $data['budget'] ?? 0,
            'spent' => $data['spent'] ?? 0,
            'status' => $data['status'] ?? 'Planning',
            'category' => $data['category'] ?? 'General',
            'progress' => $data['progress'] ?? 0,
            'image_url' => $data['image_url'] ?? null,
        ]);

        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'role' => ProjectAccess::ROLE_SUPERVISOR,
            'invited_by' => $request->user()->id,
        ]);

        foreach ($data['tags'] ?? [] as $tag) {
            ProjectTag::firstOrCreate(['project_id' => $project->id, 'tag' => $tag]);
        }

        return response()->json(['data' => $project->load('tags')], 201);
    }

    public function show(Request $request, string $project): JsonResponse
    {
        $model = Project::with([
            'tags',
            'members.user',
            'expenses',
            'tasks',
            'inventoryItems',
            'activityLogs',
            'milestones',
        ])->findOrFail($project);

        ProjectAccess::ensureView($request->user(), $model);

        return response()->json(['data' => $model]);
    }

    public function update(Request $request, string $project): JsonResponse
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureWrite($request->user(), $model);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'budget' => ['nullable', 'numeric'],
            'spent' => ['nullable', 'numeric'],
            'status' => ['nullable', 'string'],
            'category' => ['nullable', 'string'],
            'progress' => ['nullable', 'numeric'],
            'image_url' => ['nullable', 'string'],
        ]);

        $model->update($data);

        return response()->json(['data' => $model->fresh('tags')]);
    }

    public function destroy(Request $request, string $project): JsonResponse
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureWrite($request->user(), $model);

        SyncTombstone::create([
            'entity_type' => 'project',
            'entity_id' => $model->id,
            'project_id' => $model->id,
            'deleted_at' => now(),
        ]);

        $model->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function uploadMedia(Request $request, string $project): JsonResponse
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureWrite($request->user(), $model);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:12288'],
            'entity_type' => ['nullable', 'string', 'max:50'],
            'entity_id' => ['nullable', 'string', 'max:50'],
            'id' => ['nullable', 'string', 'max:50'],
        ]);

        $path = $request->file('file')->store('projects/'.$model->id, 'public');
        $media = Media::create([
            'id' => $data['id'] ?? ('media_'.Str::lower(Str::ulid())),
            'project_id' => $model->id,
            'entity_type' => $data['entity_type'] ?? null,
            'entity_id' => $data['entity_id'] ?? null,
            'path' => $path,
            'disk' => 'public',
            'mime' => $request->file('file')->getMimeType(),
            'checksum' => hash_file('sha256', $request->file('file')->getRealPath()),
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $media,
            'url' => Storage::disk('public')->url($path),
        ], 201);
    }
}
