<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Project;
use App\Services\ProjectAccess;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $projects = $request->user()
            ->projects()
            ->withCount(['expenses', 'tasks', 'inventoryItems'])
            ->orderByDesc('updated_at')
            ->get();

        return view('dashboard.index', compact('projects'));
    }
}
