@extends('layouts.master')

@section('title', $blog->adi)
@section('description', $blog->kisa ?? Str::limit(strip_tags($blog->aciklama), 160))
@section('keywords', $blog->etiketler ?? '')

@section('content')

<!-- ***** BAŞLIK + BREADCRUMB (açık tema, blog listesiyle uyumlu) ***** -->
<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
    <div class="container">
        <div style="font-size:13px; color:#999; margin-bottom:10px;">
            <a href="{{ route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') ?? 'Ana Sayfa' }}</a>
            <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
            <a href="{{ route('blog') }}" style="color:#999; text-decoration:none;">{{ __('messages.blog') ?? 'Blog' }}</a>
            <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
            <span style="color:#1a1a1a; font-weight:600;">{{ Str::limit($blog->adi, 50) }}</span>
        </div>
        <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0; line-height:1.3;">{{ $blog->adi }}</h1>
    </div>
</div>

<!-- ***** İÇERİK ***** -->
<div style="background:#f0f0ec; padding: 40px 0 70px;">
    <div class="container">
        <div style="display:grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap:24px; max-width:1100px; margin:0 auto; align-items:start;">

            <!-- SOL: ANA İÇERİK -->
            <div style="background:#fff !important; border:1px solid #ececec; border-radius:16px; padding:34px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                @if($blog->resim)
                <div style="margin-bottom:26px; border-radius:12px; overflow:hidden;">
                    <img src="{{ asset('tema/uploads/bloglar/'.$blog->resim) }}" alt="{{ $blog->adi }}" style="width:100%; height:auto; display:block;">
                </div>
                @endif

                <!-- Meta -->
                <div style="display:flex; flex-wrap:wrap; gap:18px; padding-bottom:20px; margin-bottom:24px; border-bottom:1px solid #f0f0ec;">
                    <span style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#888;">
                        <i class="mdi mdi-calendar" style="color:#b8b62e;"></i> {{ $blog->tarih ? date('d.m.Y', strtotime($blog->tarih)) : '' }}
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#888;">
                        <i class="mdi mdi-account" style="color:#b8b62e;"></i> {{ $blog->yazar ?? 'Admin' }}
                    </span>
                    @if(isset($blog->kategori) && is_object($blog->kategori) && isset($blog->kategori->adi))
                    <span style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#888;">
                        <i class="mdi mdi-folder" style="color:#b8b62e;"></i> {{ $blog->kategori->adi }}
                    </span>
                    @endif
                    <span style="display:inline-flex; align-items:center; gap:6px; font-size:13px; color:#888;">
                        <i class="mdi mdi-eye" style="color:#b8b62e;"></i> {{ $blog->hit ?? 0 }} {{ __('messages.views') ?? 'Görüntüleme' }}
                    </span>
                </div>

                <!-- İçerik gövdesi -->
                <div class="blog-content" style="color:#444 !important; font-size:16px; line-height:1.85;">
                    {!! $blog->aciklama !!}
                </div>

                @if($blog->etiketler)
                <div style="margin-top:34px; padding-top:24px; border-top:1px solid #f0f0ec;">
                    <h5 style="color:#1a1a1a; font-size:16px; font-weight:700; margin:0 0 14px;"><i class="mdi mdi-tag-multiple" style="color:#b8b62e;"></i> {{ __('messages.tags') ?? 'Etiketler' }}</h5>
                    @php
                        $etiketler = is_array($blog->etiketler) ? $blog->etiketler : explode(',', $blog->etiketler);
                    @endphp
                    @foreach($etiketler as $etiket)
                    <a href="{{ route('blog', ['etiket' => trim($etiket)]) }}" style="display:inline-block; background:rgba(184,182,46,.10); color:#9a981f; padding:6px 14px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; margin:0 8px 8px 0;">{{ trim($etiket) }}</a>
                    @endforeach
                </div>
                @endif

                <!-- Sosyal paylaşım -->
                <div style="margin-top:34px; padding-top:24px; border-top:1px solid #f0f0ec;">
                    <h5 style="color:#1a1a1a; font-size:16px; font-weight:700; margin:0 0 14px;"><i class="mdi mdi-share-variant" style="color:#b8b62e;"></i> {{ __('messages.share') ?? 'Paylaş' }}</h5>
                    <div style="display:flex; flex-wrap:wrap; gap:10px;">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ url()->current() }}" target="_blank" style="display:inline-flex; align-items:center; gap:7px; padding:9px 16px; border-radius:8px; background:#1877f2; color:#fff; font-size:13px; font-weight:600; text-decoration:none;"><i class="mdi mdi-facebook"></i> Facebook</a>
                        <a href="https://twitter.com/intent/tweet?url={{ url()->current() }}&text={{ urlencode($blog->adi) }}" target="_blank" style="display:inline-flex; align-items:center; gap:7px; padding:9px 16px; border-radius:8px; background:#1da1f2; color:#fff; font-size:13px; font-weight:600; text-decoration:none;"><i class="mdi mdi-twitter"></i> Twitter</a>
                        <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ url()->current() }}&title={{ urlencode($blog->adi) }}" target="_blank" style="display:inline-flex; align-items:center; gap:7px; padding:9px 16px; border-radius:8px; background:#0a66c2; color:#fff; font-size:13px; font-weight:600; text-decoration:none;"><i class="mdi mdi-linkedin"></i> LinkedIn</a>
                        <a href="https://wa.me/?text={{ urlencode($blog->adi.' '.url()->current()) }}" target="_blank" style="display:inline-flex; align-items:center; gap:7px; padding:9px 16px; border-radius:8px; background:#25D366; color:#fff; font-size:13px; font-weight:600; text-decoration:none;"><i class="mdi mdi-whatsapp"></i> WhatsApp</a>
                    </div>
                </div>

                <!-- İlgili yazılar -->
                @if($ilgili_bloglar && count($ilgili_bloglar) > 0)
                <div style="margin-top:40px; padding-top:28px; border-top:1px solid #f0f0ec;">
                    <h4 style="color:#1a1a1a; font-size:20px; font-weight:700; margin:0 0 18px;">{{ __('messages.related_posts') ?? 'İlgili Yazılar' }}</h4>
                    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:16px;">
                        @foreach($ilgili_bloglar as $ilgili)
                        <div style="border:1px solid #ececec; border-radius:12px; overflow:hidden; background:#fff; transition:box-shadow .2s ease;" onmouseover="this.style.boxShadow='0 6px 18px rgba(0,0,0,0.08)';" onmouseout="this.style.boxShadow='none';">
                            @if($ilgili->resim)
                            <a href="{{ route('blog.detay', $ilgili->seo) }}"><img src="{{ asset('tema/uploads/bloglar/'.$ilgili->resim) }}" alt="{{ $ilgili->adi }}" style="width:100%; height:120px; object-fit:cover; display:block;"></a>
                            @endif
                            <div style="padding:14px;">
                                <h5 style="font-size:14px; font-weight:600; margin:0 0 6px; line-height:1.4;">
                                    <a href="{{ route('blog.detay', $ilgili->seo) }}" style="color:#1a1a1a; text-decoration:none;">{{ Str::limit($ilgili->adi, 50) }}</a>
                                </h5>
                                <span style="font-size:12px; color:#999;"><i class="mdi mdi-calendar"></i> {{ date('d.m.Y', strtotime($ilgili->tarih)) }}</span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- SAĞ: SIDEBAR -->
            <div style="display:flex; flex-direction:column; gap:20px;">

                @if($kategoriler && count($kategoriler) > 0)
                <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h5 style="color:#1a1a1a; font-size:16px; font-weight:700; margin:0 0 16px; padding-bottom:12px; border-bottom:2px solid #b8b62e; display:inline-block;"><i class="mdi mdi-folder" style="color:#b8b62e;"></i> {{ __('messages.categories') ?? 'Kategoriler' }}</h5>
                    <ul style="list-style:none; margin:0; padding:0;">
                        @foreach($kategoriler as $kategori)
                        <li style="margin-bottom:4px;">
                            <a href="{{ route('blog.kategori', $kategori->seo) }}" style="display:flex; align-items:center; justify-content:space-between; padding:9px 12px; border-radius:8px; color:#555; text-decoration:none; font-size:14px; transition:all .2s ease;" onmouseover="this.style.background='rgba(184,182,46,.08)'; this.style.color='#1a1a1a';" onmouseout="this.style.background='transparent'; this.style.color='#555';">
                                <span><i class="mdi mdi-chevron-right" style="color:#b8b62e;"></i> {{ $kategori->adi }}</span>
                                <span style="background:rgba(184,182,46,.15); color:#9a981f; font-size:12px; font-weight:600; padding:2px 9px; border-radius:20px;">{{ $kategori->blog_count }}</span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @if($son_yazilar && count($son_yazilar) > 0)
                <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h5 style="color:#1a1a1a; font-size:16px; font-weight:700; margin:0 0 16px; padding-bottom:12px; border-bottom:2px solid #b8b62e; display:inline-block;"><i class="mdi mdi-newspaper" style="color:#b8b62e;"></i> {{ __('messages.recent_posts') ?? 'Son Yazılar' }}</h5>
                    <ul style="list-style:none; margin:0; padding:0;">
                        @foreach($son_yazilar as $son)
                        <li style="padding:12px 0; border-bottom:1px solid #f0f0ec;">
                            <a href="{{ route('blog.detay', $son->seo) }}" style="display:block; text-decoration:none;">
                                <h6 style="color:#1a1a1a; font-size:14px; font-weight:600; margin:0 0 4px; line-height:1.4;">{{ Str::limit($son->adi, 55) }}</h6>
                                <small style="color:#999; font-size:12px;"><i class="mdi mdi-calendar"></i> {{ date('d.m.Y', strtotime($son->tarih)) }}</small>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Arama -->
                <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h5 style="color:#1a1a1a; font-size:16px; font-weight:700; margin:0 0 16px; padding-bottom:12px; border-bottom:2px solid #b8b62e; display:inline-block;"><i class="mdi mdi-magnify" style="color:#b8b62e;"></i> {{ __('messages.search_blog') ?? 'Blog Ara' }}</h5>
                    <form action="{{ route('blog') }}" method="GET" style="display:flex; gap:8px;">
                        <input type="text" name="q" placeholder="{{ __('messages.search') ?? 'Arama...' }}" style="flex:1; padding:10px 14px; border:1px solid #e0e0d8; border-radius:8px; font-size:14px; background:#f7f7f4; color:#1a1a1a; outline:none;" onfocus="this.style.borderColor='#b8b62e'; this.style.background='#fff';" onblur="this.style.borderColor='#e0e0d8'; this.style.background='#f7f7f4';">
                        <button type="submit" style="padding:10px 16px; background:#b8b62e; color:#1a1a0e; border:none; border-radius:8px; cursor:pointer; font-size:16px;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';"><i class="mdi mdi-magnify"></i></button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    .blog-content { color:#444 !important; }
    .blog-content h1, .blog-content h2, .blog-content h3,
    .blog-content h4, .blog-content h5, .blog-content h6 { color:#1a1a1a !important; }
    .blog-content p, .blog-content li, .blog-content span, .blog-content div { color:#444 !important; }
    .blog-content a { color:#9a981f !important; }
    .blog-content img { max-width:100% !important; height:auto !important; border-radius:10px; }
    .blog-content blockquote { border-left:4px solid #b8b62e !important; background:rgba(184,182,46,.06) !important; color:#555 !important; padding:14px 20px; margin:18px 0; border-radius:0 8px 8px 0; }
</style>
@endsection