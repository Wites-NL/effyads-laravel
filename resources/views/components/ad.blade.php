@if ($ad)
    <div {{ $attributes->merge(['class' => 'effyads-ad'.($ad->format ? ' effyads-format-'.$ad->format : '')]) }} data-zone="{{ $zone }}"@if ($ad->format) data-format="{{ $ad->format }}"@endif>
        <a href="{{ route('effyads.track.click', $ad->platform_id) }}" target="_blank" rel="noopener sponsored">
            @if ($ad->media_url)
                <img src="{{ $ad->media_url }}" alt="{{ $ad->title }}" loading="lazy"@if ($imageWidth) width="{{ $imageWidth }}"@endif @if ($imageHeight) height="{{ $imageHeight }}"@endif>
            @else
                {{ $ad->title }}
            @endif
        </a>
        <img src="{{ route('effyads.track.view', $ad->platform_id) }}" width="1" height="1" alt="" style="display:none">
    </div>
@endif
