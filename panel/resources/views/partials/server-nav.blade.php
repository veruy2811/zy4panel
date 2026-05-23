<div class="mb-5 overflow-x-auto border-b border-line">
    <nav class="flex min-w-max gap-1 text-sm">
        @foreach([
            'dashboard' => 'Dashboard',
            'console' => 'Console',
            'files' => 'Files',
            'databases' => 'Databases',
            'backups' => 'Backups',
            'network' => 'Network',
            'startup' => 'Startup',
            'settings' => 'Settings',
            'activity' => 'Activity',
        ] as $route => $label)
            <a href="{{ route('server.'.$route, $server) }}" class="border-b-2 px-3 py-3 {{ request()->routeIs('server.'.$route) ? 'border-neon text-neon' : 'border-transparent text-slate-400 hover:text-slate-100' }}">
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
