@extends('layouts.master')

@section('title', $sayfa->adi)
@section('description', $sayfa->kisa ?? Str::limit(strip_tags($sayfa->aciklama), 160))
@section('keywords', $sayfa->etiketler ?? '')

@section('content')
@php
    use Illuminate\Support\Facades\DB;
    $headerBgStyle = \App\Helpers\HeaderBackgroundHelper::getHeaderBackgroundStyle('sayfa');
@endphp
<div class="top-header overlay" style="{{ $headerBgStyle }} padding: 180px 0 80px 0; min-height: 500px; display: flex; align-items: flex-end;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper" style="padding-bottom: 60px;">
                    <h1 class="heading" style="margin-bottom: 25px; font-size: 42px;">{{ $sayfa->adi }}</h1>
                    @if($sayfa->kisa)
                    <p class="subheading" style="line-height: 2; font-size: 18px;">{{ $sayfa->kisa }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<section class="modern-section sayfa-icerik-bolum" style="background:#ffffff !important; margin:0 !important; padding:0 !important;">
    <div class="container">
        <div id="wrapper" style="padding:60px 0;">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-content" style="background:transparent !important; box-shadow:none !important; border:none !important; padding:0 !important;">
                        @if($sayfa->resim)
                        <div class="page-image mb-4">
                            <img src="{{ asset('tema/uploads/sayfalar/'.$sayfa->resim) }}" 
                                 alt="{{ $sayfa->adi }}" 
                                 class="img-fluid" style="border-radius: 15px; width: 100%;">
                        </div>
                        @endif

                        @php
                            $builderContent = null;
                            if (isset($sayfa->builder_content) && !empty($sayfa->builder_content)) {
                                try {
                                    $builderContent = json_decode($sayfa->builder_content, true);
                                } catch (\Exception $e) {
                                    // JSON değilse, direkt HTML olarak kullan
                                    $builderContent = ['html' => $sayfa->builder_content, 'css' => ''];
                                }
                            }
                        @endphp

                        @if($builderContent && isset($builderContent['html']))
                            {{-- Visual Builder İçeriği --}}
                            <div class="builder-content" style="color:#2b2f36;">
                                <style>{!! $builderContent['css'] ?? '' !!}</style>
                                {!! $builderContent['html'] !!}
                            </div>
                        @else
                            {{-- Normal İçerik --}}
                        <div class="page-text" style="color:#2b2f36 !important; line-height: 1.8;">
                            {!! $sayfa->aciklama !!}
                        </div>
                        @endif

                        @if($sayfa->galeri && count($sayfa->galeri) > 0)
                        <div class="page-gallery mt-5">
                            <h3 class="mb-4" style="color:#1a2332;">Galeri</h3>
                            <div class="row">
                                @foreach($sayfa->galeri as $resim)
                                <div class="col-md-3 mb-3">
                                    <a href="{{ asset('tema/uploads/sayfalar/galeri/'.$resim) }}" 
                                       data-lightbox="gallery" 
                                       data-title="{{ $sayfa->adi }}">
                                        <img src="{{ asset('tema/uploads/sayfalar/galeri/'.$resim) }}" 
                                             alt="{{ $sayfa->adi }}" 
                                             class="img-fluid rounded">
                                    </a>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/sayfa.css') }}">
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">
<style>
/* Sözleşme/statik sayfa içeriği — açık zeminde okunaklı tipografi */
.sayfa-icerik-bolum { background:#ffffff !important; }
.sayfa-icerik-bolum .page-text,
.sayfa-icerik-bolum .builder-content { color:#2b2f36 !important; font-size:15.5px; line-height:1.9; }
.sayfa-icerik-bolum .page-text h1,
.sayfa-icerik-bolum .page-text h2,
.sayfa-icerik-bolum .page-text h3,
.sayfa-icerik-bolum .page-text h4,
.sayfa-icerik-bolum .builder-content h1,
.sayfa-icerik-bolum .builder-content h2,
.sayfa-icerik-bolum .builder-content h3,
.sayfa-icerik-bolum .builder-content h4 { color:#1a2332 !important; margin:22px 0 12px; font-weight:700; }
.sayfa-icerik-bolum .page-text p,
.sayfa-icerik-bolum .page-text li,
.sayfa-icerik-bolum .page-text span,
.sayfa-icerik-bolum .page-text div,
.sayfa-icerik-bolum .builder-content p,
.sayfa-icerik-bolum .builder-content li,
.sayfa-icerik-bolum .builder-content span { color:#3a4049 !important; }
.sayfa-icerik-bolum .page-text a,
.sayfa-icerik-bolum .builder-content a { color:#8a8a1f !important; text-decoration:underline; }
.sayfa-icerik-bolum .page-text strong { color:#1a2332 !important; }
/* İçeriğe gömülü koyu zemin kutuları varsa metni beyaz kalsın (ters durumda okunmazlık olmasın) */
.sayfa-icerik-bolum .page-text [style*="background"][style*="#1a2332"],
.sayfa-icerik-bolum .page-text [style*="background:#0"],
.sayfa-icerik-bolum .page-text [style*="background:#1"],
.sayfa-icerik-bolum .page-text [style*="background:#2"] { color:#fff !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>
@endpush
@endsection