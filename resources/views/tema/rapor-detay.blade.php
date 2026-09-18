@extends('layouts.panel')

@section('page_title', 'Rapor Detayı')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">

    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-chart-line"></i>
            {{ __('messages.report_detail') }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <strong><a href="{{ route('raporlarim') }}">{{ __('messages.my_reports') }}</a></strong> /
            <span>Detay</span>
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div style="background:#fff;border-radius:14px;box-shadow:0 4px 14px rgba(0,0,0,.06);overflow:hidden;border:1px solid #e5e7eb;">

        {{-- Üst durum şeridi --}}
        @php
            $d = is_numeric($rapor->durum) ? (int)$rapor->durum : strtolower((string)$rapor->durum);
            $aktif    = in_array($d, [1, '1', 'aktif', 'tamamlandi'], true);
            $bekleyen = in_array($d, [0, '0', 'beklemede', 'yeni'], true);
        @endphp
        @if($aktif)
            <div style="padding:12px 18px;background:#d1fae5;color:#065f46;font-weight:600;font-size:14px;">
                {{ __('messages.report_active') }}
            </div>
        @elseif($bekleyen)
            <div style="padding:12px 18px;background:#fef3c7;color:#92400e;font-weight:600;font-size:14px;">
                {{ __('messages.report_pending') }}
            </div>
        @endif

        {{-- Header --}}
        <div style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);color:#fff;padding:30px 28px;">
            <div style="font-size:42px;line-height:1;margin-bottom:6px;">📊</div>
            <h2 style="font-size:22px;font-weight:700;margin:0 0 10px;line-height:1.3;">{{ $rapor->baslik ?? '—' }}</h2>
            <div style="font-size:13px;opacity:.9;">
                {{ __('messages.report_no') }} <strong>#{{ $rapor->id }}</strong>
                @if(!empty($rapor->tarih))
                    &nbsp;•&nbsp;
                    @php
                        try { $tarihFmt = \Carbon\Carbon::parse($rapor->tarih)->format('d.m.Y H:i'); }
                        catch (\Throwable $e) { $tarihFmt = $rapor->tarih; }
                    @endphp
                    {{ $tarihFmt }}
                @endif
            </div>
        </div>

        <div style="padding:28px;">

            {{-- Bilgi satırları --}}
            <div style="background:#f8fafc;padding:16px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;">
                @if(!empty($rapor->tutar) && is_numeric($rapor->tutar) && (float)$rapor->tutar > 0)
                <div style="padding:6px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">{{ __('messages.table_amount') }}</span>
                    <strong style="color:#1e293b;font-size:16px;">
                        ₺{{ number_format((float)$rapor->tutar, 2, ',', '.') }}
                    </strong>
                </div>
                @endif
                <div style="padding:6px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">{{ __('messages.table_status') }}</span>
                    <strong style="color:#1e293b;">
                        @if($aktif) ✓ Aktif
                        @elseif($bekleyen) ⏳ Beklemede
                        @else {{ $rapor->durum ?: '—' }}
                        @endif
                    </strong>
                </div>
                <div style="padding:6px 0;display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">{{ __('messages.created_at') }}</span>
                    <strong style="color:#1e293b;">
                        @if(!empty($rapor->tarih))
                            @php
                                try { $tarihTam = \Carbon\Carbon::parse($rapor->tarih)->format('d.m.Y H:i'); }
                                catch (\Throwable $e) { $tarihTam = $rapor->tarih; }
                            @endphp
                            {{ $tarihTam }}
                        @else
                            —
                        @endif
                    </strong>
                </div>
            </div>

            {{-- İçerik --}}
            @if(!empty($rapor->icerik))
                <h6 style="font-size:13px;text-transform:uppercase;letter-spacing:1px;color:#64748b;font-weight:700;margin-bottom:10px;">
                    {{ __('messages.report_content') }}
                </h6>
                <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:18px 20px;line-height:1.7;color:#1e293b;font-size:14px;margin-bottom:24px;">
                    {!! nl2br(e($rapor->icerik)) !!}
                </div>
            @endif

            {{-- Dosya indirme --}}
            @if(!empty($rapor->dosya))
                @php
                    $ext = strtolower(pathinfo($rapor->dosya, PATHINFO_EXTENSION));
                    $iconMap = [
                        'pdf'  => '📕', 'doc'  => '📘', 'docx' => '📘',
                        'xls'  => '📗', 'xlsx' => '📗', 'csv'  => '📗',
                        'ppt'  => '📙', 'pptx' => '📙',
                        'jpg'  => '🖼️', 'jpeg' => '🖼️', 'png'  => '🖼️', 'gif'  => '🖼️',
                        'zip'  => '🗜️', 'rar'  => '🗜️',
                    ];
                    $icon = $iconMap[$ext] ?? '📎';
                @endphp
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:18px 20px;display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                    <div style="font-size:36px;line-height:1;">{{ $icon }}</div>
                    <div style="flex:1;min-width:200px;">
                        <div style="font-weight:700;font-size:14px;color:#1e3a8a;margin-bottom:4px;">
                            {{ basename($rapor->dosya) }}
                        </div>
                        <div style="font-size:12px;color:#475569;">
                            Rapor dosyası — {{ strtoupper($ext) }}
                        </div>
                    </div>
                    <a href="{{ route('rapor.indir', $rapor->id) }}"
                       class="btn btn-primary"
                       style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);border:none;padding:12px 22px;border-radius:10px;font-weight:700;color:#fff;text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
                        <i class="fa fa-download"></i>
                        {{ __('messages.download_file') }}
                    </a>
                </div>
            @else
                <div style="background:#f1f5f9;border-radius:10px;padding:16px;text-align:center;color:#64748b;font-size:13px;">
                    Bu rapora ekli bir dosya bulunmuyor.
                </div>
            @endif

            <div style="margin-top:24px;text-align:center;">
                <a href="{{ route('raporlarim') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> {{ __('messages.all_my_reports') }}
                </a>
            </div>
        </div>
    </div>

</div>
@endsection