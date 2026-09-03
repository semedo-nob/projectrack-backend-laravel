<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ProjectMember;
use App\Models\ProjectShare;
use App\Services\ProjectAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SharePageController extends Controller
{
    public function show(Request $request, string $token): View|RedirectResponse
    {
        $share = ProjectShare::with(['project', 'creator'])->where('token', $token)->firstOrFail();
        $valid = $share->isValid();

        if ($valid && $request->user()) {
            $this->grant($request, $share);

            return redirect()
                ->route('projects.show', $share->project_id)
                ->with('success', 'You now have access to this project.');
        }

        return view('shares.landing', compact('share', 'valid'));
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $share = ProjectShare::with('project')->where('token', $token)->firstOrFail();

        if (! $share->isValid()) {
            return redirect()->route('share.show', $token)
                ->with('error', 'This share link is invalid or expired.');
        }

        if (! $request->user()) {
            return redirect()->route('login', ['redirect' => '/p/'.$token]);
        }

        $this->grant($request, $share);

        return redirect()
            ->route('projects.show', $share->project_id)
            ->with('success', 'You now have access to this project.');
    }

    protected function grant(Request $request, ProjectShare $share): void
    {
        $user = $request->user();
        $member = ProjectMember::firstOrCreate(
            ['project_id' => $share->project_id, 'user_id' => $user->id],
            [
                'role' => $share->role,
                'invited_by' => $share->created_by,
            ]
        );

        if ($member->wasRecentlyCreated) {
            $share->increment('use_count');
        }

        if ($share->role === ProjectAccess::ROLE_OWNER) {
            $share->project->update(['owner_id' => $user->id]);
        }
    }
}
