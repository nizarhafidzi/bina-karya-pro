<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col justify-between">
        <div>
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider">Nilai Kontrak</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1">
                Rp {{ number_format($stats['contract_value'], 0, ',', '.') }}
            </h3>
        </div>
        <div class="mt-6 pt-6 border-t border-gray-50">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm text-gray-600">Total Anda Bayar</span>
                <span class="text-sm font-bold text-green-600">
                    Rp {{ number_format($stats['total_paid'], 0, ',', '.') }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-green-500 h-2.5 rounded-full" style="width: {{ $stats['paid_percentage'] }}%"></div>
            </div>
            <p class="text-xs text-right text-gray-400 mt-1">{{ number_format($stats['paid_percentage'], 1) }}% Lunas</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <p class="text-sm text-gray-500 font-medium uppercase tracking-wider mb-4">Status Pembayaran</p>
        
        <div class="flex items-center justify-center mb-4">
            @if($stats['health_status'] === 'healthy')
                <div class="h-16 w-16 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            @elseif($stats['health_status'] === 'overpaid')
                <div class="h-16 w-16 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            @else
                <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
            @endif
        </div>

        <h4 class="text-center text-lg font-bold text-gray-800">
            @if($stats['health_status'] === 'healthy') Sehat & Aman
            @elseif($stats['health_status'] === 'overpaid') Perhatian (Overpaid)
            @else Pembayaran Tertunda
            @endif
        </h4>
        <p class="text-center text-sm text-gray-500 mt-2 px-4">{{ $stats['health_message'] }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex flex-col items-center">
        <p class="text-sm text-gray-500 font-medium uppercase tracking-wider mb-2">Komposisi Kontrak</p>
        <div id="paymentDonutChart" class="w-full h-40"></div>
    </div>

</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const stats = @json($stats);
        
        const options = {
            series: [stats.total_paid, stats.remaining_contract],
            labels: ['Sudah Dibayar', 'Sisa Kontrak'],
            chart: { type: 'donut', height: 180 },
            colors: ['#22c55e', '#e5e7eb'], // Green & Gray
            dataLabels: { enabled: false },
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Progress Fisik',
                                formatter: () => stats.physical_progress + '%'
                            }
                        }
                    }
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#paymentDonutChart"), options);
        chart.render();
    });
</script>