<x-filament-panels::page>
    <div class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-white rounded-xl shadow border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Pemasukan</p>
                <p class="text-xl font-bold text-green-600">Rp {{ number_format($summaryData['total_income'], 0, ',', '.') }}</p>
            </div>
            <div class="p-4 bg-white rounded-xl shadow border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Pengeluaran</p>
                <p class="text-xl font-bold text-red-600">Rp {{ number_format($summaryData['total_expense'], 0, ',', '.') }}</p>
            </div>
            <div class="p-4 bg-white rounded-xl shadow border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
                <p class="text-sm text-gray-500 dark:text-gray-400">Sisa Kas</p>
                <p class="text-xl font-bold {{ $summaryData['current_balance'] < 0 ? 'text-red-600' : 'text-blue-600' }}">
                    Rp {{ number_format($summaryData['current_balance'], 0, ',', '.') }}
                </p>
            </div>
        </div>

        <div class="p-6 bg-white rounded-xl shadow border border-gray-100 dark:bg-gray-800 dark:border-gray-700">
            <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-white">Arus Kas Bulanan (Actual)</h2>
            <div class="relative h-80">
                <canvas id="cashFlowChart"></canvas>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const ctx = document.getElementById('cashFlowChart');
            const data = @json($chartData);

            new Chart(ctx, {
                type: 'bar',
                data: data,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</x-filament-panels::page>