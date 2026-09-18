<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ url('/') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    
    @if(isset($sayfalar))
        @foreach($sayfalar as $sayfa)
            @if($sayfa->seo)
            <url>
                <loc>{{ route('sayfa', $sayfa->seo) }}</loc>
                <lastmod>{{ $sayfa->updated_at ? $sayfa->updated_at->toAtomString() : now()->toAtomString() }}</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.8</priority>
            </url>
            @endif
        @endforeach
    @endif
    
    @if(isset($bloglar))
        @foreach($bloglar as $blog)
            @if($blog->seo)
            <url>
                <loc>{{ route('blog.detay', $blog->seo) }}</loc>
                <lastmod>{{ $blog->updated_at ? $blog->updated_at->toAtomString() : now()->toAtomString() }}</lastmod>
                <changefreq>weekly</changefreq>
                <priority>0.7</priority>
            </url>
            @endif
        @endforeach
    @endif
    
    <url>
        <loc>{{ route('paketler') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    
    <url>
        <loc>{{ route('hosting') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    
    <url>
        <loc>{{ route('blog') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>{{ route('firsatlar') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    
    <url>
        <loc>{{ route('referanslar') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    
    <url>
        <loc>{{ route('iletisim') }}</loc>
        <lastmod>{{ now()->toAtomString() }}</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.6</priority>
    </url>
</urlset>
