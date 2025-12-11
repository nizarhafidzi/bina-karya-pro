<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="p-4 bg-white rounded-lg shadow border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <span class="text-sm text-gray-500">Nilai Kontrak</span>
            <div class="text-lg font-bold">Rp {{ number_format($record->contract_value, 0, ',', '.') }}</div>
        </div>
        <div class="p-4 bg-white rounded-lg shadow border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
            <span class="text-sm text-gray-500">Progress Fisik Saat Ini</span>
            @php
                $currentProgress = \App\Models\WeeklyRealization::where('project_id', $record->id)
                    ->where('status', 'submitted')
                    ->orderByDesc('week')
                    ->sum('realized_progress') ?? 0;
            @endphp
            <div class="text-lg font-bold text-blue-600">{{ number_format($currentProgress, 2) }}%</div>
        </div>
    </div>

    {{ $this->table }}
</x-filament-panels::page>