<x-filament-panels::page>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $record->name }}</h2>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-bold rounded">
                        {{ $record->code }}
                    </span>
                    <span class="text-gray-500 text-sm">
                        {{ $record->region->name ?? 'Wilayah Tidak Diketahui' }}
                    </span>
                </div>
            </div>
            <div class="text-right">
                <p class="text-xs text-gray-500 uppercase font-semibold">Nilai Kontrak</p>
                <p class="text-xl font-bold text-green-600">
                    Rp {{ number_format($record->contract_value, 0, ',', '.') }}
                </p>
                <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('edit', ['record' => $record]) }}" 
                   class="text-sm text-blue-600 hover:underline mt-1 inline-block">
                    Edit Informasi Proyek &rarr;
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('rab', ['record' => $record]) }}" 
           class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-blue-400 transition flex flex-col items-center text-center">
            <div class="h-12 w-12 bg-blue-50 text-blue-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <x-heroicon-o-calculator class="w-7 h-7" />
            </div>
            <h3 class="font-bold text-gray-800 text-lg">RAB & Estimasi</h3>
            <p class="text-sm text-gray-500 mt-2">Kelola item pekerjaan, volume, dan analisa harga satuan.</p>
        </a>

        <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('schedule', ['record' => $record]) }}" 
           class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-purple-400 transition flex flex-col items-center text-center">
            <div class="h-12 w-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <x-heroicon-o-calendar class="w-7 h-7" />
            </div>
            <h3 class="font-bold text-gray-800 text-lg">Time Schedule</h3>
            <p class="text-sm text-gray-500 mt-2">Atur jadwal mingguan dan bobot pekerjaan (Kurva S Plan).</p>
        </a>

        <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('progress', ['record' => $record]) }}" 
           class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-purple-400 transition flex flex-col items-center text-center">
            <div class="h-12 w-12 bg-purple-50 text-purple-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <x-heroicon-o-chart-bar class="w-7 h-7" />
            </div>
            <h3 class="font-bold text-gray-800 text-lg">Kurva S Progress</h3>
            <p class="text-sm text-gray-500 mt-2">Dashboard Monitoring Progress Kurva S.</p>
        </a>

        <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('opname', ['record' => $record]) }}" 
           class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-green-400 transition flex flex-col items-center text-center">
            <div class="h-12 w-12 bg-green-50 text-green-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <x-heroicon-o-clipboard-document-check class="w-7 h-7" />
            </div>
            <h3 class="font-bold text-gray-800 text-lg">Opname Progress</h3>
            <p class="text-sm text-gray-500 mt-2">Input realisasi fisik mingguan di lapangan.</p>
        </a>

        <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('resource_plan', ['record' => $record]) }}" 
           class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-orange-400 transition flex flex-col items-center text-center">
            <div class="h-12 w-12 bg-orange-50 text-orange-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <x-heroicon-o-shopping-cart class="w-7 h-7" />
            </div>
            <h3 class="font-bold text-gray-800 text-lg">Logistik & Belanja</h3>
            <p class="text-sm text-gray-500 mt-2">Rencana kebutuhan material mingguan (Semen, Besi, dll).</p>
        </a>

        <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('cashflow', ['record' => $record]) }}" 
           class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-red-400 transition flex flex-col items-center text-center">
            <div class="h-12 w-12 bg-red-50 text-red-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <x-heroicon-o-banknotes class="w-7 h-7" />
            </div>
            <h3 class="font-bold text-gray-800 text-lg">Keuangan & Cashflow</h3>
            <p class="text-sm text-gray-500 mt-2">Monitoring arus kas masuk/keluar dan profitabilitas.</p>
        </a>

        <a href="{{ \App\Filament\App\Resources\ProjectResource::getUrl('termins', ['record' => $record]) }}" 
           class="group bg-white p-6 rounded-xl border border-gray-200 shadow-sm hover:shadow-md hover:border-teal-400 transition flex flex-col items-center text-center">
            <div class="h-12 w-12 bg-teal-50 text-teal-600 rounded-full flex items-center justify-center mb-4 group-hover:scale-110 transition">
                <x-heroicon-o-document-currency-dollar class="w-7 h-7" />
            </div>
            <h3 class="font-bold text-gray-800 text-lg">Termin & Penagihan</h3>
            <p class="text-sm text-gray-500 mt-2">Jadwal penagihan ke owner berdasarkan progress fisik.</p>
        </a>

    </div>

</x-filament-panels::page>