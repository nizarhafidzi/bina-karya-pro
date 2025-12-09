<div class="max-w-xl mx-auto">
    <a href="{{ route('project.dashboard', $project) }}" class="text-sm text-gray-500 hover:text-primary mb-4 inline-flex items-center">
        &larr; Kembali ke Dashboard
    </a>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 bg-primary">
            <h2 class="text-xl font-bold text-white">Laporan Harian Lapangan</h2>
            <p class="text-blue-100 text-sm mt-1">{{ $project->name }}</p>
        </div>

        <div class="p-6 space-y-6">
            <form wire:submit="saveReport" class="space-y-5">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Laporan</label>
                    <input type="date" wire:model="date" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                    @error('date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuaca Pagi</label>
                        <select wire:model="weather_am" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                            <option value="Cerah">Cerah ☀️</option>
                            <option value="Berawan">Berawan ☁️</option>
                            <option value="Hujan Ringan">Hujan Ringan 🌧️</option>
                            <option value="Hujan Lebat">Hujan Lebat ⛈️</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cuaca Siang/Sore</label>
                        <select wire:model="weather_pm" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                            <option value="Cerah">Cerah ☀️</option>
                            <option value="Berawan">Berawan ☁️</option>
                            <option value="Hujan Ringan">Hujan Ringan 🌧️</option>
                            <option value="Hujan Lebat">Hujan Lebat ⛈️</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Pekerjaan Yang Dilakukan Hari Ini</label>
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 max-h-60 overflow-y-auto">
                        @if(count($availableItems) > 0)
                            <div class="space-y-2">
                                @foreach($availableItems as $item)
                                    <label class="flex items-center space-x-3 p-2 hover:bg-white rounded cursor-pointer transition">
                                        <input type="checkbox" 
                                               wire:model="selectedItems" 
                                               value="{{ $item['id'] }}" 
                                               class="h-5 w-5 text-primary border-gray-300 rounded focus:ring-primary">
                                        <span class="text-gray-700 text-sm font-medium">{{ $item['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500 italic">Tidak ada item pekerjaan terdaftar.</p>
                        @endif
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Centang pekerjaan yang ada aktivitasnya hari ini.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Pekerjaan / Kendala</label>
                    <textarea wire:model="notes" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary focus:ring-primary" placeholder="Tuliskan aktivitas utama dan kendala di lapangan..."></textarea>
                    @error('notes') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Foto Dokumentasi</label>
                    <div class="flex items-center justify-center w-full">
                        <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <svg class="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                </svg>
                                <p class="text-sm text-gray-500"><span class="font-semibold">Tap to upload</span> or drag and drop</p>
                                <p class="text-xs text-gray-500">PNG, JPG (MAX. 5MB)</p>
                            </div>
                            <input id="dropzone-file" type="file" wire:model="photos" multiple class="hidden" accept="image/*" />
                        </label>
                    </div>
                    
                    @if ($photos)
                        <div class="flex gap-2 mt-4 overflow-x-auto">
                            @foreach ($photos as $photo)
                                <img src="{{ $photo->temporaryUrl() }}" class="h-20 w-20 object-cover rounded-lg border">
                            @endforeach
                        </div>
                    @endif
                    @error('photos.*') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <button type="submit" 
                        wire:loading.attr="disabled"
                        class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-primary hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50">
                    <span wire:loading.remove>Simpan Laporan</span>
                    <span wire:loading>Menyimpan...</span>
                </button>

            </form>
        </div>
    </div>
</div>