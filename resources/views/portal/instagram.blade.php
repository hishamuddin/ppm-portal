@if(config('instagram.enabled'))
<section class="instagram-section" aria-labelledby="instagram-heading">
    <div class="container instagram-layout">
        <div class="instagram-intro">
            <h2 id="instagram-heading">{{ $ms ? 'Sorotan Aktiviti' : 'Activity Highlights' }}</h2>
            <span class="instagram-rule" aria-hidden="true"></span>
            <p><a href="{{ config('instagram.profile_url') }}" target="_blank" rel="noopener noreferrer">@malaysiapmo<span class="sr-only"> — {{ $ms ? 'buka Instagram dalam tab baharu' : 'open Instagram in a new tab' }}</span></a></p>
            <div class="instagram-building" aria-hidden="true">@include('portal.instagram-building')</div>
        </div>
        <div class="instagram-feed">
            <ul class="instagram-grid">
                @foreach(array_slice(config('instagram.posts', []), 0, 8) as $post)
                <li>
                    <a class="instagram-card" href="{{ $post['url'] }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ asset($post['image']) }}" alt="" width="640" height="800" loading="lazy" decoding="async">
                        @if($post['type'] === 'reel')
                        <span class="instagram-play" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M9 4L28 16 9 28Z" fill="currentColor"/></svg></span>
                        @elseif($post['type'] === 'carousel')
                        <span class="instagram-carousel" aria-hidden="true"><svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="10" y="4" width="18" height="18" rx="2"/><path d="M22 25v3H4V10h3"/></svg></span>
                        @endif
                        <span class="instagram-caption">{{ $post['title_'.$locale] }}<span class="sr-only"> — {{ $ms ? 'buka di Instagram dalam tab baharu' : 'open on Instagram in a new tab' }}</span></span>
                    </a>
                </li>
                @endforeach
            </ul>
            <div class="instagram-follow">
                <a class="button" href="{{ config('instagram.profile_url') }}" target="_blank" rel="noopener noreferrer">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg>
                    {{ $ms ? 'Ikuti di Instagram' : 'Follow on Instagram' }}
                    <span class="sr-only"> — {{ $ms ? 'tab baharu' : 'new tab' }}</span>
                </a>
            </div>
        </div>
    </div>
</section>
@endif
