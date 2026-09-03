@php
    $tabs = [
        'projects.show' => 'Overview',
        'projects.expenses' => 'Expenses',
        'projects.tasks' => 'Tasks',
        'projects.inventory' => 'Inventory',
        'projects.activities' => 'Activities',
        'projects.schedule' => 'Schedule',
        'projects.reports' => 'Reports',
    ];
    $sectionCsv = match (true) {
        request()->routeIs('projects.expenses') => ['expenses', 'Expenses CSV'],
        request()->routeIs('projects.tasks') => ['tasks', 'Tasks CSV'],
        request()->routeIs('projects.inventory') => ['inventory', 'Inventory CSV'],
        request()->routeIs('projects.activities') => ['activities', 'Activities CSV'],
        request()->routeIs('projects.schedule') => ['schedule', 'Schedule CSV'],
        default => null,
    };
@endphp
<nav class="tabs" aria-label="Project sections">
    @foreach ($tabs as $route => $label)
        <a href="{{ route($route, $project) }}" class="{{ request()->routeIs($route) ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
<div class="export-bar">
    <span class="section-label" style="margin:0">Download</span>
    <a class="btn btn-sm" href="{{ route('projects.export.pdf', $project) }}">PDF report</a>
    <a class="btn btn-sm btn-secondary" href="{{ route('projects.export.csv', [$project, 'all']) }}">Full CSV</a>
    @if ($sectionCsv)
        <a class="btn btn-sm btn-secondary" href="{{ route('projects.export.csv', [$project, $sectionCsv[0]]) }}">{{ $sectionCsv[1] }}</a>
    @endif
</div>
