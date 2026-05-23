@extends('layouts.app')

@section('content')
@include('partials.server-nav')

<div class="mb-4 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="text-2xl font-bold">Console</h1>
        <p class="font-mono text-xs text-slate-500">{{ $server->uuid }}</p>
    </div>
    @include('partials.power-buttons')
</div>

<div x-data="consoleClient('{{ $wsUrl }}')" x-init="connect()" class="zy-card overflow-hidden">
    <div class="flex items-center justify-between border-b border-line px-4 py-3">
        <span class="font-mono text-sm" :class="connected ? 'text-emerald-300' : 'text-rose-300'" x-text="connected ? 'connected' : 'disconnected'"></span>
        <button class="zy-btn-secondary py-1" @click="lines=[]">Clear</button>
    </div>
    <div x-ref="log" class="h-[520px] overflow-y-auto bg-black p-4 font-mono text-sm leading-6 text-slate-200">
        <template x-for="(line, index) in lines" :key="index">
            <div x-text="line"></div>
        </template>
    </div>
    <form class="flex gap-2 border-t border-line bg-panel p-3" @submit.prevent="send()">
        <input class="zy-input font-mono" x-model="command" placeholder="Ketik command lalu Enter">
        <button class="zy-btn-primary">Send</button>
    </form>
</div>

<script>
function consoleClient(url) {
    return {
        socket: null,
        connected: false,
        command: '',
        lines: [],
        connect() {
            this.socket = new WebSocket(url);
            this.socket.onopen = () => { this.connected = true; };
            this.socket.onclose = () => { this.connected = false; setTimeout(() => this.connect(), 3000); };
            this.socket.onerror = () => { this.connected = false; };
            this.socket.onmessage = (event) => {
                this.lines.push(event.data);
                if (this.lines.length > 1200) this.lines.shift();
                this.$nextTick(() => { this.$refs.log.scrollTop = this.$refs.log.scrollHeight; });
            };
        },
        send() {
            if (!this.command.trim() || !this.connected) return;
            this.socket.send(JSON.stringify({type: 'command', data: this.command}));
            this.command = '';
        }
    }
}
</script>
@endsection
