<x-filament-panels::page>
    <div class="space-y-6">
        
        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
            <h2 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">Grafik Kurva S</h2>
            <div class="relative h-80">
                <canvas id="scurveChart"></canvas>
            </div>
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
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
                    @php
                        // Ambil data terakhir yang punya realisasi untuk Footer
                        $lastActual = collect($curveData)->last(fn($row) => $row['actual_cumulative'] !== null);
                    @endphp

                    @forelse($curveData as $data)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                Minggu {{ $data['week'] }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                {{ number_format($data['plan_weekly'], 2) }}%
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-blue-600">
                                {{ number_format($data['plan_cumulative'], 2) }}%
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-green-600">
                                @if($data['actual_cumulative'] !== null)
                                    {{ number_format($data['actual_cumulative'], 2) }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($data['deviation'] !== null)
                                    <span class="{{ $data['deviation'] < 0 ? 'text-red-600' : 'text-green-600' }} font-bold">
                                        {{ $data['deviation'] > 0 ? '+' : '' }}{{ number_format($data['deviation'], 2) }}%
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center">Belum ada jadwal proyek.</td>
                        </tr>
                    @endforelse
                </tbody>

                @if(count($curveData) > 0)
                <tfoot class="text-xs text-gray-700 uppercase bg-gray-100 dark:bg-gray-700 dark:text-gray-200 font-bold">
                    <tr>
                        <td class="px-6 py-4">SUB TOTAL</td>
                        
                        <td class="px-6 py-4 text-right">
                            {{ number_format(collect($curveData)->sum('plan_weekly'), 2) }}%
                        </td>
                        
                        <td class="px-6 py-4 text-right">
                            100.00%
                        </td>

                        <td class="px-6 py-4 text-right text-green-700">
                            @if($lastActual)
                                {{ number_format($lastActual['actual_cumulative'], 2) }}%
                            @else
                                0.00%
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            @if($lastActual)
                                <span class="{{ $lastActual['deviation'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ $lastActual['deviation'] > 0 ? '+' : '' }}{{ number_format($lastActual['deviation'], 2) }}%
                                </span>
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const ctx = document.getElementById('scurveChart');
            
            // Ambil data dari Property Livewire ($chartDataset)
            const chartData = @json($chartDataset);

            if (ctx && chartData) {
                new Chart(ctx, {
                    type: 'line',
                    data: chartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + '%';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100,
                                ticks: {
                                    callback: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-filament-panels::page>