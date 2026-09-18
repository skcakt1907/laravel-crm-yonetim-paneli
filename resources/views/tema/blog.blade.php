@extends('layouts.master')

@section('title', 'Blog')

@section('content')
@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/blog.css') }}">
@endpush

@php
    use Illuminate\Support\Facades\DB;
@endphp
<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
    <div class="container">
        <div style="font-size:13px; color:#999; margin-bottom:10px;">
            <a href="{{ route('anasayfa') }}" style="color:#999; text-decoration:none;">Ana Sayfa</a>
            <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
            <span style="color:#1a1a1a; font-weight:600;">Blog</span>
        </div>
        <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ __('messages.blog') }}</h1>
        <p style="color:#999; font-size:14px; margin:6px 0 0;">{{ __('messages.latest_blog_posts') }}</p>
    </div>
</div>

<section class="blog-section">
    <div class="container">
        <div class="blog-grid">
            @forelse($bloglar as $blog)
            <article class="news-card">
                <div class="news-thumb">
                    @php
                        $blogImage = $blog->resim ? asset('tema/uploads/bloglar/'.$blog->resim) : asset('tema/img/noimage.png');
                    @endphp
                    <img src="{{ $blogImage }}" alt="{{ $blog->adi }}">
                    <span class="news-date">{{ $blog->tarih ? date('d.m.Y', strtotime($blog->tarih)) : date('d.m.Y') }}</span>
                </div>
                <div class="news-body">
                    <h4 class="news-title">
                        <a href="{{ route('blog.detay', $blog->seo) }}">{{ Str::limit($blog->adi, 60) }}</a>
                    </h4>
                    <p class="news-excerpt">{{ Str::limit(strip_tags($blog->aciklama), 180) }}</p>
                    <div class="news-footer">
                        <a href="{{ route('blog.detay', $blog->seo) }}" class="read-more">
                            {{ __('messages.read_more') ?? 'Devamını Oku' }} <i class="mdi mdi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </article>
            @empty
            <div class="col-12">
                <div class="blog-empty">
                    <i class="mdi mdi-information"></i>
                    <strong>{{ __('messages.info_label') }}</strong> {{ __('messages.no_blog_posts') }}
                </div>
            </div>
            @endforelse
        </div>

        <div class="row mt-4">
            <div class="col-12">
                {{ $bloglar->links() }}
            </div>
        </div>
    </div>
</section>
@endsection