@extends('admin._layout')

@section('title', 'Ticket #' . $ticket->id)

@push('head')
<style>
    .ticket-chat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
    .msg-row { padding: 14px 18px; border-bottom: 1px solid var(--border); }
    .msg-row:last-child { border-bottom: none; }
    .msg-row.from-admin { background: var(--brand-soft); }
    .msg-row.from-other { background: var(--bg-subtle); }
    .msg-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
    .msg-avatar { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; flex-shrink: 0; }
    .msg-avatar.me { background: linear-gradient(135deg, var(--brand), #8a8a1f); color: #000; }
    .msg-avatar.other { background: linear-gradient(135deg, #3b82f6, #1e40af); color: #fff; }
    .msg-name { font-weight: 700; font-size: 13px; color: var(--text); }
    .msg-time { font-size: 11px; color: var(--text-muted); margin-left: auto; }
    .msg-body { font-size: 13.5px; line-height: 1.7; color: var(--text); white-space: pre-wrap; word-break: break-word; }
</style>
@endpush

@section('content')

@php
    $tDurum = (int)($ticket->durum ?? 0);
    $durumMap = [
        0 => ['label' => 'Bekliyor', 'class' => 'badge-warning', 'icon' => '⏳', 'mini' => 'warning'],
        1 => ['label' => 'Çözüldü', 'class' => 'badge-success', 'icon' => '✅', 'mini' => 'success'],
        2 => ['label' => 'İptal', 'class' => 'badge-danger', 'icon' => '❌', 'mini' => 'danger'],
    ];
    $dur = $durumMap[$tDurum] ?? $durumMap[0];

    $oncelikMap = [
        'acil' => 'badge-danger', 'yuksek' => 'badge-warning',
        'normal' => 'badge-neutral', 'dusuk' => 'badge-success',
    ];
    $oncCls = $oncelikMap[$ticket->oncelik ?? 'normal'] ?? 'badge-neutral';

    $myId = session('admin_id');

    $tarihFmt = null;
    if (!empty($ticket->tarih)) {
        try { $tarihFmt = \Carbon\Carbon::parse($ticket->tarih)->format('d.m.Y H:i'); } catch (\Throwable $e) {}
    }
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.tickets.index') }}">Ticketlar</a>
    <span class="sep">/</span>
    <span class="current">#{{ $ticket->id }}</span>
</div>

<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg, #f59e0b, #d97706); font-size:28px">🎫</div>
    <div class="profile-info">
        <h1 class="profile-name">{{ \Illuminate\Support\Str::limit($ticket->baslik ?? 'Ticket #'.$ticket->id, 60) }}</h1>
        <div class="profile-meta">
            <span class="meta-item"><i data-lucide="hash"></i> #{{ $ticket->id }}</span>
            <span class="meta-item"><i data-lucide="user-plus"></i> {{ $ticket->olusturan_adi ?? '—' }}</span>
            <span class="meta-item"><i data-lucide="user-check"></i> {{ ($atananlar ?? collect())->isNotEmpty() ? $atananlar->implode(', ') : ($ticket->atanan_adi ?? '—') }}</span>
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            <span class="badge {{ $dur['class'] }}" style="font-size:12px;padding:5px 10px">{{ $dur['icon'] }} {{ $dur['label'] }}</span>
            <span class="badge {{ $oncCls }}" style="font-size:12px;padding:5px 10px">⚡ {{ ucfirst($ticket->oncelik ?? 'normal') }}</span>
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat {{ $dur['mini'] }}">
        <div class="mini-stat-icon">{{ $dur['icon'] }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Durum</div>
            <div class="mini-stat-value" style="font-size:17px">{{ $dur['label'] }}</div>
        </div>
    </div>
    <div class="mini-stat info">
        <div class="mini-stat-icon">💬</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Mesaj Sayısı</div>
            <div class="mini-stat-value">{{ ($cevaplar->count() ?? 0) + 1 }}</div>
        </div>
    </div>
    <div class="mini-stat info">
        <div class="mini-stat-icon">📅</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Açılış</div>
            <div class="mini-stat-value" style="font-size:15px">{{ $tarihFmt ?? '—' }}</div>
        </div>
    </div>
    <div class="mini-stat warning">
        <div class="mini-stat-icon">⚡</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Öncelik</div>
            <div class="mini-stat-value" style="font-size:17px">{{ ucfirst($ticket->oncelik ?? 'normal') }}</div>
        </div>
    </div>
</div>

<div class="form-grid" style="margin-top:20px">
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="messages-square"></i>
                <span>Mesajlaşma</span>
            </div>

            <div class="ticket-chat">
                {{-- Ana mesaj --}}
                @php
                    $isMyInitial = ($ticket->olusturan_id ?? 0) == $myId;
                    $oluName = $ticket->olusturan_adi ?? '—';
                @endphp
                <div class="msg-row {{ $isMyInitial ? 'from-admin' : 'from-other' }}">
                    <div class="msg-head">
                        <div class="msg-avatar {{ $isMyInitial ? 'me' : 'other' }}">
                            {{ strtoupper(mb_substr($oluName, 0, 1, 'UTF-8')) }}
                        </div>
                        <div class="msg-name">{{ $oluName }}</div>
                        <div class="msg-time">{{ $tarihFmt ?? '—' }}</div>
                    </div>
                    <div class="msg-body">{{ $ticket->mesaj ?? '' }}</div>
                </div>

                {{-- Cevaplar --}}
                @foreach($cevaplar as $c)
                    @php
                        $isMine = ($c->yazan_id ?? 0) == $myId;
                        $yName = $c->yazan_adi ?? '—';
                        $cTarih = null;
                        if (!empty($c->tarih)) {
                            try { $cTarih = \Carbon\Carbon::parse($c->tarih)->format('d.m.Y H:i'); } catch (\Throwable $e) {}
                        }
                    @endphp
                    <div class="msg-row {{ $isMine ? 'from-admin' : 'from-other' }}">
                        <div class="msg-head">
                            <div class="msg-avatar {{ $isMine ? 'me' : 'other' }}">
                                {{ strtoupper(mb_substr($yName, 0, 1, 'UTF-8')) }}
                            </div>
                            <div class="msg-name">{{ $yName }}</div>
                            <div class="msg-time">{{ $cTarih ?? '—' }}</div>
                        </div>
                        <div class="msg-body">{{ $c->mesaj ?? '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        @if($tDurum !== 2)
            <div class="section">
                <div class="section-title">
                    <i data-lucide="reply"></i>
                    <span>Cevap Gönder</span>
                </div>

                <form action="{{ route('admin.tickets.cevapla', $ticket->id) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <textarea name="mesaj" rows="5" required class="form-textarea" placeholder="Cevabınızı yazın..."></textarea>
                    </div>
                    <div style="display:flex;justify-content:flex-end">
                        <button type="submit" class="btn btn-primary">
                            <i data-lucide="send"></i>
                            <span>Gönder</span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
    </div>

    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Ticket Bilgisi</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Ticket ID</span>
                    <span class="val">#{{ $ticket->id }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Oluşturan</span>
                    <span class="val">{{ $ticket->olusturan_adi ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">{{ ($atananlar ?? collect())->count() > 1 ? 'Atananlar' : 'Atanan' }}</span>
                    <span class="val">
                        @if(($atananlar ?? collect())->isNotEmpty())
                            {{ $atananlar->implode(', ') }}
                        @else
                            {{ $ticket->atanan_adi ?? 'Atanmamış' }}
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Öncelik</span>
                    <span class="val"><span class="badge {{ $oncCls }}">{{ ucfirst($ticket->oncelik ?? 'normal') }}</span></span>
                </div>
                <div class="info-item">
                    <span class="lbl">Açılış</span>
                    <span class="val">{{ $tarihFmt ?? '—' }}</span>
                </div>
            </div>
        </div>

        {{-- ATAMAYI DÜZENLE — yalnızca patron veya ticket'ı açan kişi görür.
             Gönderilen liste mevcut atananların yerine geçer. --}}
        @if($atamaDuzenlenebilir ?? false)
        <div class="section">
            <div class="section-title">
                <i data-lucide="user-cog"></i>
                <span>Atamayı Düzenle</span>
            </div>
            <form action="{{ route('admin.tickets.atananlar', $ticket->id) }}" method="POST">
                @csrf
                <div class="atanan-liste">
                    @foreach($calisanlar ?? [] as $c)
                        @php $sec = in_array((int) $c->id, $atananIdler ?? [], true); @endphp
                        <label class="atanan-secim">
                            <input type="checkbox" name="atanan_ids[]" value="{{ $c->id }}" @checked($sec)>
                            <span>{{ $c->adi ?: $c->kullaniciadi }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-primary btn-sm" style="width:100%;margin-top:10px">
                    <i data-lucide="check"></i> <span>Atamayı Kaydet</span>
                </button>
                <small class="form-help" style="display:block;margin-top:6px">
                    İşaretli olanlar ticket'a atanır. Tümünü kaldırırsan ticket atanmamış olur.
                </small>
            </form>
        </div>

        <style>
            .atanan-liste{display:flex;flex-wrap:wrap;gap:7px}
            .atanan-secim{display:inline-flex;align-items:center;gap:6px;cursor:pointer;
                border:1.5px solid var(--border,#e2e5ea);border-radius:8px;
                padding:6px 10px;font-size:12.5px;transition:.15s;user-select:none}
            .atanan-secim:hover{border-color:var(--brand,#8a8a1f)}
            .atanan-secim input{margin:0;cursor:pointer}
            .atanan-secim:has(input:checked){border-color:var(--brand,#8a8a1f);
                background:rgba(138,138,31,.08);font-weight:600}
        </style>
        @endif

        <div class="section">
            <div class="section-title">
                <i data-lucide="flag"></i>
                <span>Durum Değiştir</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
                @if($tDurum !== 0)
                    <form action="{{ route('admin.tickets.durum', ['id' => $ticket->id, 'durum' => 0]) }}" method="POST" style="margin:0">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center">
                            <i data-lucide="clock"></i>
                            <span>Beklemeye Al</span>
                        </button>
                    </form>
                @endif
                @if($tDurum !== 1)
                    <form action="{{ route('admin.tickets.durum', ['id' => $ticket->id, 'durum' => 1]) }}" method="POST" style="margin:0" onsubmit="return confirm('Çözüldü?');">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" style="width:100%;justify-content:center;background:linear-gradient(135deg,#10b981,#059669)">
                            <i data-lucide="check-circle"></i>
                            <span>Çözüldü</span>
                        </button>
                    </form>
                @endif
                @if($tDurum !== 2)
                    <form action="{{ route('admin.tickets.durum', ['id' => $ticket->id, 'durum' => 2]) }}" method="POST" style="margin:0" onsubmit="return confirm('İptal?');">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;color:var(--danger)">
                            <i data-lucide="x-circle"></i>
                            <span>İptal Et</span>
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.tickets.sil', $ticket->id) }}" method="POST" onsubmit="return confirm('Ticket silinsin mi?');" style="margin:0;margin-top:8px">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" style="width:100%;justify-content:center">
                        <i data-lucide="trash-2"></i>
                        <span>Sil</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection