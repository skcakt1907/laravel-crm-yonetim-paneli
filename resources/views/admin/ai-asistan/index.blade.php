@extends('admin._layout')

@section('title', 'AI Asistan')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">AI Asistan</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🤖 AI Asistan
            <span class="badge" style="background: #8b5cf6;color: var(--text-inverse);font-size:12px;vertical-align:middle">YAKINDA</span>
        </h1>
        <div class="page-subtitle">Doğal dille komut ver — paketler, müşteriler, faturalar hakkında bilgi al; işlem yaptır.</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start">

    {{-- Sohbet mockup --}}
    <div class="ai-chat" style="background: var(--surface,var(--surface));border: 1px solid var(--border,var(--border));border-radius: 16px;overflow:hidden;display:flex;flex-direction:column;min-height:520px">
        <div style="background: #16160f;color: var(--text-inverse);padding:14px 18px;display:flex;align-items:center;gap:10px">
            <span style="width:9px;height:9px;border-radius: 50%;background: #7ee081;display:inline-block"></span>
            <b>İş Ortağım Asistanı</b>
            <span style="color: #b9b98f;font-size:12px">· çevrimiçi (yakında)</span>
        </div>
        <div style="flex:1;padding:20px;background: var(--bg-soft,#f6f6f1);display:flex;flex-direction:column;gap:14px">
            <div style="max-width:80%;background: var(--surface,var(--surface));border: 1px solid var(--border,var(--border));color: var(--text,var(--text));padding:12px 15px;border-radius: 14px;border-bottom-left-radius: 4px;font-size:14px;line-height:1.6;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                Merhaba! 👋 Yakında burada şunları yazabileceksin:
                <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:12px">
                    <span class="ai-ex">kaç müşteri var</span>
                    <span class="ai-ex">bodrum otellerini listele</span>
                    <span class="ai-ex">referansları göster</span>
                    <span class="ai-ex">Sosyal Medya'da %10 indirim</span>
                    <span class="ai-ex">bu ayki faturalar</span>
                </div>
            </div>
            <div style="align-self:flex-end;max-width:70%;background: var(--brand);color: #1a1a0e;padding:11px 15px;border-radius: 14px;border-bottom-right-radius: 4px;font-size:14px">
                kaç müşteri var?
            </div>
            <div style="max-width:80%;background: var(--surface,var(--surface));border: 1px solid var(--border,var(--border));color: var(--text,var(--text));padding:12px 15px;border-radius: 14px;border-bottom-left-radius: 4px;font-size:14px;line-height:1.6;box-shadow:0 1px 3px rgba(0,0,0,.05)">
                📊 Şu an <b>752 müşteriniz</b> var. <span style="color: var(--text-muted,var(--text-muted));font-size:12px">(örnek yanıt)</span>
            </div>
        </div>
        <div style="padding:13px 14px;border-top: 1px solid var(--border,var(--border));background: var(--surface,var(--surface));display:flex;gap:8px">
            <input type="text" placeholder="Yakında aktif olacak…" disabled
                   style="flex:1;padding:12px 16px;font-size:15px;border: 1.5px solid var(--border,var(--border));border-radius: 22px;background: var(--bg-soft,#f6f6f1);color: var(--text-muted,var(--text-muted))">
            <button disabled style="background: #d8d6ba;color: #7a7a55;border: none;border-radius: 22px;padding:0 20px;font-weight:800;cursor:not-allowed">➤</button>
        </div>
    </div>

    {{-- Bilgi / güvenlik kutusu --}}
    <div style="display:flex;flex-direction:column;gap:16px">
        <div style="background: var(--surface,var(--surface));border: 1px solid var(--border,var(--border));border-radius: 16px;padding:18px">
            <div style="font-weight:800;margin-bottom:12px;color: var(--text,var(--text))">🔒 Güvenlik ilkeleri</div>
            <ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.9;color: var(--text-secondary,var(--text-secondary))">
                <li>Her işlemde <b>onay</b> sorulur</li>
                <li>Uygulamadan önce <b>önizleme</b> gösterilir</li>
                <li><b>Otomatik mod yok</b> — hep sen tetiklersin</li>
                <li>Yapılan her işlem <b>geri alınabilir</b></li>
                <li>Şimdilik <b>mail / SMS gönderemez</b></li>
            </ul>
        </div>
        <div style="background: rgba(139,92,246,.08);border: 1px solid rgba(139,92,246,.25);border-radius: 16px;padding:18px">
            <div style="font-weight:800;margin-bottom:8px;color: #7c3aed">Nasıl çalışacak?</div>
            <div style="font-size:13px;line-height:1.7;color: var(--text-secondary,var(--text-secondary))">
                Komut yaz → asistan anlar → <b>ne yapacağını önizler</b> → sen <b>onaylarsın</b> → uygular →
                <a href="{{ route('admin.islem-gecmisi.index') }}" style="color: #7c3aed;font-weight:600">İşlem Geçmişi</a>'nden geri alınabilir.
            </div>
        </div>
        <div style="text-align:center;font-size:12px;color: var(--text-muted,var(--text-muted))">
            Altyapı (İşlem Geçmişi + Geri-Al) hazır. Sohbet motoru yakında aktifleşecek.
        </div>
    </div>
</div>

<style>
    .ai-ex{background: var(--surface,var(--surface));border: 1px solid var(--border,#e2e2d6);border-radius: 999px;padding:6px 12px;font-size:12.5px;color: var(--text-muted,var(--text-muted))}
    @media(max-width:900px){ .ai-chat + div, [style*="grid-template-columns:1fr 340px"]{grid-template-columns:1fr !important} }
    body.theme-dark .ai-ex{background: #242424;border-color: #333}
</style>

@endsection
