<x-filament-panels::page>
    <div class="space-y-6">
        
        <div class="p-4 bg-white rounded-xl shadow border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Grafik Kurva S</h2>
                <span class="text-xs font-medium px-2.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                    Real-time
                </span>
            </div>
            <div class="relative h-80">
                <canvas id="scurveChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden dark:bg-gray-800 dark:border-gray-700">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-bold text-gray-800 dark:text-white">Tabel Data Kurva S</h2>
                <p class="text-sm text-gray-500">Rincian bobot rencana vs realisasi per minggu.</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                        <tr>
                            <th scope="col" class="px-4 py-3">Minggu</th>
                            <th scope="col" class="px-4 py-3 text-right">Plan Weekly</th>
                            <th scope="col" class="px-4 py-3 text-right">Plan Kumulatif</th>
                            <th scope="col" class="px-4 py-3 text-right">Actual Weekly</th>
                            <th scope="col" class="px-4 py-3 text-right">Actual Kumulatif</th>
                            <th scope="col" class="px-4 py-3 text-right">Deviasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($curveData as $row)
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                <td class="px-4 py-2 font-medium text-gray-900 dark:text-white">
                                    Minggu {{ $row['week'] }}
                                </td>
                                <td class="px-4 py-2 text-right">{{ number_format($row['plan_weekly'], 2) }}%</td>
                                <td class="px-4 py-2 text-right text-blue-600 font-semibold">{{ number_format($row['plan_cumulative'], 2) }}%</td>
                                <td class="px-4 py-2 text-right">{{ $row['actual_weekly'] !== null ? number_format($row['actual_weekly'], 2).'%' : '-' }}</td>
                                <td class="px-4 py-2 text-right text-green-600 font-semibold">{{ $row['actual_cumulative'] !== null ? number_format($row['actual_cumulative'], 2).'%' : '-' }}</td>
                                <td class="px-4 py-2 text-right">
                                    @if($row['deviation'] !== null)
                                        <span class="{{ $row['deviation'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ ($row['deviation'] > 0 ? '+' : '') . number_format($row['deviation'], 2) }}%
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    
                    <tfoot class="font-bold text-gray-900 bg-gray-50 dark:bg-gray-700 dark:text-white border-t border-gray-200">
                        <tr>
                            <td class="px-4 py-3">TOTAL AKHIR</td>
                            
                            <td class="px-4 py-3 text-right">
                                @php
                                    $lastRow = end($curveData);
                                    $totalPlan = $lastRow ? $lastRow['plan_cumulative'] : 0;
                                @endphp
                                {{ number_format($totalPlan, 2) }}%
                            </td>
                            
                            <td class="px-4 py-3 text-right">{{ number_format($totalPlan, 2) }}%</td>
                            
                            <td class="px-4 py-3 text-right">
                                @php
                                    $totalActual = $lastRow ? $lastRow['actual_cumulative'] : null;
                                @endphp
                                {{ $totalActual !== null ? number_format($totalActual, 2).'%' : '-' }}
                            </td>
                            
                            <td class="px-4 py-3 text-right">
                                {{ $totalActual !== null ? number_format($totalActual, 2).'%' : '-' }}
                            </td>
                            
                            <td class="px-4 py-3 text-right">
                                @if($totalActual !== null)
                                    @php $finalDev = $totalActual - $totalPlan; @endphp
                                    <span class="{{ $finalDev < 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ ($finalDev > 0 ? '+' : '') . number_format($finalDev, 2) }}%
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{ $this->table }}

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const ctx = document.getElementById('scurveChart');
            
            // FIX: Gunakan variabel $curveData yang dikirim dari PHP
            // Kita perlu memformat data agar sesuai struktur Chart.js
            const rawData = @json($curveData);

            if (ctx && rawData) {
                // Transform data PHP ke format Chart.js
                const labels = rawData.map(item => 'Mg ' + item.week);
                const planData = rawData.map(item => item.plan_cumulative);
                const actualData = rawData.map(item => item.actual_cumulative); // Bisa berisi null

                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Rencana (Plan)',
                                data: planData,
                                borderColor: '#3b82f6', // Biru
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4,
                                fill: true
                            },
                            {
                                label: 'Realisasi (Actual)',
                                data: actualData,
                                borderColor: '#22c55e', // Hijau
                                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                tension: 0.4,
                                fill: true,
                                spanGaps: false // Garis putus jika data null
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += context.parsed.y.toFixed(2) + '%';
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100, // Paksa mentok di 100%
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