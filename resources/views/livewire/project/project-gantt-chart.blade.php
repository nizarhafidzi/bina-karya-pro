<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 overflow-hidden">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-lg font-bold text-gray-800">Time Schedule (Gantt Chart)</h3>
        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded">
            Total Durasi: {{ $maxWeek }} Minggu
        </span>
    </div>

    <div class="overflow-x-auto pb-4">
        
        <div class="min-w-[800px] grid grid-cols-[250px_1fr] gap-0 border-t border-l border-gray-200">

            <div class="bg-gray-50 p-3 text-xs font-bold text-gray-500 uppercase tracking-wider border-b border-r border-gray-200 sticky left-0 z-10">
                Item Pekerjaan
            </div>

            <div class="grid border-b border-gray-200" 
                 style="grid-template-columns: repeat({{ $maxWeek }}, minmax(40px, 1fr));">
                @for($i = 1; $i <= $maxWeek; $i++)
                    <div class="text-center text-xs text-gray-400 py-3 border-r border-gray-100 {{ $i % 4 == 0 ? 'bg-gray-50' : '' }}">
                        W{{ $i }}
                    </div>
                @endfor
            </div>

            @forelse($items as $item)
                <div class="p-3 text-sm text-gray-700 font-medium border-b border-r border-gray-200 bg-white sticky left-0 z-10 truncate" 
                     title="{{ $item->ahsMaster->name ?? 'Item #'.$item->id }}">
                    {{ $item->ahsMaster->name ?? 'Item #'.$item->id }}
                </div>

                <div class="grid relative border-b border-gray-100 hover:bg-gray-50 transition" 
                     style="grid-template-columns: repeat({{ $maxWeek }}, minmax(40px, 1fr));">
                    
                    @for($i = 1; $i <= $maxWeek; $i++)
                        <div class="border-r border-gray-100 h-full w-full pointer-events-none {{ $i % 4 == 0 ? 'bg-gray-50/50' : '' }}"></div>
                    @endfor

                    @php
                        $start = $item->start_week;
                        $duration = $item->end_week - $item->start_week + 1;
                    @endphp

                    <div class="absolute top-2 bottom-2 rounded-md shadow-sm bg-blue-500 border border-blue-600 cursor-help group flex items-center justify-center"
                         style="grid-column: {{ $start }} / span {{ $duration }}; left: 2px; right: 2px;">
                        
                        @if($duration > 1)
                            <span class="text-[10px] text-white font-bold opacity-80">{{ $item->weight }}%</span>
                        @endif

                        <div class="absolute bottom-full mb-2 hidden group-hover:block w-48 p-2 bg-gray-800 text-white text-xs rounded shadow-lg z-50">
                            <p class="font-bold">{{ $item->ahsMaster->name ?? 'Item' }}</p>
                            <p>Minggu: {{ $start }} - {{ $item->end_week }}</p>
                            <p>Bobot: {{ $item->weight }}%</p>
                        </div>
                    </div>

                </div>
            @empty
                <div class="col-span-2 p-8 text-center text-gray-500 italic">
                    Belum ada jadwal yang dibuat.
                </div>
            @endforelse

        </div>
    </div>
</div>