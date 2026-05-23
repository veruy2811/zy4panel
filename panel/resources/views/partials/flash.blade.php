@if(session('status'))
    <div class="mb-4 rounded-lg border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-100">
        <div class="font-semibold">Ada yang perlu diperbaiki:</div>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
