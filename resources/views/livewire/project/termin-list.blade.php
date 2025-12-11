<div class="space-y-4">
    <h3 class="text-lg font-bold text-gray-800">Status Pembayaran & Termin</h3>
    
    <div class="grid gap-3">
        @forelse($termins as $termin)
            <div class="p-4 rounded-lg border {{ $termin->status === 'submitted' ? 'bg-blue-50 border-blue-200 ring-2 ring-blue-100' : 'bg-white border-gray-100' }} shadow-sm flex flex-col md:flex-row justify-between items-center transition hover:shadow-md">
                
                <div class="flex items-center gap-4 mb-2 md:mb-0">
                    <div class="p-2 rounded-full {{ $termin->status === 'paid' ? 'bg-green-100 text-green-600' : ($termin->status === 'submitted' ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-400') }}">
                        @if($termin->status === 'paid')
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                        @elseif($termin->status === 'submitted')
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @else
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        @endif
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-900">{{ $termin->name }}</h4>
                        <p class="text-xs text-gray-500">Syarat: Progress {{ $termin->target_progress }}%</p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-lg font-bold text-gray-800">Rp {{ number_format($termin->nominal_value, 0, ',', '.') }}</p>
                    
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                        {{ $termin->status === 'submitted' ? 'bg-blue-100 text-blue-800' : 
                           ($termin->status === 'paid' ? 'bg-green-100 text-green-800' : 
                           ($termin->status === 'ready' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                        @if($termin->status === 'submitted')
                            MENUNGGU PEMBAYARAN ANDA
                        @elseif($termin->status === 'paid')
                            LUNAS
                        @elseif($termin->status === 'ready')
                            SIAP DITAGIH (Menunggu Admin)
                        @else
                            TERJADWAL
                        @endif
                    </span>
                </div>
            </div>
        @empty
            <div class="text-center py-6 text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                Belum ada jadwal termin.
            </div>
        @endforelse
    </div>
</div>