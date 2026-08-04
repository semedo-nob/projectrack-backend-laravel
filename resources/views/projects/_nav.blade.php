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
@endphp
<nav class="tabs">
    @foreach ($tabs as $route => $label)
        <a href="{{ route($route, $project) }}" class="{{ request()->routeIs($route) ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</nav>
