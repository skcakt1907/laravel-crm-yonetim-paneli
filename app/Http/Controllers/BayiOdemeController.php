<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BayiOdemeController extends Controller
{
    public function show(string $token)
    {
        $talep = DB::table('crm_musteri_teklifleri')->where('token', $token)->first();
        abort_if(!$talep, 404);

        $customer = DB::table('crm_customers')->where('id', $talep->customer_id)->first();
        $ayarlar  = DB::table('ayarlar')->first();

        return view('bayi-odeme.show', compact('talep', 'customer', 'ayarlar'));
    }

    public function markPaid(Request $request, string $token)
    {
        $talep = DB::table('crm_musteri_teklifleri')->where('token', $token)->first();
        abort_if(!$talep, 404);

        if (!in_array($talep->durum, ['gonderildi', 'bekliyor'])) {
            return back()->with('error', 'Bu talep için zaten bir işlem yapılmış.');
        }

        DB::table('crm_musteri_teklifleri')->where('id', $talep->id)->update([
            'durum'      => 'odendi',
            'paid_at'    => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('bayi.odeme.tesekkur', $token);
    }

    public function tesekkur(string $token)
    {
        $talep = DB::table('crm_musteri_teklifleri')->where('token', $token)->first();
        abort_if(!$talep, 404);
        return view('bayi-odeme.tesekkur', compact('talep'));
    }

    /**
     * Müşteri teklifi kabul ediyor (henüz ödeme yapmadı, sadece okeyleyor).
     * Durum: gonderildi/bekliyor → kabul_edildi
     */
    public function kabul(Request $request, string $token)
    {
        $talep = DB::table('crm_musteri_teklifleri')->where('token', $token)->first();
        abort_if(!$talep, 404);

        if (!in_array($talep->durum, ['gonderildi', 'bekliyor'])) {
            return back()->with('error', 'Bu teklif için zaten bir işlem yapılmış.');
        }

        DB::table('crm_musteri_teklifleri')->where('id', $talep->id)->update([
            'durum'      => 'kabul_edildi',
            'updated_at' => now(),
        ]);

        // Admin'e bildirim maili (opsiyonel, sessiz başarısızlık)
        try {
            $customer = DB::table('crm_customers')->where('id', $talep->customer_id)->first();
            $ayar     = DB::table('ayarlar')->first();
            $toEmail  = $ayar->mail_adresi ?? $ayar->firma_email ?? null;
            if ($toEmail) {
                $subject = '✅ Teklif kabul edildi: ' . ($talep->paket_adi ?? '');
                $html    = '<h3>Bir teklifiniz kabul edildi</h3>'
                         . '<p><strong>Müşteri:</strong> ' . e($customer->adi ?? '—') . ' (' . e($customer->email ?? '—') . ')</p>'
                         . '<p><strong>Paket:</strong> ' . e($talep->paket_adi) . '</p>'
                         . '<p><strong>Tutar:</strong> ₺' . number_format((float)$talep->tutar, 2, ',', '.') . '</p>'
                         . '<p><strong>Ödeme yöntemi:</strong> ' . e($talep->odeme_yontemi) . '</p>'
                         . '<p>Müşteri ödeme adımına geçecek. Süreci CRM panelinden takip edebilirsiniz.</p>';
                \App\Services\EmailNotificationService::send($toEmail, $subject, $html, true);
            }
        } catch (\Throwable $e) {
            \Log::warning('Teklif kabul mail hatası', ['err' => $e->getMessage()]);
        }

        return back()->with('success', '✅ Teklifi kabul ettiniz. Şimdi "Ödemeyi Yaptım" butonu ile ödeme bildiriminde bulunabilirsiniz.');
    }

    /**
     * Müşteri teklifi reddediyor (opsiyonel sebep girebilir).
     * Durum: → reddedildi
     */
    public function red(Request $request, string $token)
    {
        $talep = DB::table('crm_musteri_teklifleri')->where('token', $token)->first();
        abort_if(!$talep, 404);

        if (in_array($talep->durum, ['odendi', 'onaylandi', 'reddedildi', 'iptal'])) {
            return back()->with('error', 'Bu teklif için zaten bir işlem yapılmış.');
        }

        $sebep = trim((string) $request->input('red_sebep', ''));

        $updateData = [
            'durum'      => 'reddedildi',
            'updated_at' => now(),
        ];

        // red_sebep kolonu varsa kaydet, yoksa mesaj alanına ekle
        if (\Illuminate\Support\Facades\Schema::hasColumn('crm_musteri_teklifleri', 'red_sebep')) {
            $updateData['red_sebep'] = $sebep ?: null;
        } elseif (\Illuminate\Support\Facades\Schema::hasColumn('crm_musteri_teklifleri', 'red_aciklama')) {
            $updateData['red_aciklama'] = $sebep ?: null;
        }

        DB::table('crm_musteri_teklifleri')->where('id', $talep->id)->update($updateData);

        // Admin'e bildirim
        try {
            $customer = DB::table('crm_customers')->where('id', $talep->customer_id)->first();
            $ayar     = DB::table('ayarlar')->first();
            $toEmail  = $ayar->mail_adresi ?? $ayar->firma_email ?? null;
            if ($toEmail) {
                $subject = '❌ Teklif reddedildi: ' . ($talep->paket_adi ?? '');
                $html    = '<h3>Bir teklifiniz reddedildi</h3>'
                         . '<p><strong>Müşteri:</strong> ' . e($customer->adi ?? '—') . ' (' . e($customer->email ?? '—') . ')</p>'
                         . '<p><strong>Paket:</strong> ' . e($talep->paket_adi) . '</p>'
                         . '<p><strong>Tutar:</strong> ₺' . number_format((float)$talep->tutar, 2, ',', '.') . '</p>'
                         . ($sebep ? '<p><strong>Müşterinin sebebi:</strong></p><blockquote style="border-left:3px solid #ef4444;padding:10px 14px;background:#fef2f2">' . nl2br(e($sebep)) . '</blockquote>' : '');
                \App\Services\EmailNotificationService::send($toEmail, $subject, $html, true);
            }
        } catch (\Throwable $e) {
            \Log::warning('Teklif red mail hatası', ['err' => $e->getMessage()]);
        }

        return back()->with('success', 'Teklifi reddettiniz. Geri bildiriminiz için teşekkürler.');
    }
}