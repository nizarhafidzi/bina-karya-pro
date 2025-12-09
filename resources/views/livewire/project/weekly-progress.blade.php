<div class="max-w-3xl mx-auto pb-12">
    <a href="{{ route('project.dashboard', $project) }}" class="text-sm text-gray-500 hover:text-primary mb-4 inline-flex items-center">
        &larr; Kembali ke Dashboard
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 bg-primary text-white">
            <h2 class="text-xl font-bold">Input Opname Mingguan</h2>
            <p class="text-blue-100 text-sm mt-1">{{ $project->name }}</p>
        </div>

        <div class="p-6 bg-gray-50 border-b border-gray-100">
            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Minggu Pelaporan</label>
            <select wire:model.live="selectedWeek" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                <option value="">-- Pilih Minggu --</option>
                @foreach($weekOptions as $week => $label)
                    <option value="{{ $week }}">{{ $label }}</option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-2">
                Tips: Input persentase kumulatif (Total selesai sampai saat ini).
            </p>
        </div>

        @if($selectedWeek)
        <div class="p-0">
            @if (session()->has('error'))
                <div class="p-4 bg-red-100 text-red-700 text-sm font-bold border-l-4 border-red-500">
                    {{ session('error') }}
                </div>
            @endif

            <div class="hidden md:grid grid-cols-12 gap-4 px-6 py-3 bg-gray-100 text-xs font-bold text-gray-500 uppercase tracking-wider">
                <div class="col-span-6">Nama Pekerjaan</div>
                <div class="col-span-2 text-center">Bobot</div>
                <div class="col-span-2 text-center">Lalu (%)</div>
                <div class="col-span-2 text-center">Saat Ini (%)</div>
            </div>

            <div class="divide-y divide-gray-100">
                @forelse($progressData as $itemId => $data)
                    <div class="p-4 md:px-6 md:py-4 grid grid-cols-1 md:grid-cols-12 gap-4 items-center hover:bg-gray-50 transition">
                        
                        <div class="col-span-12 md:col-span-6">
                            <p class="text-sm font-semibold text-gray-900">{{ $data['name'] }}</p>
                            <div class="flex items-center gap-2 mt-1 md:hidden">
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">Bobot: {{ number_format($data['weight'], 2) }}%</span>
                            </div>
                        </div>

                        <div class="hidden md:block col-span-2 text-center">
                            <span class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded">{{ number_format($data['weight'], 2) }}%</span>
                        </div>

                        <div class="col-span-6 md:col-span-2 text-center flex flex-col justify-center">
                            <span class="md:hidden text-xs text-gray-400 uppercase mb-1">Minggu Lalu</span>
                            <span class="text-sm text-gray-500">{{ number_format($data['prev_cumulative'], 2) }}%</span>
                        </div>

                        <div class="col-span-6 md:col-span-2">
                            <span class="md:hidden text-xs text-gray-700 font-bold uppercase mb-1 block">Saat Ini</span>
                            <div class="relative">
                                <input type="number" 
                                       wire:model="progressData.{{ $itemId }}.current_cumulative"
                                       min="{{ $data['prev_cumulative'] }}" 
                                       max="100" 
                                       step="0.01"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                                       placeholder="0-100">
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 sm:text-sm">%</span>
                                </div>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="p-6 text-center text-gray-500">
                        Tidak ada item pekerjaan ditemukan.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="p-6 bg-gray-50 border-t border-gray-100 flex justify-end">
            <button wire:click="saveProgress" 
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-secondary hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-secondary disabled:opacity-50">
                <span wire:loading.remove>Simpan Progress Minggu {{ $selectedWeek }}</span>
                <span wire:loading>Menyimpan...</span>
            </button>
        </div>
        @endif
    </div>
</div>