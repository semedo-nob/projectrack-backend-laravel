<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\User;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_exports(): void
    {
        $this->get('/projects/proj_1/export/pdf')->assertRedirect(route('login'));
        $this->get('/projects/proj_1/export/csv/all')->assertRedirect(route('login'));
    }

    public function test_member_can_download_pdf_and_csv(): void
    {
        [$user, $project] = $this->memberAndProject();

        $pdf = $this->actingAs($user)->get(route('projects.export.pdf', $project));
        $pdf->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $pdf->headers->get('content-type')));
        $this->assertStringStartsWith('%PDF', $pdf->getContent());

        $csv = $this->actingAs($user)->get(route('projects.export.csv', [$project, 'all']));
        $csv->assertOk();
        $this->assertStringContainsString('csv', strtolower((string) $csv->headers->get('content-type')));
        $body = $csv->streamedContent();
        $this->assertStringContainsString('ProjectRack project export', $body);
        $this->assertStringContainsString($project->name, $body);
    }

    public function test_section_csv_downloads(): void
    {
        [$user, $project] = $this->memberAndProject();

        $this->actingAs($user)
            ->get(route('projects.export.csv', [$project, 'expenses']))
            ->assertOk();
    }

    public function test_non_member_cannot_export(): void
    {
        [, $project] = $this->memberAndProject();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get(route('projects.export.pdf', $project))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Project}
     */
    private function memberAndProject(): array
    {
        $user = User::factory()->create();
        $project = Project::create([
            'id' => 'proj_export_test',
            'owner_id' => $user->id,
            'name' => 'Export Test Site',
            'description' => 'A sample project',
            'start_date' => now()->subMonth(),
            'budget' => 100000,
            'spent' => 25000,
            'status' => 'Active',
            'category' => 'Construction',
            'progress' => 0.4,
        ]);
        ProjectMember::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'role' => ProjectAccess::ROLE_SUPERVISOR,
            'invited_by' => $user->id,
        ]);

        return [$user, $project];
    }
}
