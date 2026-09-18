@extends('admin._layout')

@section('title', 'Avans Talepleri')

@push('head')
<style>
    .iz-head{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:22px}
    .iz-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .iz-head h1 .rozet{background: var(--danger);color: var(--text-inverse);font-size:12px;font-weight:700;padding:2px 9px;border-radius: 20px}
    .iz-btn{display:inline-flex;align-items:center;gap:8px;background: var(--brand);color: var(--text);font-weight:700;padding:11px 20px;border-radius: 12px;text-decoration:none;font-size:14px;transition:.2s}
    .iz-btn:hover{background: var(--brand-hover)}
    .iz-tabs{display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap}
    .iz-tab{padding:8px 16px;border-radius: 20px;font-size:13.5px;font-weight:600;text-decoration:none;border: 1.5px solid var(--border);color: var(--text-secondary)}
    .iz-tab.aktif{background: var(--brand);border-color: var(--brand);color: var(--text)}
    .iz-card{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;overflow:hidden}
    .iz-table{width:100%;border-collapse: collapse}
    .iz-table th{text-align:left;font-size:11.5px;font-weight:700;color: var(--text-muted);text-transform:uppercase;letter-spacing:.4px;padding:13px 16px;border-bottom: 1px solid var(--border);background: var(--surface)}
    .iz-table td{padding:14px 16px;border-bottom: 1px solid var(--border);font-size:13.5px;color: var(--text-secondary);vertical-align:middle}
    .iz-table tr:last-child td{border-bottom: none}
    .iz-table tr:hover td{background: var(--bg-subtle)}
    .iz-tutar{font-weight:800;color: var(--text);font-size:14.5px}
    .iz-d{font-size:11.5px;font-weight:600;padding:3px 10px;border-radius: 20px;white-space:nowrap}
    .iz-d.bekliyor{background: rgba(245,158,11,.14);color: var(--warning)}
    .iz-d.onaylandi{background: rgba(16,185,129,.12);color: var(--success)}
    .iz-d.reddedildi{background: rgba(239,68,68,.12);color: var(--danger)}
    .iz-act{display:inline-flex;align-items:center;gap:5px;font-size:12.5px;font-weight:700;padding:6px 12px;border-radius: 9px;border: none;cursor:pointer;text-decoration:none}
    .iz-onay{background: rgba(16,185,129,.14);color: var(--success)}
    .iz-onay:hover{background: rgba(16,185,129,.24)}
    .iz-red{background: rgba(239,68,68,.12);color: var(--danger)}
    .iz-red:hover{background: rgba(239,68,68,.22)}
    .iz-del{width:32px;height:32px;border-radius: 8px;border: 1px solid var(--border);background: var(--surface);color: var(--text-secondary);display:inline-flex;align-items:center;justify-content:center;cursor:pointer}
    .iz-del:hover{border-color: var(--danger);color: var(--danger)}
    .iz-empty{padding:50px;text-align:center;color: var(--text-muted)}
    .iz-modal{display:none;position:fixed;inset:0;background: rgba(0,0,0,.45);z-index:9999;align-items:center;justify-content:center}
    .iz-modal.acik{display:flex}
    .iz-modal-box{background: var(--surface);border-radius: 16px;padding:24px;max-width:420px;width:90%}
    .iz-modal-box h4{margin:0 0 14px;font-size:17px;font-weight:700}.iz-modal-box textarea{width:100%;border: 1.5px solid var(--border);border-radius: 10px;padding:10px;font-family:inherit;font-size:14px;box-sizing:border-box;min-height:80px;background: var(--surface);color: var(--text);}
    .iz-modal-act{display:flex;gap:10px;justify-content:flex-end;margin-top:16px}
</style>
@endpush

@section('content')
<div class="iz-head">
    <h1><i data-lucide="banknote"></i> Avans Talepleri @if($onayYetkisi && $bekleyenSayi > 0)<span class="rozet">{{ $bekleyenSayi }} bekliyor</span>@endif</h1>
    <a href="{{ route('admin.hrm.avans.olustur') }}" class="iz-btn"><i data-lucide="plus" style="width:18px;height:18px"></i> Avans Talebi Oluştur</a>
</div>

@if(session('success'))<div style="background: rgba(16,185,129,.1);border: 1px solid rgba(16,185,129,.3);color: var(--success);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('success') }}</div>@endif
@if(session('error'))<div style="background: rgba(239,68,68,.1);border: 1px solid rgba(239,68,68,.3);color: var(--danger);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px">{{ session('error') }}</div>@endif

<div class="iz-tabs">
    <a href="{{ route('admin.hrm.avans.index') }}" class="iz-tab {{ !$durum ? 'aktif' : '' }}">Tümü</a>
    <a href="{{ route('admin.hrm.avans.index', ['durum'=>'bekliyor']) }}" class="iz-tab {{ $durum==='bekliyor' ? 'aktif' : '' }}">Bekleyen</a>
    <a href="{{ route('admin.hrm.avans.index', ['durum'=>'onaylandi']) }}" class="iz-tab {{ $durum==='onaylandi' ? 'aktif' : '' }}">Onaylanan</a>
    <a href="{{ route('admin.hrm.avans.index', ['durum'=>'reddedildi']) }}" class="iz-tab {{ $durum==='reddedildi' ? 'aktif' : '' }}">Reddedilen</a>
</div>

<div class="iz-card">
    <table class="iz-table">
        <thead>
            <tr>
                @if($onayYetkisi)<th>Personel</th>@endif
                <th>Tutar</th>
                <th>Açıklama</th>
                <th>Talep Tarihi</th>
                <th>Durum</th>
                <th style="text-align:right">İşlem</th>
            </tr>
        </thead>
        <tbody>
            @forelse($talepler as $t)
            <tr>
                @if($onayYetkisi)<td><strong>{{ $t->personel_adi ?? ('#'.$t->yonetici_id) }}</strong></td>@endif
                <td><span class="iz-tutar">₺{{ number_format($t->tutar, 2, ',', '.') }}</span></td>
                <td style="max-width:280px;color: var(--text-secondary)">{{ \Illuminate\Support\Str::limit($t->aciklama ?? '—', 70) }}</td>
                <td>{{ $t->created_at ? \Carbon\Carbon::parse($t->created_at)->format('d.m.Y H:i') : '—' }}</td>
                <td>
                    <span class="iz-d {{ $t->durum }}">{{ ['bekliyor'=>'Bekliyor','onaylandi'=>'Onaylandı','reddedildi'=>'Reddedildi'][$t->durum] ?? $t->durum }}</span>
                    @if($t->durum === 'reddedildi' && !empty($t->red_nedeni))<div style="font-size:11.5px;color: var(--text-muted);margin-top:3px">{{ $t->red_nedeni }}</div>@endif
                    @if($t->durum === 'onaylandi' && !empty($t->onaylayan_adi))<div style="font-size:11.5px;color: var(--text-muted);margin-top:3px">Onaylayan: {{ $t->onaylayan_adi }}</div>@endif
                </td>
                <td style="text-align:right;white-space:nowrap">
                    @if($onayYetkisi && $t->durum === 'bekliyor')
                        <form action="{{ route('admin.hrm.avans.onayla', $t->id) }}" method="POST" style="display:inline">
                            @csrf
                            <button type="submit" class="iz-act iz-onay"><i data-lucide="check" style="width:14px;height:14px"></i> Onayla</button>
                        </form>
                        <button type="button" class="iz-act iz-red" onclick="redAc({{ $t->id }})"><i data-lucide="x" style="width:14px;height:14px"></i> Reddet</button>
                    @endif
                    <form action="{{ route('admin.hrm.avans.sil', $t->id) }}" method="POST" onsubmit="return confirm('Talep silinsin mi?')" style="display:inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="iz-del"><i data-lucide="trash-2" style="width:15px;height:15px"></i></button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="{{ $onayYetkisi ? 6 : 5 }}"><div class="iz-empty"><i data-lucide="wallet" style="width:42px;height:42px;opacity:.4;margin-bottom:8px"></i><p>Avans talebi bulunmuyor.</p></div></td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($talepler instanceof \Illuminate\Pagination\LengthAwarePaginator && $talepler->hasPages())
<div style="margin-top:18px">{{ $talepler->links() }}</div>
@endif

{{-- Red nedeni modalı --}}
<div class="iz-modal" id="redModal">
    <div class="iz-modal-box">
        <h4>Avans Talebini Reddet</h4>
        <form id="redForm" method="POST">
            @csrf
            <textarea name="red_nedeni" placeholder="Red nedeni (isteğe bağlı)..."></textarea>
            <div class="iz-modal-act">
                <button type="button" class="iz-act" style="background: var(--bg-subtle);color: var(--text-secondary)" onclick="redKapat()">Vazgeç</button>
                <button type="submit" class="iz-act iz-red">Reddet</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
if(window.lucide)lucide.createIcons();
function redAc(id){
    var f = document.getElementById('redForm');
    f.action = "{{ url('admin/hrm/avans') }}/" + id + "/reddet";
    document.getElementById('redModal').classList.add('acik');
}
function redKapat(){ document.getElementById('redModal').classList.remove('acik'); }
document.getElementById('redModal').addEventListener('click', function(e){ if(e.target===this) redKapat(); });
</script>
@endpush