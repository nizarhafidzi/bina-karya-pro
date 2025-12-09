<x-filament-panels::page>
    <div class="space-y-6">
        
        <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Pilih Minggu Pelaporan</label>
            <select wire:model.live="selectedWeek" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white">
                <option value="">-- Pilih Minggu --</option>
                @foreach($weekOptions as $week => $label)
                    <option value="{{ $week }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if($selectedWeek)
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3">Nama Pekerjaan</th>
                        <th scope="col" class="px-6 py-3 text-center">Bobot (%)</th>
                        <th scope="col" class="px-6 py-3 text-center">Lalu (%)</th>
                        <th scope="col" class="px-6 py-3 text-center" style="width: 200px;">S.d. Minggu Ini (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($progressData as $itemId => $data)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $data['name'] }}
                        </td>

                        <td class="px-6 py-4 text-center">
                            <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300">
                                {{ number_format($data['weight'], 2) }}%
                            </span>
                        </td>

                        <td class="px-6 py-4 text-center text-gray-500">
                            {{ number_format($data['prev_cumulative'], 2) }}%
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <input type="number" 
                                       wire:model="progressData.{{ $itemId }}.current_cumulative"
                                       min="{{ $data['prev_cumulative'] }}" 
                                       max="100" 
                                       step="0.01"
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                       placeholder="0-100">
                                <span class="ml-2">%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="flex justify-end mt-4">
            <x-filament::button wire:click="saveProgress" color="success" icon="heroicon-m-check">
                Simpan Realisasi Minggu {{ $selectedWeek }}
            </x-filament::button>
        </div>
        @endif

    </div>
</x-filament-panels::page>