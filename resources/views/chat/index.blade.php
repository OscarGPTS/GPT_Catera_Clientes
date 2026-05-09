<x-layouts.app title="Chat interno">
    <div class="mb-4">
        <h1 class="text-2xl font-semibold text-slate-900">Chat interno</h1>
        <p class="text-slate-600 text-sm mt-1">Canales por proyecto, departamento y dirección. Polling cada 5s. <span class="text-amber-700">Reverb realtime: TODO</span>.</p>
    </div>

    @livewire('chat.chat-panel', ['canalId' => $initialCanalId])
</x-layouts.app>
