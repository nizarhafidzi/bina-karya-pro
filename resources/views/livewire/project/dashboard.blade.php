<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
            <p class="text-sm text-gray-500">Kode Proyek: {{ $project->code }}</p>
        </div>
        
        @php
            $userRoles = auth()->user()->roles->pluck('name')->toArray();
        @endphp

        @if(in_array('Site Manager', $userRoles) || in_array('Tenant Admin', $userRoles) || in_array('Super Admin', $userRoles))
            <div class="flex gap-2">
                <a href="{{ route('project.daily-input', $project) }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg shadow-sm transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Daily Report
                </a>

                <a href="{{ route('project.weekly-progress', $project) }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg shadow transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Opname Mingguan
                </a>
            </div>
        @endif
    </div>

    @livewire('project.owner-financial-stats', ['project' => $project])

    <div class="mt-8">
        @livewire('project.termin-list', ['project' => $project])
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Nilai Kontrak</p>
            <p class="text-lg font-bold text-gray-900 mt-1">Rp {{ number_format($summary['contract_value'], 0, ',', '.') }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Progress Fisik (Actual)</p>
            <div class="flex items-end gap-2">
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($summary['current_progress'], 2) }}%</p>
                <span class="text-xs text-gray-400 mb-1">Kumulatif Terkini</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Deviasi (Plan vs Actual)</p>
            <div class="flex items-end gap-2 mt-1">
                <p class="text-lg font-bold {{ $summary['deviation'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                    {{ $summary['deviation'] > 0 ? '+' : '' }}{{ number_format($summary['deviation'], 2) }}%
                </p>
            </div>
            <p class="text-xs text-gray-400 mt-1">
                {{ $summary['deviation'] < 0 ? 'Terlambat dari Jadwal' : ($summary['deviation'] > 0 ? 'Lebih Cepat (Ahead)' : 'On Track') }}
            </p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Total Durasi Proyek</p>
            <p class="text-lg font-bold text-gray-900 mt-1">{{ $summary['duration_weeks'] }} Minggu</p>
        </div>
    </div>

    <div class="mt-4">
        @livewire('project.project-gantt-chart', ['project' => $project])
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Kurva S Proyek</h3>
            <span class="px-2 py-1 bg-gray-100 text-xs text-gray-500 rounded">Real-time Update</span>
        </div>
        <div id="scurve-chart" style="min-height: 400px;"></div>
    </div>

    <div class="mt-12">
        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            Galeri Proyek & Logbook
        </h3>
        
        @livewire('project.project-gallery', ['project' => $project])
    </div>

</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const data = @json($chartData);

        const options = {
            series: [
                {
                    name: 'Rencana (Plan)',
                    data: data.plan
                }, 
                {
                    name: 'Realisasi (Actual)',
                    data: data.actual
                }
            ],
            chart: {
                height: 400,
                type: 'line',
                zoom: { enabled: false },
                toolbar: { show: false },
                fontFamily: 'inherit'
            },
            colors: ['#3b82f6', '#22c55e'], // Plan (Blue), Actual (Green)
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 3,
                dashArray: [0, 0] 
            },
            markers: {
                size: 4,
                hover: { size: 6 }
            },
            grid: {
                borderColor: '#f3f4f6',
                strokeDashArray: 4,
                xaxis: {
                    lines: { show: true }
                }
            },
            xaxis: {
                categories: data.weeks,
                labels: {
                    style: { colors: '#9ca3af' }
                },
                tooltip: { enabled: false }
            },
            yaxis: {
                min: 0,
                max: 100, // Fix skala 0-100%
                tickAmount: 5,
                labels: {
                    formatter: (value) => value.toFixed(0) + '%',
                    style: { colors: '#9ca3af' }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val !== null ? val + "%" : "Belum ada data";
                    }
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#scurve-chart"), options);
        chart.render();
    });
</script>