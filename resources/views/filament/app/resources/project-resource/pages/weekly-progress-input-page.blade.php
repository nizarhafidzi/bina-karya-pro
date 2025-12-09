<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6">
        
        <div class="bg-white dark:bg-gray-900 p-6 rounded-xl shadow border border-gray-200 dark:border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pilih Minggu Ke</label>
                <div class="flex items-center gap-2">
                    <button wire:click="$set('currentWeek', {{ max(1, $currentWeek - 1) }})" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800">
                        &larr;
                    </button>
                    <input type="number" wire:model.live.debounce.500ms="currentWeek" class="w-20 text-center border-gray-300 rounded-md shadow-sm font-bold text-lg dark:bg-gray-800 dark:border-gray-600">
                    <button wire:click="$set('currentWeek', {{ $currentWeek + 1 }})" class="p-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800">
                        &rarr;
                    </button>
                </div>
            </div>
            
            <div class="text-right">
                <div class="text-sm text-gray-500">Periode Tanggal</div>
                <div class="font-bold text-lg">{{ $startDate }} <span class="text-gray-400 mx-1">s/d</span> {{ $endDate }}</div>
            </div>

            <button wire:click="submitOpname" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold shadow-lg transition flex items-center gap-2">
                <x-heroicon-o-check-circle class="w-5 h-5" />
                Submit Laporan
            </button>
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-xl shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-4 bg-gray-50 dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <h3 class="font-bold text-gray-700 dark:text-gray-200">Input Capaian Fisik (Realisasi)</h3>
                <p class="text-xs text-gray-500">Isi kolom "Minggu Ini" dengan persentase kemajuan yang dicapai minggu ini saja.</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th class="px-6 py-3 w-1/3">Item Pekerjaan</th>
                            <th class="px-6 py-3 text-center">Bobot Total</th>
                            <th class="px-6 py-3 text-center">Progress Lalu</th>
                            <th class="px-6 py-3 text-center w-32 bg-yellow-50 dark:bg-yellow-900/20 border-l border-r border-yellow-100 dark:border-yellow-900">
                                % Minggu Ini
                            </th>
                            <th class="px-6 py-3 text-center">Total Akumulasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($progressInputs as $itemId => $input)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50">
                                <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                    {{ $input['name'] }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    {{ number_format($input['weight'], 2) }}%
                                </td>
                                <td class="px-6 py-4 text-center text-gray-400">
                                    {{ $input['prev_cumulative'] }}%
                                </td>
                                <td class="px-6 py-4 bg-yellow-50 dark:bg-yellow-900/20 border-l border-r border-yellow-100 dark:border-yellow-900">
                                    <div class="flex items-center gap-1">
                                        <input type="number" step="0.01" min="0" max="{{ $input['max_input'] }}"
                                            wire:model.live="progressInputs.{{ $itemId }}.this_week"
                                            class="w-full text-center border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 font-bold text-green-700 dark:text-green-400">
                                        <span class="text-xs">%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-bold">
                                    {{ $input['prev_cumulative'] + (float)$input['this_week'] }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    Tidak ada item aktif atau semua pekerjaan telah selesai 100%.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>