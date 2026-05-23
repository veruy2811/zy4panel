<div class="flex flex-wrap gap-2">
    @foreach(['start' => 'Start', 'stop' => 'Stop', 'restart' => 'Restart', 'kill' => 'Kill'] as $action => $label)
        <form method="POST" action="{{ route('server.power', [$server, $action]) }}">
            @csrf
            <button class="{{ $action === 'kill' ? 'zy-btn-danger' : 'zy-btn-secondary' }}">{{ $label }}</button>
        </form>
    @endforeach
</div>
