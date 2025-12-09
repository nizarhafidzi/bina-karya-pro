<x-filament-panels::page>
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden dark:bg-gray-900 dark:border-gray-700">
        <div class="p-6">
            <h2 class="text-lg font-bold mb-4 text-gray-800 dark:text-gray-200">
                Tabel Data Kurva S: {{ $record->name }}
            </h2>

            @if(empty($curveData))
                <div class="p-4 text-center text-gray-500">
                    Belum ada data jadwal atau realisasi. Silakan input jadwal (Time Schedule) terlebih dahulu.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Minggu Ke</th>
                                <th scope="col" class="px-6 py-3 text-right">Rencana Mingguan</th>
                                <th scope="col" class="px-6 py-3 text-right">Rencana Kumulatif</th>
                                <th scope="col" class="px-6 py-3 text-right">Realisasi Kumulatif</th>
                                <th scope="col" class="px-6 py-3 text-right">Deviasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($curveData as $data)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        Minggu {{ $data['week'] }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        {{ $data['plan_weekly'] }}%
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-blue-600">
                                        {{ $data['plan_cumulative'] }}%
                                    </td>
                                    <td class="px-6 py-4 text-right font-bold text-green-600">
                                        {{ $data['actual_cumulative'] !== null ? $data['actual_cumulative'] . '%' : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        @if($data['deviation'] !== null)
                                            <span class="{{ $data['deviation'] < 0 ? 'text-red-600' : 'text-green-600' }} font-bold">
                                                {{ $data['deviation'] }}%
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>