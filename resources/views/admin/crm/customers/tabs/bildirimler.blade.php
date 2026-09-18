{{--
================================================================
CRM Müşteri Detay - GÖNDERİLEN BİLDİRİMLER TAB
================================================================
Konum: resources/views/admin/crm/customers/tabs/bildirimler.blade.php

Show blade'inde $tabs dizisine ekle:
    'bildirimler' => ['📧', 'Bildirimler'],

@switch içine ekle:
    @case('bildirimler') @include('admin.crm.customers.tabs.bildirimler') @break
================================================================
--}}

@php
    // Bildirimleri çek (musteri_bildirimleri tablosundan)
    $bildirimler = collect();
    if (\Illuminate\Support\Facades\Schema::hasTable('musteri_bildirimleri')) {
        try {
            // Hem uye_id hem crm_customer_id ile sorgu (müşteri her ikisi de olabilir)
            $bildirimler = \Illuminate\Support\Facades\DB::table('musteri_bildirimleri')
                ->where(function($q) use ($customer) {
                    // CRM müşteri ise crm_customer_id
                    $q->where('crm_customer_id', $customer->id);

                    // Eğer email eşleşen üye varsa onun id'si ile de
                    if (!empty($customer->email)) {
                        $eslesenUye = \Illuminate\Support\Facades\DB::table('uyeler')
                            ->where('email', $customer->email)
                            ->value('id');
                        if ($eslesenUye) {
                            $q->orWhere('uye_id', $eslesenUye);
                        }
                    }
                })
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        } catch (\Throwable $e) {}
    }

    $olayLabels = [
        'fatura_kesildi' => '🧾 Fatura Kesildi',
        'odeme_onaylandi' => '✅ Ödeme Onaylandı',
        'bakiye_yuklendi' => '💳 Bakiye Yüklendi',
        'sifre_degisti' => '🔒 Şifre Değişti',
        'hesap_engellendi' => '🚫 Hesap Engellendi',
        'hesap_aktif_edildi' => '✓ Hesap Aktif',
        'teklif_gonderildi' => '💼 Teklif Gönderildi',
        'hosting_alindi' => '🖥️ Hosting Aktif',
        'domain_alindi' => '🌐 Domain Aktif',
        'ticket_cevaplandi' => '🎫 Ticket Güncellendi',
        'hizmet_suresi_doluyor' => '⏰ Süre Uyarısı',
        'kampanya_bildirimi' => '🎉 Kampanya',
        'hosgeldin' => '👋 Hoşgeldin',
    ];

    $durumLabels = [
        'gonderildi' => ['Gönderildi', 'success'],
        'basarisiz' => ['Başarısız', 'danger'],
        'atlandi' => ['Atlandı', 'neutral'],
        'devre_disi' => ['İzin Yok', 'warning'],
    ];

    $kanalIcons = [
        'mail' => '📧',
        'sms' => '📱',
        'whatsapp' => '💬',
    ];
@endphp

<div class="section">
    <div class="section-title">
        <i data-lucide="mail"></i>
        <span>Gönderilen Bildirimler</span>
        <span style="margin-left:auto;font-size:11px;color:var(--text-muted);font-weight:400">
            Son 100 kayıt
        </span>
    </div>

    @if($bildirimler->isEmpty())
        <div class="empty-state" style="padding:32px 16px">
            <i data-lucide="inbox" class="empty-state-icon"></i>
            <h4>Henüz bildirim gönderilmemiş</h4>
            <p>Bu müşteriye sistem üzerinden gönderilen tüm bildirimler burada görünecek.</p>
        </div>
    @else
        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                <thead>
                    <tr style="background:var(--bg-subtle);text-align:left">
                        <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">Tarih</th>
                        <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">Kanal</th>
                        <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">Olay</th>
                        <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted)">Başlık / Adres</th>
                        <th style="padding:10px 12px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);text-align:center">Sonuç</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bildirimler as $b)
                        @php
                            $tar = null;
                            try { $tar = \Carbon\Carbon::parse($b->created_at)->format('d.m.Y H:i'); } catch(\Throwable $e) {}
                            $olayLabel = $olayLabels[$b->olay] ?? $b->olay;
                            [$durumText, $durumColor] = $durumLabels[$b->durum] ?? [$b->durum, 'neutral'];
                            $kanalIcon = $kanalIcons[$b->kanal] ?? '📨';
                        @endphp
                        <tr style="border-top:1px solid var(--border)">
                            <td style="padding:10px 12px;color:var(--text-muted);white-space:nowrap">
                                {{ $tar ?? '—' }}
                            </td>
                            <td style="padding:10px 12px">
                                <span title="{{ ucfirst($b->kanal) }}">{{ $kanalIcon }}</span>
                            </td>
                            <td style="padding:10px 12px">{{ $olayLabel }}</td>
                            <td style="padding:10px 12px">
                                <div style="font-weight:500">{{ $b->baslik ?? '—' }}</div>
                                @if($b->gonderilen_adres)
                                    <div style="font-size:11px;color:var(--text-muted)">{{ $b->gonderilen_adres }}</div>
                                @endif
                            </td>
                            <td style="padding:10px 12px;text-align:center">
                                <span class="badge badge-{{ $durumColor }}" title="{{ $b->hata_mesaji ?? '' }}">
                                    {{ $durumText }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>