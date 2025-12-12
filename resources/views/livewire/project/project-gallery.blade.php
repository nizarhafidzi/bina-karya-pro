<div class="space-y-8" x-data="{ open: false, imgSrc: '', imgCap: '' }">
    
    @foreach($logs as $log)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <div>
                    <h3 class="font-bold text-gray-800 text-lg">{{ \Carbon\Carbon::parse($log->date)->translatedFormat('l, d F Y') }}</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Cuaca: {{ $log->weather_am }} / {{ $log->weather_pm }} • Pekerja: {{ $log->manpower_total }} Org
                    </p>
                </div>
                <span class="text-xs font-mono text-gray-400">#LOG-{{ $log->id }}</span>
            </div>

            <div class="p-6">
                @if($log->work_note)
                    <p class="text-sm text-gray-600 mb-4 bg-blue-50 p-3 rounded border-l-4 border-blue-400">
                        <span class="font-bold">Aktivitas:</span> {{ $log->work_note }}
                    </p>
                @endif

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($log->images as $img)
                        @php
                            // FIX LOGIC: Cek apakah path adalah URL eksternal (Dummy) atau file lokal
                            $imageUrl = \Illuminate\Support\Str::startsWith($img->path, 'http') 
                                ? $img->path 
                                : asset('storage/' . $img->path);
                        @endphp

                        <div class="group relative aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer hover:shadow-md transition"
                             @click="open = true; imgSrc = '{{ $imageUrl }}'; imgCap = '{{ $img->caption }}'">
                            
                            <img src="{{ $imageUrl }}" 
                                 class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                                 loading="lazy">
                            
                            <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition duration-300"></div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach

    <div class="mt-4">
        {{ $logs->links() }}
    </div>

    <div x-show="open" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @keydown.escape.window="open = false">
        
        <div class="relative max-w-5xl max-h-screen w-full" @click.away="open = false">
            <button @click="open = false" class="absolute -top-10 right-0 text-white hover:text-gray-300">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
            
            <img :src="imgSrc" class="w-full h-auto max-h-[85vh] object-contain rounded shadow-2xl mx-auto">
            
            <p class="text-center text-white mt-4 font-medium" x-text="imgCap"></p>
        </div>
    </div>

</div>