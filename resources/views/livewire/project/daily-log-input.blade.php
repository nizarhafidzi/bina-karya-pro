<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Input Logbook Harian</h2>
            
            @if (session()->has('message'))
                <div class="bg-green-50 text-green-700 p-4 rounded-lg mb-4 text-sm font-medium">
                    {{ session('message') }}
                </div>
            @endif

            <form wire:submit.prevent="save" class="space-y-4">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700">Tanggal Laporan</label>
                    <input type="date" wire:model="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cuaca Pagi</label>
                        <select wire:model="weather_am" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option>Cerah</option>
                            <option>Berawan</option>
                            <option>Hujan Ringan</option>
                            <option>Hujan Lebat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cuaca Sore</label>
                        <select wire:model="weather_pm" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option>Cerah</option>
                            <option>Berawan</option>
                            <option>Hujan Ringan</option>
                            <option>Hujan Lebat</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Total Tenaga Kerja (Orang)</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="number" wire:model="manpower_total" class="block w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 pl-3 pr-12" placeholder="0">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 sm:text-sm">Org</span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Pekerjaan Hari Ini</label>
                    <textarea wire:model="work_note" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Contoh: Pengecoran kolom lantai 1 zona A..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Material Masuk (Opsional)</label>
                    <textarea wire:model="material_note" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Catat jika ada semen/besi datang..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 text-red-600">Kendala / Masalah (Opsional)</label>
                    <textarea wire:model="problem_note" rows="2" class="mt-1 block w-full rounded-md border-red-300 focus:border-red-500 focus:ring-red-500 shadow-sm bg-red-50" placeholder="Contoh: Hujan deras jam 2 siang, pekerjaan berhenti..."></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Dokumentasi Foto</label>
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p class="text-sm text-gray-500"><span class="font-semibold">Klik upload</span> atau drag and drop</p>
                            </div>
                            <input type="file" wire:model="photos" multiple class="hidden" />
                        </label>
                    </div>
                    @if ($photos)
                        <div class="mt-4 grid grid-cols-4 gap-2">
                            @foreach ($photos as $photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 object-cover rounded shadow">
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="pt-4">
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                        <span wire:loading.remove>Simpan Laporan</span>
                        <span wire:loading>Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="lg:col-span-1">
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-6 sticky top-6">
            <h3 class="text-blue-900 font-bold text-lg mb-2">Target Minggu Ini</h3>
            <p class="text-blue-600 text-sm mb-4">Berdasarkan Resource Plan</p>

            <div class="space-y-4">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 uppercase font-bold">Estimasi Tenaga Kerja</p>
                    <p class="text-2xl font-bold text-gray-800 mt-1">
                        {{ $plannedManpower > 0 ? $plannedManpower : '-' }} <span class="text-sm font-normal text-gray-500">Org/Hari</span>
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Akumulasi Mingguan</p>
                </div>

                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-xs text-gray-500 uppercase font-bold mb-2">Kebutuhan Material Utama</p>
                    @if(count($plannedMaterials) > 0)
                        <ul class="space-y-2">
                            @foreach($plannedMaterials as $mat)
                                <li class="flex justify-between text-sm border-b border-gray-100 pb-1">
                                    <span class="text-gray-600">{{ $mat['name'] }}</span>
                                    <span class="font-bold text-gray-800">{{ number_format($mat['qty']) }} {{ $mat['unit'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-400 italic">Tidak ada plan material signifikan.</p>
                    @endif
                </div>
            </div>

            <div class="mt-6 text-xs text-blue-700">
                <p>💡 <strong>Tips:</strong> Gunakan data ini sebagai acuan. Jika realisasi jauh berbeda, tulis alasannya di kolom "Kendala".</p>
            </div>
        </div>
    </div>

</div>