<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($staticUrls as $url)
    <url>
        <loc>{{ $url }}</loc>
    </url>
@endforeach
@foreach ($copypastas as $copypasta)
    <url>
        <loc>{{ route('copypastas.show', [$copypasta, $copypasta->slug]) }}</loc>
        <lastmod>{{ ($copypasta->edited_at ?? $copypasta->published_at)->toAtomString() }}</lastmod>
    </url>
@endforeach
</urlset>
