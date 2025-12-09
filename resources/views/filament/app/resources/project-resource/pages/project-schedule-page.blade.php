<x-filament-panels::page>
    <div class="space-y-6">
        
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-700">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white">Planning Jadwal</h2>
                    <p class="text-sm text-gray-500">Masukkan minggu mulai & selesai. Sistem otomatis membagi rata bobot pekerjaan.</p>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" wire:click="$toggle('showDetails')" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-800">
                        {{ $showDetails ? 'Sembunyikan Detail Mingguan' : 'Tampilkan Detail Mingguan' }}
                    </button>
                    <button wire:click="saveSchedule" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-bold shadow transition flex items-center gap-2">
                        <x-heroicon-o-check class="w-5 h-5" />
                        Simpan
                    </button>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($scheduleInputs as $itemId => $input)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 flex flex-col gap-4">
                        
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 dark:text-white text-md">{{ $input['name'] }}</h3>
                                <div class="text-xs text-gray-500 mt-1">Bobot: <span class="font-bold text-blue-600">{{ number_format($input['weight'], 2) }}%</span></div>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 mb-1">Mulai Minggu Ke</label>
                                    <input type="number" min="1" 
                                        wire:model.live.debounce.500ms="scheduleInputs.{{ $itemId }}.start_week"
                                        class="w-32 text-center border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 font-bold text-lg h-10">
                                </div>
                                <span class="text-gray-400 mt-5">s/d</span>
                                <div class="flex flex-col">
                                    <label class="text-xs font-bold text-gray-500 mb-1">Selesai Minggu Ke</label>
                                    <input type="number" min="1" 
                                        wire:model.live.debounce.500ms="scheduleInputs.{{ $itemId }}.end_week"
                                        class="w-32 text-center border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 font-bold text-lg h-10">
                                </div>
                            </div>

                            <div class="text-right min-w-[120px]">
                                @php $dur = $input['end_week'] - $input['start_week'] + 1; @endphp
                                <div class="text-xs text-gray-400">Durasi</div>
                                <div class="font-bold text-gray-700 dark:text-gray-300">{{ $dur }} Minggu</div>
                                <div class="text-[10px] text-green-600">Avg: {{ number_format(100/$dur, 1) }}% / minggu</div>
                            </div>
                        </div>

                        @if($showDetails)
                            <div class="mt-2 pt-4 border-t border-gray-200 dark:border-gray-700 animate-fadeIn">
                                <label class="text-xs font-bold text-gray-400 mb-2 block">Detail Distribusi Per Minggu (Edit jika perlu Kurva-S manual)</label>
                                <div class="grid grid-cols-6 md:grid-cols-12 gap-2">
                                    @foreach($input['weekly_distribution'] as $idx => $val)
                                        <div class="relative">
                                            <span class="text-[9px] text-gray-400 absolute top-[-15px] left-0 w-full text-center">Mgg {{ $input['start_week'] + $idx }}</span>
                                            <input type="number" step="0.01"
                                                wire:model.live="scheduleInputs.{{ $itemId }}.weekly_distribution.{{ $idx }}"
                                                class="w-full text-center text-xs border-gray-300 rounded shadow-sm py-1 px-0">
                                        </div>
                                    @endforeach
                                </div>
                                <div class="text-right mt-1 text-xs {{ abs($input['total_check'] - 100) > 0.1 ? 'text-red-500' : 'text-green-500' }}">
                                    Total: {{ number_format($input['total_check'], 2) }}%
                                </div>
                            </div>
                        @endif

                    </div>
                @endforeach
            </div>
        </div>

    </div>
</x-filament-panels::page>