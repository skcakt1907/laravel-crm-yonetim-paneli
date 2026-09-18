{{-- Müşteri Görevleri sekmesi --}}
@php
    $musteriGorevleri = \App\Models\CRM\Task::where('musteri_id', $customer->id)
        ->orderByDesc('created_at')
        ->get();

    $gorevDurumlar = [
        'beklemede'  => ['label' => '📝 Beklemede',    'class' => 'badge-warning'],
        'devam'      => ['label' => '⏳ Devam Ediyor', 'class' => 'badge-info'],
        'tamamlandi' => ['label' => '✅ Tamamlandı',   'class' => 'badge-success'],
    ];

    $gorevSorumlular = \App\Models\Yonetici::pluck('adi', 'id');

    $gorevYeniUrl = \Illuminate\Support\Facades\Route::has('admin.crm.gorevler.create')
        ? route('admin.crm.gorevler.create') . '?musteri_id=' . $customer->id
        : null;
    $gorevEditVar = \Illuminate\Support\Facades\Route::has('admin.crm.gorevler.edit');
    $gorevShowVar = \Illuminate\Support\Facades\Route::has('admin.crm.gorevler.show');
@endphp

<div class="section">
    <div class="section-header" style="display:flex;align-items:center;justify-content:space-between;gap:10px">
        <h3 class="section-title" style="margin:0">✅ Görevler
            <span class="badge badge-brand" style="font-size:12px;vertical-align:middle">{{ $musteriGorevleri->count() }}</span>
        </h3>
        @if($gorevYeniUrl)
        <a href="{{ $gorevYeniUrl }}" class="btn btn-primary btn-sm">
            <i data-lucide="plus"></i>
            <span>Yeni Görev</span>
        </a>
        @endif
    </div>

    @if($musteriGorevleri->isEmpty())
        <div class="empty-state" style="padding:32px 16px;text-align:center">
            <i data-lucide="list-checks" class="empty-state-icon"></i>
            <h4>Görev yok</h4>
            <p style="color:var(--text-muted)">Bu müşteriye henüz görev açılmamış.</p>
        </div>
    @else
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Konu</th>
                        <th>Departman</th>
                        <th>Sorumlu</th>
                        <th>Son Tarih</th>
                        <th>Durum</th>
                        @if($gorevEditVar)<th class="text-right">İşlem</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($musteriGorevleri as $g)
                        @php
                            $gd = $gorevDurumlar[$g->durum ?? 'beklemede'] ?? $gorevDurumlar['beklemede'];
                            $sonTarih = null; $gecikti = false;
                            if (!empty($g->son_tarih)) {
                                try {
                                    $sonTarih = \Carbon\Carbon::parse($g->son_tarih);
                                    $gecikti  = ($g->durum ?? '') !== 'tamamlandi' && $sonTarih->isPast();
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        @php $gorevDetayUrl = $gorevShowVar ? route('admin.crm.gorevler.show', $g->id) : null; @endphp
                        <tr @if($gorevDetayUrl) style="cursor:pointer" onclick="window.location='{{ $gorevDetayUrl }}'" title="Görev detayını aç" @endif>
                            <td>
                                <strong>{{ $g->konu ?? $g->baslik ?? '—' }}</strong>
                                @if(!empty($g->aciklama))
                                    <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px">
                                        {{ \Illuminate\Support\Str::limit($g->aciklama, 80) }}
                                    </div>
                                @endif
                            </td>
                            <td style="font-size:12px;color:var(--text-secondary)">{{ $g->departman ? \App\Models\CRM\Task::departmanLabel($g->departman) : '—' }}</td>
                            <td style="font-size:12px">{{ $gorevSorumlular[$g->atanan_id] ?? 'Atanmamış' }}</td>
                            <td style="font-size:12px;white-space:nowrap">
                                @if($sonTarih)
                                    {{ $sonTarih->format('d.m.Y') }}
                                    @if($gecikti)
                                        <span class="badge badge-danger" style="font-size:10px;margin-left:4px">Gecikti</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td><span class="badge {{ $gd['class'] }}" style="font-size:10.5px">{{ $gd['label'] }}</span></td>
                            @if($gorevEditVar)
                            <td class="text-right">
                                <a href="{{ route('admin.crm.gorevler.edit', $g->id) }}" class="table-action" title="Düzenle" onclick="event.stopPropagation()">
                                    <i data-lucide="edit-2"></i>
                                </a>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>