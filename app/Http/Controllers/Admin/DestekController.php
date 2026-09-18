<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DestekController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->query('period', 'all');

        $now = now();
        $thisMonth = [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()];

        $defaultPick = $now->copy()->subMonth();
        $pickMonth = (int) $request->query('month', $defaultPick->month);
        $pickYear = (int) $request->query('year', $defaultPick->year);
        if ($pickMonth < 1 || $pickMonth > 12) { $pickMonth = $defaultPick->month; }
        if ($pickYear < 2000 || $pickYear > (int) $now->year + 1) { $pickYear = $defaultPick->year; }

        $pickedRef = \Carbon\Carbon::create($pickYear, $pickMonth, 1);
        $pickedRange = [$pickedRef->copy()->startOfMonth(), $pickedRef->copy()->endOfMonth()];

        $baseRoot = fn() => DB::table('destek')->where('ustid', 0);

        $stats = [
            'this_month' => [
                'total'    => (clone $baseRoot())->whereTarihBetween('tarih', $thisMonth[0], $thisMonth[1])->count(),
                'bekleyen' => (clone $baseRoot())->where('durum', 0)->whereTarihBetween('tarih', $thisMonth[0], $thisMonth[1])->count(),
                'cozulmus' => (clone $baseRoot())->where('durum', 1)->whereTarihBetween('tarih', $thisMonth[0], $thisMonth[1])->count(),
            ],
            'picked' => [
                'total'    => (clone $baseRoot())->whereTarihBetween('tarih', $pickedRange[0], $pickedRange[1])->count(),
                'bekleyen' => (clone $baseRoot())->where('durum', 0)->whereTarihBetween('tarih', $pickedRange[0], $pickedRange[1])->count(),
                'cozulmus' => (clone $baseRoot())->where('durum', 1)->whereTarihBetween('tarih', $pickedRange[0], $pickedRange[1])->count(),
                'month'    => $pickMonth,
                'year'     => $pickYear,
            ],
            'all' => [
                'total'    => (clone $baseRoot())->count(),
                'bekleyen' => (clone $baseRoot())->where('durum', 0)->count(),
                'cozulmus' => (clone $baseRoot())->where('durum', 1)->count(),
            ],
        ];

        $mevcutYillar = DB::table('destek')
            ->where('ustid', 0)
            ->whereNotNull('tarih')
            ->selectRaw('DISTINCT YEAR(tarih) as y')
            ->orderByDesc('y')
            ->pluck('y')
            ->map(fn($y) => (int) $y)
            ->toArray();
        if (!in_array((int) $now->year, $mevcutYillar, true)) {
            array_unshift($mevcutYillar, (int) $now->year);
        }
        if (!in_array($pickYear, $mevcutYillar, true)) {
            $mevcutYillar[] = $pickYear;
            rsort($mevcutYillar);
        }

        $query = DB::table('destek')
            ->join('uyeler', 'destek.uyeid', '=', 'uyeler.id')
            ->select('destek.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->where('destek.ustid', 0);

        if ($filter === 'this_month') {
            $query->whereTarihBetween('destek.tarih', $thisMonth[0], $thisMonth[1]);
        } elseif ($filter === 'picked') {
            $query->whereTarihBetween('destek.tarih', $pickedRange[0], $pickedRange[1]);
        }

        // Kart filtresi: ?durum=bekleyen | cozulmus
        $durumKart = $request->query('durum');
        if ($durumKart === 'bekleyen') {
            $query->where('destek.durum', 0);
        } elseif ($durumKart === 'cozulmus') {
            $query->where('destek.durum', 1);
        }

        $destekler = $query->orderBy('destek.son_cevap', 'desc')
            ->paginate(20)
            ->appends($request->query());

        $bekleyen_sayisi = $stats['all']['bekleyen'];
        $cozulmus_sayisi = $stats['all']['cozulmus'];

        return view('admin.destek.index', compact(
            'destekler', 'bekleyen_sayisi', 'cozulmus_sayisi',
            'stats', 'filter', 'pickMonth', 'pickYear', 'mevcutYillar'
        ));
    }
    
    public function detay($id)
    {
        $destek = DB::table('destek')
            ->join('uyeler', 'destek.uyeid', '=', 'uyeler.id')
            ->select('destek.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
            ->where('destek.id', $id)
            ->first();

        if (!$destek) {
            return redirect()->route('admin.destek.index')->with('error', 'Destek talebi bulunamadı!');
        }

        // Alt mesajları getir
        $cevaplar = DB::table('destek')
            ->leftJoin('uyeler', 'destek.uyeid', '=', 'uyeler.id')
            ->select('destek.*', 'uyeler.ad', 'uyeler.soyad')
            ->where('destek.ustid', $id)
            ->orderBy('destek.tarih', 'asc')
            ->get();

        // Admin atama için yönetici listesi (patron + çalışan)
        $yoneticiler = DB::table('yoneticiler')
            ->where('durum', 1)
            ->whereIn('rol', [1, 2])
            ->select('id', 'adi', 'kullaniciadi')
            ->orderBy('adi')
            ->get();

        // Atanan admin bilgisi
        $atananAdmin = null;
        if ($destek->atanan_admin_id) {
            $atananAdmin = DB::table('yoneticiler')->where('id', $destek->atanan_admin_id)->first();
        }

        return view('admin.destek.detay', compact('destek', 'cevaplar', 'yoneticiler', 'atananAdmin'));
    }

    
    
     public function cevapla(Request $request, $id)
    {
        $request->validate([
            'mesaj' => 'required|string',
            'dosya' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,rar|max:10240',
        ]);
 
        $destek = DB::table('destek')->where('id', $id)->first();
 
        if (!$destek) {
            return redirect()->route('admin.destek.index')->with('error', 'Destek talebi bulunamadı!');
        }
 
        // Dosya yükleme (varsa)
        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $file = $request->file('dosya');
 
            $uploadPath = public_path('tema/uploads/destek');
            if (!is_dir($uploadPath)) {
                @mkdir($uploadPath, 0755, true);
            }
 
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $dosyaYolu = 'tema/uploads/destek/' . $fileName;
        }
 
        // Schema-aware: yeni kolonlar varsa kullan
        $kolonlar = Schema::getColumnListing('destek');
        $cevapData = [
            'uyeid'     => 0,
            'baslik'    => 'RE: ' . $destek->baslik,
            'hizmet'    => $destek->hizmet ?? null,
            'mesaj'     => $request->mesaj,
            'oncelik'   => $destek->oncelik ?? 'Normal',
            'durum'     => 1,
            'ustid'     => $id,
            'dosya'     => $dosyaYolu,
            'tarih'     => now(),
            'son_cevap' => now(),
        ];
        if (in_array('gonderen_tip', $kolonlar)) $cevapData['gonderen_tip'] = 'admin';
        if (in_array('admin_id', $kolonlar))     $cevapData['admin_id']     = (int) (session('admin_id') ?? 0) ?: null;
 
        $cevapId = DB::table('destek')->insertGetId($cevapData);
 
        // Ana talebi güncelle
        DB::table('destek')->where('id', $id)->update([
            'son_cevap' => now(),
            'durum'     => 1,
        ]);
 
        // @mention parse + mail (eski özellik korundu)
        $this->mentionMailGonder($request->mesaj, $destek, $id);
 
        // YENİ: Müşteriye cevap bildirimi mail
        try {
            $musteri = DB::table('uyeler')->where('id', $destek->uyeid ?? 0)->first();
            $cevap = DB::table('destek')->where('id', $cevapId)->first();
            if ($musteri && $cevap) {
                \App\Services\DestekMailHelper::cevapAdmindenMusteriye($destek, $cevap, $musteri);
            }
        } catch (\Throwable $e) {
            \Log::warning('Cevap mail hatası: ' . $e->getMessage());
        }
 
        return redirect()->route('admin.destek.detay', $id)->with('success', 'Cevabınız gönderildi.');
    }

    /**
     * Mesajda @kullaniciadi geçen adminlere mail gönder
     */
    protected function mentionMailGonder(string $mesaj, $destek, int $id): void
    {
        if (!preg_match_all('/@([a-zA-Z0-9_\.]+)/', $mesaj, $matches)) return;
        $usernames = array_unique($matches[1]);
        if (!$usernames) return;
 
        $olusturanAdi = session('admin_adi', 'Admin');
        $mentioned = DB::table('yoneticiler')
            ->whereIn('kullaniciadi', $usernames)
            ->where('durum', 1)
            ->select('id', 'kullaniciadi', 'adi', 'email', 'eposta')->get();
 
        foreach ($mentioned as $m) {
            $to = $m->email ?: $m->eposta;
            if (!$to) continue;
            try {
                $url = url('/admin/destek/' . $id . '/detay');
                $subject = '🔔 ' . $olusturanAdi . ' sizi destek talebinde etiketledi';
                $html = '<h3>Merhaba ' . e($m->adi ?? $m->kullaniciadi) . ',</h3>'
                      . '<p><strong>' . e($olusturanAdi) . '</strong> seni <strong>"' . e($destek->baslik ?? '') . '"</strong> destek talebinde etiketledi.</p>'
                      . '<blockquote style="border-left:3px solid #facc15;padding:10px 14px;background:#fef9c3;color:#0f172a;border-radius:8px;">' . nl2br(e($mesaj)) . '</blockquote>'
                      . '<p><a href="' . $url . '" style="display:inline-block;padding:10px 18px;background:#facc15;color:#000;text-decoration:none;border-radius:8px;font-weight:700;">Talebi Gör →</a></p>';
                \App\Services\EmailNotificationService::send($to, $subject, $html);
            } catch (\Throwable $e) {
                \Log::warning('Destek mention mail gonderilemedi', ['to' => $to, 'err' => $e->getMessage()]);
            }
        }
    }
    
    public function durumDegistir($id, $durum)
    {
        DB::table('destek')->where('id', $id)->update([
            'durum' => $durum,
        ]);
 
        return redirect()->back()->with('success', 'Durum güncellendi.');
    }
 
    public function sil($id)
    {
        DB::table('destek')->where('ustid', $id)->delete();
        DB::table('destek')->where('id', $id)->delete();
 
        return redirect()->route('admin.destek.index')->with('success', 'Destek talebi silindi.');
    }
 
    public function olustur()
    {
        $uyeler = DB::table('uyeler')->where('durum', 1)
            ->select('id', 'ad', 'soyad', 'email')
            ->orderBy('ad')->limit(500)->get();
 
        return view('admin.destek.olustur', compact('uyeler'));
    }
 
    public function olusturPost(Request $request)
    {
        $validated = $request->validate([
            'uyeid'    => 'required|integer',
            'baslik'   => 'required|string|max:255',
            'departman' => 'nullable|string|max:100',
            'oncelik'  => 'nullable|string|max:50',
            'mesaj'    => 'required|string',
        ]);
 
        // Schema-aware: yeni kolonlar varsa kullan
        $kolonlar = Schema::getColumnListing('destek');
        $talepData = [
            'uyeid'      => $validated['uyeid'],
            'baslik'     => $validated['baslik'],
            'departman'  => $validated['departman'] ?? 'Genel',
            'oncelik'    => $validated['oncelik'] ?? 'Normal',
            'mesaj'      => $validated['mesaj'],
            'durum'      => 0,
            'ip'         => $request->ip(),
            'tarih'      => date('Y-m-d H:i:s'),
            'son_tarih'  => date('Y-m-d H:i:s'),
        ];
        if (in_array('gonderen_tip', $kolonlar)) $talepData['gonderen_tip'] = 'admin';
        if (in_array('admin_id', $kolonlar))     $talepData['admin_id']     = (int) (session('admin_id') ?? 0) ?: null;
 
        $id = DB::table('destek')->insertGetId($talepData);
 
        // YENİ: Müşteriye yeni talep bildirimi mail
        try {
            $musteri = DB::table('uyeler')->where('id', $validated['uyeid'])->first();
            $talep   = DB::table('destek')->where('id', $id)->first();
            if ($musteri && $talep) {
                \App\Services\DestekMailHelper::yeniTalepAdmindenMusteriye($talep, $musteri);
            }
        } catch (\Throwable $e) {
            \Log::warning('Yeni talep mail hatası: ' . $e->getMessage());
        }
 
        return redirect()->route('admin.destek.detay', $id)
            ->with('success', 'Destek talebi müşteri adına oluşturuldu.');
    }
     public function cevapSil($id, $cevapId)
    {
        $cevap = \Illuminate\Support\Facades\DB::table('destek')
            ->where('id', $cevapId)
            ->where('ustid', $id)
            ->first();
 
        if (!$cevap) {
            return redirect()->route('admin.destek.detay', $id)
                ->with('error', 'Mesaj bulunamadı.');
        }
 
        // Eğer mesajın bir dosyası varsa, dosyayı da sil
        if (!empty($cevap->dosya)) {
            $abs = public_path($cevap->dosya);
            if (file_exists($abs)) {
                @unlink($abs);
            }
        }
 
        \Illuminate\Support\Facades\DB::table('destek')->where('id', $cevapId)->delete();
 
        return redirect()->route('admin.destek.detay', $id)
            ->with('success', 'Mesaj silindi.');
    }
    
    public function ata(Request $request, $id)
    {
        $request->validate(['admin_id' => 'nullable|integer']);
 
        $yeniAdminId = $request->admin_id ?: null;
 
        DB::table('destek')->where('id', $id)->update([
            'atanan_admin_id' => $yeniAdminId,
        ]);
 
        // YENİ: Atanan admine mail bildirim
        if ($yeniAdminId) {
            try {
                $talep = DB::table('destek')
                    ->join('uyeler', 'destek.uyeid', '=', 'uyeler.id')
                    ->select('destek.*', 'uyeler.ad', 'uyeler.soyad', 'uyeler.email')
                    ->where('destek.id', $id)
                    ->first();
 
                $atananAdmin = DB::table('yoneticiler')->where('id', $yeniAdminId)->first();
 
                if ($talep && $atananAdmin) {
                    // Not: $talep query'si uyeler ile join'li, içinde ad/soyad/email var
                    // Bu yüzden 3. parametre olarak da $talep geçiyoruz
                    \App\Services\DestekMailHelper::talepAtandi($talep, $atananAdmin, $talep);
                }
            } catch (\Throwable $e) {
                \Log::warning('Atama mail hatası: ' . $e->getMessage());
            }
        }
 
        return redirect()->route('admin.destek.detay', $id)->with('success', 'Admin ataması güncellendi.');
    }

    /**
     * AJAX POLLING: Bu talebin verilen ID'den sonraki yeni mesajlarını döner.
     */
    public function yeniMesajlar(Request $request, $id)
    {
        $afterId = (int) $request->input('after_id', 0);

        $talep = DB::table('destek')->where('id', $id)->first();
        if (!$talep) {
            return response()->json(['ok' => false, 'msg' => 'Talep bulunamadı'], 404);
        }

        $cevaplar = DB::table('destek')
            ->leftJoin('uyeler', 'destek.uyeid', '=', 'uyeler.id')
            ->select('destek.*', 'uyeler.ad', 'uyeler.soyad')
            ->where('destek.ustid', $id)
            ->where('destek.id', '>', $afterId)
            ->orderBy('destek.id', 'asc')
            ->get();

        $mesajlar = [];
        foreach ($cevaplar as $c) {
            $isAdmin = false;
            if (isset($c->gonderen_tip) && $c->gonderen_tip === 'admin') {
                $isAdmin = true;
            } elseif (!empty($c->admin_id) && (int)$c->admin_id > 0) {
                $isAdmin = true;
            } elseif (!empty($c->uyeid) && (int)$c->uyeid > 0) {
                $isAdmin = false;
            }

            $cName = null;
            if ($isAdmin) {
                if (!empty($c->admin_id)) {
                    try {
                        $admin = DB::table('yoneticiler')->where('id', $c->admin_id)->first();
                        if ($admin) $cName = $admin->adi ?? $admin->kullaniciadi ?? 'Admin';
                    } catch (\Throwable $e) {}
                }
                $cName = $cName ?: 'Admin';
            } else {
                $cName = trim(($c->ad ?? '') . ' ' . ($c->soyad ?? '')) ?: 'Müşteri';
            }

            $tarihFmt = '';
            if (!empty($c->tarih)) {
                try { $tarihFmt = \Carbon\Carbon::parse($c->tarih)->format('d.m.Y H:i'); }
                catch (\Throwable $e) { $tarihFmt = (string) $c->tarih; }
            }

            $mesajlar[] = [
                'id'      => (int) $c->id,
                'isAdmin' => $isAdmin,
                'ad'      => $cName,
                'mesaj'   => (string) ($c->mesaj ?? ''),
                'tarih'   => $tarihFmt,
                'dosya'   => $c->dosya ?? null,
            ];
        }

        return response()->json([
            'ok'       => true,
            'mesajlar' => $mesajlar,
            'durum'    => (int) ($talep->durum ?? 0),
        ]);
    }

    /**
     * AJAX: Admin cevap gönderme (JSON döner).
     */
    public function cevaplaAjax(Request $request, $id)
    {
        $request->validate([
            'mesaj' => 'required|string',
            'dosya' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,rar|max:10240',
        ]);

        $destek = DB::table('destek')->where('id', $id)->first();
        if (!$destek) {
            return response()->json(['ok' => false, 'msg' => 'Talep bulunamadı'], 404);
        }

        $dosyaYolu = null;
        if ($request->hasFile('dosya')) {
            $file = $request->file('dosya');
            $uploadPath = public_path('tema/uploads/destek');
            if (!is_dir($uploadPath)) @mkdir($uploadPath, 0755, true);
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move($uploadPath, $fileName);
            $dosyaYolu = 'tema/uploads/destek/' . $fileName;
        }

        $kolonlar = Schema::getColumnListing('destek');
        $cevapData = [
            'uyeid'     => 0,
            'baslik'    => 'RE: ' . $destek->baslik,
            'hizmet'    => $destek->hizmet ?? null,
            'mesaj'     => $request->mesaj,
            'oncelik'   => $destek->oncelik ?? 'Normal',
            'durum'     => 1,
            'ustid'     => $id,
            'dosya'     => $dosyaYolu,
            'tarih'     => now(),
            'son_cevap' => now(),
        ];
        if (in_array('gonderen_tip', $kolonlar)) $cevapData['gonderen_tip'] = 'admin';
        if (in_array('admin_id', $kolonlar))     $cevapData['admin_id']     = (int) (session('admin_id') ?? 0) ?: null;

        $cevapId = DB::table('destek')->insertGetId($cevapData);

        DB::table('destek')->where('id', $id)->update([
            'son_cevap' => now(),
            'durum'     => 1,
        ]);

        // @mention parse + mail (mevcut özellik)
        try { $this->mentionMailGonder($request->mesaj, $destek, $id); } catch (\Throwable $e) {}

        // Müşteriye cevap bildirimi mail (cevapla ile ayni - AJAX'ta eksikti)
        try {
            $musteri = DB::table('uyeler')->where('id', $destek->uyeid ?? 0)->first();
            $cevap = DB::table('destek')->where('id', $cevapId)->first();
            if ($musteri && $cevap) {
                \App\Services\DestekMailHelper::cevapAdmindenMusteriye($destek, $cevap, $musteri);
            }
        } catch (\Throwable $e) {
            \Log::warning('Cevap (ajax) mail hatası: ' . $e->getMessage());
        }

        return response()->json([
            'ok' => true,
            'id' => (int) $cevapId,
            'msg' => 'Cevap gönderildi.',
        ]);
    }
}