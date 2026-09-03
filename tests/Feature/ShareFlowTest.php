<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectShare;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShareFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_share_landing_then_register_opens_project(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = $this->projectOwnedBy($supervisor);
        $share = ProjectShare::create([
            'token' => str_repeat('a', 48),
            'project_id' => $project->id,
            'created_by' => $supervisor->id,
            'role' => ProjectAccess::ROLE_OWNER,
        ]);

        $this->get('/p/'.$share->token)
            ->assertOk()
            ->assertSee($project->name)
            ->assertSee('Register as owner');

        $this->from('/p/'.$share->token)
            ->post('/register', [
                'name' => 'Site Owner',
                'email' => 'owner@example.com',
                'password' => 'secret12',
                'password_confirmation' => 'secret12',
                'role' => 'owner',
                'redirect' => '/p/'.$share->token,
            ])
            ->assertRedirect('/p/'.$share->token);

        $this->get('/p/'.$share->token)
            ->assertRedirect(route('projects.show', $project->id));

        $this->get(route('projects.show', $project->id))
            ->assertOk()
            ->assertSee($project->name);

        $this->assertDatabaseHas('project_members', [
            'project_id' => $project->id,
            'role' => ProjectAccess::ROLE_OWNER,
        ]);
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'owner_id' => User::where('email', 'owner@example.com')->value('id'),
        ]);
    }

    public function test_supervisor_api_creates_share_url(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $project = $this->projectOwnedBy($supervisor);

        Sanctum::actingAs($supervisor);
        $res = $this->postJson('/api/v1/projects/'.$project->id.'/shares', [
            'role' => 'owner',
        ]);

        $res->assertCreated()
            ->assertJsonPath('data.role', 'owner');
        $this->assertStringContainsString('/p/', (string) $res->json('url'));
    }

    private function projectOwnedBy(User $user): Project
    {
        $project = Project::create([
            'id' => 'proj_share_test',
            'owner_id' => $user->id,
            'name' => 'Harbor Clinic',
            'description' => 'Field site',
            'start_date' => now()->subWeek(),
            'budget' => 50000,
            'spent' => 12000,
            'status' => 'Active',
            'category' => 'Construction',
            'progress' => 0.3,
        ]);
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectAccess::ROLE_SUPERVISOR,
            'invited_by' => $user->id,
        ]);

        return $project;
    }
}
