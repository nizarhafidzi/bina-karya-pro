<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-8">
        <div>
            <h3 class="text-lg font-bold text-gray-800">Rencana Belanja (Resource Plan)</h3>
            <p class="text-sm text-gray-500">Prediksi kebutuhan bahan & upah per minggu.</p>
        </div>

        <div class="flex items-center gap-3">
            <select wire:model.live="selectedWeek" 
                    class="rounded-lg border-gray-300 text-sm focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                @foreach($weekOptions as $val => $label)
                    <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>

            <button wire:click="recalculate" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white text-xs font-bold uppercase rounded-lg transition shadow-sm">
                <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span wire:loading class="mr-2">...</span>
                Hitung Ulang
            </button>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-1/3">
                        Resource / Bahan
                    </th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider w-24">
                        Satuan
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-blue-700 uppercase tracking-wider bg-blue-50/50">
                        System Qty
                        <span class="block text-[10px] font-normal text-blue-500 lowercase">(rekomendasi)</span>
                    </th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-yellow-700 uppercase tracking-wider bg-yellow-50/50">
                        Plan Qty
                        <span class="block text-[10px] font-normal text-yellow-600 lowercase">(final/order)</span>
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($plans as $plan)
                    <tr class="hover:bg-gray-50 transition group">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            {{ $plan->resource->name ?? 'Unknown' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 text-center">
                            {{ $plan->unit }}
                        </td>
                        
                        <td class="px-6 py-4 text-right text-sm text-blue-700 font-mono bg-blue-50/20 group-hover:bg-blue-50/40">
                            {{ number_format($plan->system_qty, 4) }}
                        </td>

                        <td class="px-6 py-2 text-right bg-yellow-50/20 group-hover:bg-yellow-50/40">
                            <input type="number" 
                                   step="0.0001" 
                                   wire:change="updateQty({{ $plan->id }}, $event.target.value)"
                                   value="{{ $plan->adjusted_qty }}"
                                   placeholder="{{ number_format($plan->system_qty, 4) }}"
                                   class="block w-full text-right text-sm border-gray-300 rounded-md focus:ring-yellow-500 focus:border-yellow-500 shadow-sm placeholder-gray-400">
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                </svg>
                                <p class="text-base font-medium">Tidak ada kebutuhan material minggu ini.</p>
                                <p class="text-sm mt-1">Pastikan jadwal sudah diisi dan tombol "Hitung Ulang" ditekan.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>