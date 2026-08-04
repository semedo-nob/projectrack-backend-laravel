<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectShare;
use App\Services\ProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShareController extends Controller
{
    public function store(Request $request, string $project): JsonResponse
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureWrite($request->user(), $model);

        $data = $request->validate([
            'role' => ['nullable', 'in:owner,viewer'],
            'expires_at' => ['nullable', 'date'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
        ]);

        $token = Str::random(48);
        $share = ProjectShare::create([
            'token' => $token,
            'project_id' => $model->id,
            'created_by' => $request->user()->id,
            'role' => $data['role'] ?? ProjectAccess::ROLE_OWNER,
            'expires_at' => $data['expires_at'] ?? null,
            'max_uses' => $data['max_uses'] ?? null,
        ]);

        $url = url('/p/'.$token);

        return response()->json([
            'data' => $share,
            'url' => $url,
        ], 201);
    }

    public function index(Request $request, string $project): JsonResponse
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureView($request->user(), $model);

        $shares = ProjectShare::where('project_id', $model->id)
            ->whereNull('revoked_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $shares]);
    }

    public function destroy(Request $request, string $project, string $share): JsonResponse
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureWrite($request->user(), $model);

        $row = ProjectShare::where('project_id', $model->id)->findOrFail($share);
        $row->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Share revoked']);
    }

    public function showPublic(string $token): JsonResponse
    {
        $share = ProjectShare::with(['project', 'creator'])->where('token', $token)->firstOrFail();

        if (! $share->isValid()) {
            return response()->json(['message' => 'Share link is invalid or expired'], 410);
        }

        return response()->json([
            'data' => [
                'token' => $share->token,
                'role' => $share->role,
                'project' => [
                    'id' => $share->project->id,
                    'name' => $share->project->name,
                    'description' => $share->project->description,
                    'status' => $share->project->status,
                    'category' => $share->project->category,
                    'budget' => $share->project->budget,
                    'spent' => $share->project->spent,
                    'progress' => $share->project->progress,
                ],
                'inviter' => [
                    'name' => $share->creator?->name,
                    'email' => $share->creator?->email,
                ],
                'expires_at' => $share->expires_at?->toIso8601String(),
            ],
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $share = ProjectShare::with('project')->where('token', $token)->firstOrFail();

        if (! $share->isValid()) {
            return response()->json(['message' => 'Share link is invalid or expired'], 410);
        }

        $user = $request->user();

        ProjectMember::updateOrCreate(
            ['project_id' => $share->project_id, 'user_id' => $user->id],
            [
                'role' => $share->role,
                'invited_by' => $share->created_by,
            ]
        );

        $share->increment('use_count');

        // Prefer owner_id on project when accepting as owner
        if ($share->role === ProjectAccess::ROLE_OWNER && ! $share->project->owner_id) {
            $share->project->update(['owner_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Access granted',
            'project_id' => $share->project_id,
            'role' => $share->role,
        ]);
    }
}
