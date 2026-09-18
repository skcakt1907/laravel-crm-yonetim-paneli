@extends('admin._layout')

@section('title', $board->baslik . ' — Arşiv')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.index') }}">Kanban</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.kanban.show', $board->id) }}">{{ $board->baslik }}</a>
    <span class="sep">/</span>
    <span class="current">Arşiv</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🗂️ Arşivlenmiş Kartlar</h1>
        <div class="page-subtitle">{{ $board->baslik }} · {{ $cards->count() }} arşivlenmiş kart</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.crm.kanban.show', $board->id) }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Panoya Dön</span>
        </a>
    </div>
</div>

@if($cards->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="archive" class="empty-state-icon"></i>
            <h4>Arşivlenmiş kart yok</h4>
            <p>Bu panoda henüz arşivlenmiş bir kart bulunmuyor.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Kart Başlığı</th>
                        <th>Liste</th>
                        <th>Öncelik</th>
                        <th>Son Tarih</th>
                        <th>Arşiv Tarihi</th>
                        <th class="text-right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cards as $card)
                        @php
                            $oncelikLabel = ['dusuk'=>'Düşük','normal'=>'Normal','yuksek'=>'Yüksek','acil'=>'Acil'][$card->oncelik ?? 'normal'] ?? 'Normal';
                            $oncelikClass = ['dusuk'=>'badge-success','normal'=>'badge-brand','yuksek'=>'badge-warning','acil'=>'badge-danger'][$card->oncelik ?? 'normal'] ?? 'badge-brand';
                            $oncelikIcon  = ['dusuk'=>'🟢','normal'=>'🟡','yuksek'=>'🟠','acil'=>'🔴'][$card->oncelik ?? 'normal'] ?? '🟡';
                            $listeName    = $card->list->baslik ?? $card->list->adi ?? '—';
                        @endphp
                        <tr id="arsiv-row-{{ $card->id }}">
                            <td>
                                <div style="font-weight:600;font-size:13px">{{ $card->baslik ?? '—' }}</div>
                                @if(!empty($card->aciklama))
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ Str::limit($card->aciklama, 70) }}</div>
                                @endif
                            </td>
                            <td><span class="badge badge-neutral">{{ $listeName }}</span></td>
                            <td><span class="badge {{ $oncelikClass }}">{{ $oncelikIcon }} {{ $oncelikLabel }}</span></td>
                            <td style="font-size:12px;color:var(--text-secondary)">
                                @if($card->son_tarih)
                                    {{ \Carbon\Carbon::parse($card->son_tarih)->format('d.m.Y') }}
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary)">
                                @if($card->deleted_at)
                                    {{ \Carbon\Carbon::parse($card->deleted_at)->format('d.m.Y H:i') }}
                                @elseif($card->updated_at)
                                    {{ \Carbon\Carbon::parse($card->updated_at)->format('d.m.Y H:i') }}
                                @else
                                    <span style="color:var(--text-muted)">—</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="table-actions">
                                    <button type="button" onclick="ykRestoreCard({{ $card->id }})" class="table-action" style="color:var(--success)" title="Geri Yükle">
                                        <i data-lucide="undo-2"></i>
                                    </button>
                                    <button type="button" onclick="ykDeleteCardForever({{ $card->id }})" class="table-action" style="color:var(--danger)" title="Kalıcı Sil">
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
@endif

<script>
const _CSRF = document.querySelector('meta[name="csrf-token"]').content;
const _BOARD_BASE = @json(url('admin/crm/kanban/'.$board->id));

function ykRestoreCard(cardId) {
    if (!confirm('Kart geri yüklensin mi?')) return;
    fetch(_BOARD_BASE + '/cards/' + cardId + '/restore', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        if (d.success) {
            document.getElementById('arsiv-row-' + cardId)?.remove();
        } else {
            alert('Hata: ' + (d.message || 'Geri yüklenemedi'));
        }
    }).catch(err => alert('Hata: ' + err.message));
}

function ykDeleteCardForever(cardId) {
    if (!confirm('Kart KALICI olarak silinsin mi?\n\nBu işlem GERİ ALINAMAZ!')) return;
    fetch(_BOARD_BASE + '/cards/' + cardId + '/force-delete', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' }
    }).then(r => r.json()).then(d => {
        if (d.success) {
            document.getElementById('arsiv-row-' + cardId)?.remove();
        } else {
            alert('Hata: ' + (d.message || 'Silinemedi'));
        }
    }).catch(err => alert('Hata: ' + err.message));
}
</script>

@endsection