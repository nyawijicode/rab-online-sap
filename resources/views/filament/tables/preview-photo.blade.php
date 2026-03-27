<div>
    @php
        $ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
        $url = Storage::url($photo);
    @endphp

    @if($ext === 'pdf')
        <iframe src="{{ $url }}" class="w-full" style="height: 500px; border: none;"></iframe>
        <a href="{{ $url }}" target="_blank" class="text-sm text-blue-600 underline mt-2 inline-block">
            Buka PDF di tab baru
        </a>
    @else
        <img src="{{ $url }}" alt="Bukti Dadakan" class="w-full h-auto" />
    @endif
</div>