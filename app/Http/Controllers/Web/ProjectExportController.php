<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\ProjectAccess;
use App\Services\ProjectExportService;
use Illuminate\Http\Request;

class ProjectExportController extends Controller
{
    public function pdf(Request $request, string $project, ProjectExportService $export)
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureView($request->user(), $model);

        return $export->downloadPdf($model);
    }

    public function csv(Request $request, string $project, ProjectExportService $export, string $kind = 'all')
    {
        $model = Project::findOrFail($project);
        ProjectAccess::ensureView($request->user(), $model);

        abort_unless(in_array($kind, ProjectExportService::KINDS, true), 404);

        return $export->downloadCsv($model, $kind);
    }
}
