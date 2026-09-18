@extends('admin._layout')

@section('title', 'Blog')

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Blog</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="newspaper"></i>
            Blog Yazıları
            <span class="badge badge-brand">{{ $bloglar->total() }}</span>
        </h1>
        <div class="page-subtitle">Blog yazılarını yönetin, yayınlayın, düzenleyin</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.blog.ekle') }}" class="btn btn-primary">
            <i data-lucide="plus"></i>
            <span>Yeni Yazı</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
@php
    $toplam = $bloglar->total();
    $yayinda = $bloglar->filter(fn($b) => (int)($b->durum ?? 0) === 1)->count();
    $taslak = $bloglar->filter(fn($b) => (int)($b->durum ?? 0) === 0)->count();

    $buayBaslangic = now()->startOfMonth();
    $buay = 0;
    foreach ($bloglar as $b) {
        $tarih = $b->tarih ?? $b->created_at ?? null;
        if (!$tarih) continue;
        try {
            if (\Carbon\Carbon::parse($tarih)->gte($buayBaslangic)) $buay++;
        } catch (\Throwable $e) {}
    }
@endphp

<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="newspaper"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Yazı</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Yayında</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $yayinda }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="file-text"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Taslak</div>
            <div class="stat-card-value" style="color:var(--warning)">{{ $taslak }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,0.18);color:#b8b62e">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bu Ay</div>
            <div class="stat-card-value" style="color:var(--brand-dark)">{{ $buay }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Yeni yazı</div>
        </div>
    </div>
</div>

{{-- TABLO --}}
@if($bloglar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="newspaper" class="empty-state-icon"></i>
            <h4>Henüz blog yazısı yok</h4>
            <p>İlk blog yazınızı ekleyerek içerik üretmeye başlayın.</p>
            <div style="margin-top:16px">
                <a href="{{ route('admin.blog.ekle') }}" class="btn btn-primary">
                    <i data-lucide="plus"></i>
                    <span>Yeni Yazı</span>
                </a>
            </div>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:70px"></th>
                        <th>Başlık / Slug</th>
                        <th style="width:110px">Tarih</th>
                        <th style="width:90px">Dil</th>
                        <th style="width:110px">Durum</th>
                        <th class="text-right" style="width:140px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bloglar as $b)
                        @php
                            $baslik = $b->baslik ?? $b->adi ?? '—';
                            $seo = $b->seo ?? '';
                            $resim = $b->resim ?? null;
                            $aktif = (int)($b->durum ?? 0) === 1;
                            $dil = (int)($b->dil ?? 1);

                            $tarih = $b->tarih ?? $b->created_at ?? null;
                            $tarihFmt = null;
                            $tarihHuman = null;
                            if ($tarih) {
                                try {
                                    $c = \Carbon\Carbon::parse($tarih);
                                    $tarihFmt = $c->format('d.m.Y');
                                    $tarihHuman = $c->locale('tr')->diffForHumans();
                                } catch (\Throwable $e) {}
                            }

                            $dilMap = [1 => ['🇹🇷','TR'], 2 => ['🇬🇧','EN'], 3 => ['🇸🇦','AR']];
                            $dilInfo = $dilMap[$dil] ?? ['🌐', '—'];
                        @endphp
                        <tr>
                            <td style="color:var(--text-muted);font-size:12px">#{{ $b->id }}</td>
                            <td>
                                @if($resim)
                                    <img src="{{ asset('tema/uploads/bloglar/' . $resim) }}"
                                         alt="{{ $baslik }}"
                                         style="width:56px;height:42px;object-fit:cover;border-radius:6px;border:1px solid var(--border)"
                                         onerror="this.style.display='none'">
                                @else
                                    <div style="width:56px;height:42px;border-radius:6px;background:var(--bg-subtle);display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
                                        <i data-lucide="image" style="width:18px;height:18px"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.blog.duzenle', $b->id) }}"
                                   style="font-weight:600;color:var(--text);text-decoration:none">
                                    {{ \Illuminate\Support\Str::limit($baslik, 60) }}
                                </a>
                                @if($seo)
                                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;font-family:monospace">
                                        /{{ \Illuminate\Support\Str::limit($seo, 50) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($tarihFmt)
                                    <div style="font-size:12.5px;font-weight:500">{{ $tarihFmt }}</div>
                                    <div style="font-size:11px;color:var(--text-muted)">{{ $tarihHuman }}</div>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-neutral" style="font-size:11.5px">
                                    {{ $dilInfo[0] }} {{ $dilInfo[1] }}
                                </span>
                            </td>
                            <td>
                                @if($aktif)
                                    <span class="badge badge-success">
                                        <i data-lucide="check" style="width:11px;height:11px"></i>
                                        Yayında
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        <i data-lucide="pause" style="width:11px;height:11px"></i>
                                        Taslak
                                    </span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <a href="{{ route('admin.blog.duzenle', $b->id) }}" class="table-action" title="Düzenle">
                                        <i data-lucide="edit-3"></i>
                                    </a>
                                    @if($seo && Route::has('blog.detay'))
                                        <a href="{{ url('/blog/' . $seo) }}" target="_blank" class="table-action" title="Sitede gör">
                                            <i data-lucide="external-link"></i>
                                        </a>
                                    @endif
                                    <button type="button" class="table-action"
                                            style="color:var(--danger)"
                                            onclick="silBlog({{ $b->id }}, '{{ addslashes($baslik) }}')"
                                            title="Sil">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($bloglar->hasPages())
        <div style="margin-top:16px">{{ $bloglar->links() }}</div>
    @endif

    @foreach($bloglar as $b)
        <form id="del-blog-{{ $b->id }}"
              action="{{ route('admin.blog.sil', $b->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function silBlog(id, baslik) {
    if (confirm('Bu blog yazısını silmek istediğinize emin misiniz?\n\n' + baslik + '\n\nBu işlem geri alınamaz.')) {
        document.getElementById('del-blog-' + id).submit();
    }
}
</script>

@endsection