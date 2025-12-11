<div class="space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
            <p class="text-sm text-gray-500">Kode Proyek: {{ $project->code }}</p>
        </div>
        
        @php
            $userRoles = auth()->user()->roles->pluck('name')->toArray();
        @endphp

        @if(in_array('Site Manager', $userRoles) || in_array('Tenant Admin', $userRoles))
            <div class="flex gap-2">
                <a href="{{ route('project.daily-report', $project) }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium rounded-lg shadow-sm transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Daily
                </a>

                <a href="{{ route('project.weekly-progress', $project) }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-secondary hover:bg-green-600 text-white text-sm font-medium rounded-lg shadow transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Opname Mingguan
                </a>
            </div>
        @endif
    </div>

    @livewire('project.owner-financial-stats', ['project' => $project])

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Nilai Kontrak</p>
            <p class="text-lg font-bold text-gray-900 mt-1">Rp {{ number_format($summary['contract_value'], 0, ',', '.') }}</p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Progress Realisasi</p>
            <div class="flex items-end gap-2">
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($summary['current_progress'], 2) }}%</p>
                <span class="text-xs text-gray-400 mb-1">s/d hari ini</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Deviasi (Rencana vs Actual)</p>
            <p class="text-lg font-bold mt-1 {{ $summary['deviation'] < 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $summary['deviation'] > 0 ? '+' : '' }}{{ number_format($summary['deviation'], 2) }}%
            </p>
            <p class="text-xs text-gray-400">
                {{ $summary['deviation'] < 0 ? 'Terlambat' : 'Ahead of Schedule' }}
            </p>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs text-gray-500 uppercase font-semibold">Total Durasi</p>
            <p class="text-lg font-bold text-gray-900 mt-1">{{ $summary['duration_weeks'] }} Minggu</p>
        </div>
    </div>

    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <h3 class="text-lg font-bold text-gray-800 mb-4">Kurva S (Rencana vs Realisasi)</h3>
        <div id="scurve-chart" style="min-height: 400px;"></div>
    </div>

</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        const data = @json($chartData);

        const options = {
            series: [{
                name: 'Rencana (Plan)',
                data: data.plan
            }, {
                name: 'Realisasi (Actual)',
                data: data.actual
            }],
            chart: {
                height: 400,
                type: 'line',
                zoom: { enabled: false },
                toolbar: { show: false }
            },
            colors: ['#3b82f6', '#22c55e'], // Blue for Plan, Green for Actual
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            grid: {
                row: {
                    colors: ['#f3f3f3', 'transparent'], 
                    opacity: 0.5
                },
            },
            xaxis: {
                categories: data.weeks,
            },
            yaxis: {
                min: 0,
                max: 100,
                tickAmount: 5,
                labels: {
                    formatter: (value) => value.toFixed(0) + '%'
                }
            },
            legend: {
                position: 'top'
            }
        };

        const chart = new ApexCharts(document.querySelector("#scurve-chart"), options);
        chart.render();
    });
</script>