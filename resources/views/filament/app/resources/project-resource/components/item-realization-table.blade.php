<div class="overflow-x-auto border border-gray-200 rounded-lg">
    <table class="w-full text-sm text-left text-gray-500">
        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3">Item Pekerjaan</th>
                <th scope="col" class="px-6 py-3 text-center">Bobot Item</th>
                <th scope="col" class="px-6 py-3 text-right">Realisasi (%)</th>
                <th scope="col" class="px-6 py-3 text-right">Kontribusi (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                @php
                    $weight = $item->rabItem->weight ?? 0;
                    $realization = $item->progress_this_week;
                    $contribution = ($realization * $weight) / 100;
                @endphp
                <tr class="bg-white border-b hover:bg-gray-50">
                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        {{ $item->rabItem->ahsMaster->name ?? 'Item #' . $item->rab_item_id }}
                        <div class="text-xs text-gray-400 font-normal">
                            Vol: {{ $item->rabItem->qty }} {{ $item->rabItem->unit }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        {{ number_format($weight, 2) }}%
                    </td>
                    <td class="px-6 py-4 text-right font-bold text-blue-600">
                        {{ number_format($realization, 2) }}%
                    </td>
                    <td class="px-6 py-4 text-right font-bold text-green-600">
                        +{{ number_format($contribution, 3) }}%
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="px-6 py-4 text-center text-gray-400 italic">
                        Tidak ada item yang dikerjakan minggu ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>