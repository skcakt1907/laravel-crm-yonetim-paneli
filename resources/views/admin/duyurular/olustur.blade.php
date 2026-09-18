@extends('admin._layout')

@section('title', 'Yeni Duyuru')

@push('head')
<style>
    .dyo-head{display:flex;align-items:center;gap:10px;margin-bottom:24px}
    .dyo-head h1{font-size:24px;font-weight:800;margin:0;display:flex;align-items:center;gap:10px}
    .dyo-back{display:inline-flex;align-items:center;gap:6px;color: var(--text-secondary);text-decoration:none;font-size:13.5px;font-weight:600}
    .dyo-back:hover{color: var(--brand-hover)}
    .dyo-card{background: var(--surface);border: 1px solid var(--border);border-radius: 16px;padding:26px;max-width:760px}
    .dyo-group{margin-bottom:20px}
    .dyo-label{display:block;font-weight:700;font-size:13.5px;color: var(--text-secondary);margin-bottom:8px}.dyo-input,.dyo-textarea{width:100%;border: 1.5px solid var(--border);border-radius: 11px;padding:11px 14px;font-size:14px;
        font-family:inherit;color: var(--text);transition:.15s;box-sizing:border-box;background: var(--surface);}
    .dyo-input:focus,.dyo-textarea:focus{outline: none;border-color: var(--brand);box-shadow:0 0 0 3px rgba(184,182,46,.12)}.dyo-textarea{resize:vertical;min-height:130px;line-height:1.6;background: var(--surface);color: var(--text);}
    .dyo-radio-row{display:flex;gap:12px;flex-wrap:wrap}
    .dyo-radio{flex:1;min-width:200px;border: 1.5px solid var(--border);border-radius: 12px;padding:14px 16px;cursor:pointer;
        display:flex;align-items:flex-start;gap:11px;transition:.15s}
    .dyo-radio:hover{border-color: #cdcb6a}
    .dyo-radio.sel{border-color: var(--brand);background: #fafbf0}.dyo-radio input{margin-top:3px;background: var(--surface);color: var(--text);}
    .dyo-radio-t{font-weight:700;font-size:14px;color: var(--text)}
    .dyo-radio-d{font-size:12.5px;color: var(--text-muted);margin-top:2px}
    .dyo-alicilar{margin-top:14px;border: 1.5px solid var(--border);border-radius: 12px;padding:14px;max-height:280px;overflow-y:auto}
    .dyo-ali-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
    .dyo-ali-head a{font-size:12.5px;color: var(--brand-hover);font-weight:600;cursor:pointer;text-decoration:none}
    .dyo-ali-item{display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius: 9px;cursor:pointer;transition:.12s}
    .dyo-ali-item:hover{background: var(--bg-subtle)}
    .dyo-ali-name{font-size:14px;color: var(--text-secondary);font-weight:600}
    .dyo-ali-mail{font-size:12px;color: var(--text-muted)}
    .dyo-check{display:flex;align-items:center;gap:10px;padding:13px 16px;background: #fafbf0;border: 1.5px solid #ece9b8;border-radius: 12px;cursor:pointer}
    .dyo-check input{width:18px;height:18px;accent-color:#b8b62e}
    .dyo-check-t{font-weight:700;font-size:14px;color: var(--text)}
    .dyo-check-d{font-size:12.5px;color: var(--text-muted)}
    .dyo-submit{display:inline-flex;align-items:center;gap:8px;background: var(--brand);color: var(--text);font-weight:700;
        padding:13px 26px;border-radius: 12px;border: none;cursor:pointer;font-size:15px;transition:.2s}
    .dyo-submit:hover{background: var(--brand-hover)}
    .dyo-err{background: rgba(239,68,68,.1);border: 1px solid rgba(239,68,68,.3);color: var(--danger);padding:12px 16px;border-radius: 12px;margin-bottom:18px;font-size:14px}
    .dyo-hidden{display:none}
</style>
@endpush

@section('content')
<div class="dyo-head">
    <h1><i data-lucide="megaphone"></i> Yeni Duyuru</h1>
</div>
<a href="{{ route('admin.duyurular.index') }}" class="dyo-back" style="margin-bottom:18px"><i data-lucide="arrow-left" style="width:16px;height:16px"></i> Duyurulara dön</a>

@if($errors->any())
<div class="dyo-err">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif
@if(session('error'))
<div class="dyo-err">{{ session('error') }}</div>
@endif

<form action="{{ route('admin.duyurular.store') }}" method="POST" class="dyo-card" id="duyuruForm">
    @csrf

    <div class="dyo-group">
        <label class="dyo-label">Başlık</label>
        <input type="text" name="baslik" class="dyo-input" value="{{ old('baslik') }}" required placeholder="Duyuru başlığı" autofocus>
    </div>

    <div class="dyo-group">
        <label class="dyo-label">İçerik</label>
        <textarea name="icerik" class="dyo-textarea" required placeholder="Duyuru metnini yazın...">{{ old('icerik') }}</textarea>
    </div>

    <div class="dyo-group">
        <label class="dyo-label">Kime gönderilsin?</label>
        <div class="dyo-radio-row">
            <label class="dyo-radio sel" id="radioTum">
                <input type="radio" name="hedef_tip" value="tum" checked>
                <span>
                    <span class="dyo-radio-t">Tüm Yöneticiler</span>
                    <span class="dyo-radio-d">Aktif tüm yöneticilere gönderilir</span>
                </span>
            </label>
            <label class="dyo-radio" id="radioSecili">
                <input type="radio" name="hedef_tip" value="secili">
                <span>
                    <span class="dyo-radio-t">Seçili Yöneticiler</span>
                    <span class="dyo-radio-d">Sadece seçtiklerin görür</span>
                </span>
            </label>
        </div>

        <div class="dyo-alicilar dyo-hidden" id="aliciBox">
            <div class="dyo-ali-head">
                <span style="font-size:12.5px;font-weight:600;color: var(--text-secondary)">Yöneticiler ({{ $yoneticiler->count() }})</span>
                <a onclick="tumunuSec(this)" data-state="0">Tümünü seç</a>
            </div>
            @forelse($yoneticiler as $y)
            <label class="dyo-ali-item">
                <input type="checkbox" name="alicilar[]" value="{{ $y->id }}" style="accent-color:#b8b62e">
                <span>
                    <span class="dyo-ali-name">{{ $y->adi ?? $y->kullaniciadi ?? ('#'.$y->id) }}</span>
                    @if(!empty($y->email))<span class="dyo-ali-mail"> · {{ $y->email }}</span>@endif
                </span>
            </label>
            @empty
            <p style="color: var(--text-muted);font-size:13px;text-align:center;padding:10px">Yönetici bulunamadı.</p>
            @endforelse
        </div>
    </div>

    <div class="dyo-group">
        <label class="dyo-check">
            <input type="checkbox" name="mail_gonder" value="1" checked>
            <span>
                <span class="dyo-check-t">E-posta da gönder</span>
                <span class="dyo-check-d">Uygulama-içi bildirimin yanı sıra e-posta ile de iletilsin</span>
            </span>
        </label>
    </div>

    <button type="submit" class="dyo-submit"><i data-lucide="send" style="width:18px;height:18px"></i> Duyuruyu Gönder</button>
</form>
@endsection

@push('scripts')
<script>
if(window.lucide)lucide.createIcons();

const radioTum = document.getElementById('radioTum');
const radioSecili = document.getElementById('radioSecili');
const aliciBox = document.getElementById('aliciBox');

function syncHedef(){
    const secili = document.querySelector('input[name="hedef_tip"]:checked').value === 'secili';
    aliciBox.classList.toggle('dyo-hidden', !secili);
    radioTum.classList.toggle('sel', !secili);
    radioSecili.classList.toggle('sel', secili);
}
document.querySelectorAll('input[name="hedef_tip"]').forEach(r => r.addEventListener('change', syncHedef));
syncHedef();

function tumunuSec(el){
    const checks = aliciBox.querySelectorAll('input[type="checkbox"]');
    const yeni = el.dataset.state === '0';
    checks.forEach(c => c.checked = yeni);
    el.dataset.state = yeni ? '1' : '0';
    el.textContent = yeni ? 'Seçimi kaldır' : 'Tümünü seç';
}

// Seçili modda en az 1 alıcı kontrolü
document.getElementById('duyuruForm').addEventListener('submit', function(e){
    const secili = document.querySelector('input[name="hedef_tip"]:checked').value === 'secili';
    if(secili){
        const adet = aliciBox.querySelectorAll('input[type="checkbox"]:checked').length;
        if(adet === 0){
            e.preventDefault();
            alert('En az bir yönetici seçmelisiniz.');
        }
    }
});
</script>
@endpush