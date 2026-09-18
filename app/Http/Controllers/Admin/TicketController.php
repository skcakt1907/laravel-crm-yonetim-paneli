<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $admin_id = session('admin_id');
        $admin_rol = session('admin_rol', 2);
        
        // Filtreleme parametreleri
        $filtre = $request->get('filtre', 'tumu'); // tumu, benim_olusturduklarim, bana_atananlar
        $durum_filtre = $request->get('durum', 'tumu'); // tumu, bekleyen, cozulmus, iptal
        
        // Patron ise TÜM ticket'ları göster
        if ($admin_rol == 1) {
            $query = DB::table('calisan_tickets');
            
            // Durum filtresi
            if ($durum_filtre != 'tumu') {
                $durum_map = ['bekleyen' => 0, 'cozulmus' => 1, 'iptal' => 2];
                if (isset($durum_map[$durum_filtre])) {
                    $query->where('durum', $durum_map[$durum_filtre]);
                }
            }
            
            $tickets = $this->atananlariEkle($query->orderBy('id', 'desc')->paginate(20));
            
            $bekleyen_sayisi = DB::table('calisan_tickets')->where('durum', 0)->count();
            $cozulmus_sayisi = DB::table('calisan_tickets')->where('durum', 1)->count();
            $iptal_sayisi = DB::table('calisan_tickets')->where('durum', 2)->count();
            $benim_olusturduklarim_sayisi = 0;
            $bana_atananlar_sayisi = 0;
        } else {
            // Çalışan (rol=2) ve Bayi (rol=3) ise SADECE kendisiyle ilgili ticket'ları göster
            $query = DB::table('calisan_tickets')
                ->where(function($q) use ($admin_id, $filtre) {
                    if ($filtre == 'benim_olusturduklarim') {
                        $q->where('olusturan_id', $admin_id);
                    } elseif ($filtre == 'bana_atananlar') {
                        $q->where($this->banaAtananKosulu($admin_id));
                    } else {
                        $q->where('olusturan_id', $admin_id)
                          ->orWhere($this->banaAtananKosulu($admin_id));
                    }
                });
            
            // Durum filtresi
            if ($durum_filtre != 'tumu') {
                $durum_map = ['bekleyen' => 0, 'cozulmus' => 1, 'iptal' => 2];
                if (isset($durum_map[$durum_filtre])) {
                    $query->where('durum', $durum_map[$durum_filtre]);
                }
            }
            
            $tickets = $this->atananlariEkle($query->orderBy('id', 'desc')->paginate(20));
            
            // İstatistikler
            $bekleyen_sayisi = DB::table('calisan_tickets')
                ->where(function($q) use ($admin_id) {
                    $q->where('olusturan_id', $admin_id)->orWhere($this->banaAtananKosulu($admin_id));
                })->where('durum', 0)->count();
                
            $cozulmus_sayisi = DB::table('calisan_tickets')
                ->where(function($q) use ($admin_id) {
                    $q->where('olusturan_id', $admin_id)->orWhere($this->banaAtananKosulu($admin_id));
                })->where('durum', 1)->count();
            
            $benim_olusturduklarim_sayisi = DB::table('calisan_tickets')
                ->where('olusturan_id', $admin_id)->count();
            
            $bana_atananlar_sayisi = DB::table('calisan_tickets')
                ->where($this->banaAtananKosulu($admin_id))->count();
            
            $iptal_sayisi = 0; // Çalışanlar için iptal sayısı gerekmiyor
        }
        
        return view('admin.tickets.index', compact(
            'tickets', 'bekleyen_sayisi', 'cozulmus_sayisi',
            'benim_olusturduklarim_sayisi', 'bana_atananlar_sayisi',
            'filtre', 'durum_filtre', 'iptal_sayisi'
        ));
    }
    
    public function olustur()
    {
        // Tüm yöneticileri getir (Patron, Çalışan ve Bayi)
        $calisanlar = DB::table('yoneticiler')
            ->whereIn('rol', [1, 2, 3])
            ->where('durum', 1)
            ->select('id', 'adi', 'kullaniciadi', 'rol')
            ->get();
        
        return view('admin.tickets.olustur', compact('calisanlar'));
    }
    
    /**
     * Listedeki ticket'lara "atananlar_metni" alanını ekler.
     *
     * Sayfadaki TÜM ticket'ların atananları TEK sorguda çekilir; ticket başına
     * ayrı sorgu atılmaz (20 kayıtlık sayfada 20 sorgu olurdu).
     */
    protected function atananlariEkle($tickets)
    {
        if (!Schema::hasTable('calisan_ticket_atananlar')) {
            return $tickets;
        }

        $idler = collect($tickets->items())->pluck('id');
        if ($idler->isEmpty()) {
            return $tickets;
        }

        $harita = DB::table('calisan_ticket_atananlar')
            ->whereIn('ticket_id', $idler)
            ->orderBy('id')
            ->get()
            ->groupBy('ticket_id')
            ->map(fn ($grup) => $grup->pluck('yonetici_adi')->filter()->implode(', '));

        foreach ($tickets as $t) {
            // Ara tabloda kayıt yoksa eski tek atamaya düşer
            $t->atananlar_metni = $harita[$t->id] ?? ($t->atanan_adi ?? null);
        }

        return $tickets;
    }

    /**
     * "Bana atanan" koşulu — hem eski tek atama kolonuna hem yeni ara tabloya bakar.
     *
     * Ticket'lar 01.09.2026'dan beri birden fazla kişiye atanabiliyor. Eski
     * kayıtlarda yalnızca calisan_tickets.atanan_id dolu; yeni kayıtlarda
     * atananların TAMAMI calisan_ticket_atananlar tablosunda. İkisine birden
     * bakılmazsa çoklu atanan ticket'lar "Bana Atananlar" listesinde çıkmaz.
     */
    protected function banaAtananKosulu(int $adminId): \Closure
    {
        return function ($q) use ($adminId) {
            $q->where('calisan_tickets.atanan_id', $adminId);

            if (Schema::hasTable('calisan_ticket_atananlar')) {
                $q->orWhereExists(function ($alt) use ($adminId) {
                    $alt->selectRaw('1')
                        ->from('calisan_ticket_atananlar')
                        ->whereColumn('calisan_ticket_atananlar.ticket_id', 'calisan_tickets.id')
                        ->where('calisan_ticket_atananlar.yonetici_id', $adminId);
                });
            }
        };
    }

    public function olusturPost(Request $request)
    {
        $validated = $request->validate([
            'baslik'      => 'required|string|max:255',
            'mesaj'       => 'required|string',
            'oncelik'     => 'required|in:dusuk,normal,yuksek,acil',
            // Birden fazla kişiye atanabilir (01.09.2026). Eski tek alanlı
            // form hâlâ çalışsın diye 'atanan_id' de kabul ediliyor.
            'atanan_ids'   => 'nullable|array',
            'atanan_ids.*' => 'integer|exists:yoneticiler,id',
            'atanan_id'    => 'nullable|integer|exists:yoneticiler,id',
        ], [
            'atanan_ids.*.exists' => 'Seçilen kişilerden biri sistemde bulunamadı.',
        ]);

        // Seçilen kişiler: yeni çoklu alan, yoksa eski tek alan.
        $atananIdler = collect($validated['atanan_ids'] ?? [])
            ->when(!empty($validated['atanan_id']), fn ($c) => $c->push($validated['atanan_id']))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        // Adları tek sorguda al (kişi başına ayrı sorgu atmamak için)
        $adlar = $atananIdler->isEmpty()
            ? collect()
            : DB::table('yoneticiler')->whereIn('id', $atananIdler)->get()
                ->mapWithKeys(fn ($y) => [$y->id => $y->adi ?: $y->kullaniciadi]);

        // BİRİNCİL ATANAN = listedeki ilk kişi. calisan_tickets.atanan_id /
        // atanan_adi kolonları korunuyor ki bu kolonları okuyan mevcut kodlar
        // (gün sonu raporu, bildirim ekranı, eski kayıtlar) bozulmasın.
        $birincilId  = $atananIdler->first();
        $birincilAdi = $birincilId ? ($adlar[$birincilId] ?? null) : null;

        $dosyalar = [];

        $ticketId = DB::table('calisan_tickets')->insertGetId([
            'baslik' => $validated['baslik'],
            'mesaj' => $validated['mesaj'],
            'oncelik' => $validated['oncelik'],
            'olusturan_id' => session('admin_id'),
            'olusturan_adi' => session('admin_adi'),
            'atanan_id' => $birincilId,
            'atanan_adi' => $birincilAdi,
            'dosyalar' => !empty($dosyalar) ? json_encode($dosyalar) : null,
            'durum' => 0, // Bekliyor
            'tarih' => date('Y-m-d H:i:s'),
        ]);

        // Tüm atananları ara tabloya yaz
        if ($atananIdler->isNotEmpty() && Schema::hasTable('calisan_ticket_atananlar')) {
            DB::table('calisan_ticket_atananlar')->insertOrIgnore(
                $atananIdler->map(fn ($id) => [
                    'ticket_id'    => $ticketId,
                    'yonetici_id'  => $id,
                    'yonetici_adi' => $adlar[$id] ?? null,
                    'created_at'   => now(),
                ])->all()
            );
        }

        $kime = $atananIdler->isEmpty()
            ? 'ilgili kişiye'
            : $atananIdler->map(fn ($id) => $adlar[$id] ?? '?')->implode(', ');

        return redirect()->route('admin.tickets.index')
            ->with('success', 'Ticket başarıyla oluşturuldu ve ' . $kime . ' atandı.');
    }
    
    public function detay($id)
    {
        $admin_id = session('admin_id');
        $admin_rol = session('admin_rol', 2);
        
        $ticket = DB::table('calisan_tickets')->where('id', $id)->first();
        
        if (!$ticket) {
            return redirect()->route('admin.tickets.index')->with('error', 'Ticket bulunamadı.');
        }
        
        // Çalışan (rol=2) ve Bayi (rol=3) ise sadece kendi ticket'larına erişebilir
        if ($admin_rol != 1) {
            // Çoklu atama (01.09.2026): kişi ara tabloda da atanmış olabilir.
            // Yalnızca atanan_id'ye bakılırsa ikinci/üçüncü atanan kendi
            // ticket'ını açamaz ve "yetkiniz yok" uyarısı alır.
            $ataliMi = Schema::hasTable('calisan_ticket_atananlar')
                && DB::table('calisan_ticket_atananlar')
                    ->where('ticket_id', $ticket->id)
                    ->where('yonetici_id', $admin_id)
                    ->exists();

            if ($ticket->olusturan_id != $admin_id && $ticket->atanan_id != $admin_id && !$ataliMi) {
                return redirect()->route('admin.tickets.index')->with('error', 'Bu ticket\'ı görüntüleme yetkiniz yok.');
            }
        }
        
        // Cevapları getir
        $cevaplar = DB::table('calisan_ticket_cevaplar')
            ->where('ticket_id', $id)
            ->orderBy('id', 'asc')
            ->get();
        
        // Ticket'a atanan HERKES (çoklu atama). Ara tablo yoksa eski tek
        // atamadan üretilir — böylece güncelleme yüklenmemiş kurulumda da çalışır.
        $atananlar = Schema::hasTable('calisan_ticket_atananlar')
            ? DB::table('calisan_ticket_atananlar')
                ->where('ticket_id', $ticket->id)
                ->orderBy('id')
                ->pluck('yonetici_adi')
                ->filter()
                ->values()
            : collect();

        if ($atananlar->isEmpty() && !empty($ticket->atanan_adi)) {
            $atananlar = collect([$ticket->atanan_adi]);
        }

        // Atama düzenleme bölümü için: seçilebilir kişiler + seçili id'ler.
        // Yalnızca yetkisi olana (patron / ticket'ı açan) gönderilir.
        $atamaDuzenlenebilir = ($admin_rol == 1) || ($ticket->olusturan_id == $admin_id);

        $calisanlar = $atamaDuzenlenebilir
            ? DB::table('yoneticiler')->whereIn('rol', [1, 2, 3])->where('durum', 1)
                ->select('id', 'adi', 'kullaniciadi', 'rol')->orderBy('adi')->get()
            : collect();

        $atananIdler = Schema::hasTable('calisan_ticket_atananlar')
            ? DB::table('calisan_ticket_atananlar')->where('ticket_id', $ticket->id)
                ->pluck('yonetici_id')->map(fn ($x) => (int) $x)->all()
            : array_filter([(int) ($ticket->atanan_id ?? 0)]);

        return view('admin.tickets.detay', compact(
            'ticket', 'cevaplar', 'atananlar',
            'atamaDuzenlenebilir', 'calisanlar', 'atananIdler'
        ));
    }
    
    public function cevapla(Request $request, $id)
    {
        $validated = $request->validate([
            'mesaj' => 'required|string',
        ]);

        $dosyalar = [];
        
        DB::table('calisan_ticket_cevaplar')->insert([
            'ticket_id' => $id,
            'mesaj' => $validated['mesaj'],
            'yazan_id' => session('admin_id'),
            'yazan_adi' => session('admin_adi'),
            'dosyalar' => !empty($dosyalar) ? json_encode($dosyalar) : null,
            'tarih' => date('Y-m-d H:i:s'),
        ]);
        
        // Ticket'i güncelle
        DB::table('calisan_tickets')->where('id', $id)->update([
            'son_cevap_tarih' => date('Y-m-d H:i:s'),
        ]);
        
        return redirect()->route('admin.tickets.detay', $id)->with('success', 'Cevabınız eklendi.');
    }

    /**
     * Açılmış bir ticket'ın atananlarını değiştir (çoklu seçim).
     *
     * Gönderilen liste, mevcut atananların YERİNE geçer: listede olmayanlar
     * çıkarılır, yeni olanlar eklenir. Hiç kimse seçilmezse ticket
     * "atanmamış" duruma döner.
     *
     * YETKİ: yalnızca patron (rol=1) veya ticket'ı açan kişi değiştirebilir.
     * Atanan biri kendini listeden çıkarıp ticket'ı sahipsiz bırakamasın diye
     * atananlara bu yetki VERİLMEDİ.
     */
    public function atananlariGuncelle(Request $request, $id)
    {
        $adminId  = session('admin_id');
        $adminRol = session('admin_rol', 2);

        $ticket = DB::table('calisan_tickets')->where('id', $id)->first();

        if (!$ticket) {
            return redirect()->route('admin.tickets.index')->with('error', 'Ticket bulunamadı.');
        }

        if ($adminRol != 1 && $ticket->olusturan_id != $adminId) {
            return redirect()->route('admin.tickets.detay', $id)
                ->with('error', 'Atamayı yalnızca ticket\'ı açan kişi veya patron değiştirebilir.');
        }

        $validated = $request->validate([
            'atanan_ids'   => 'nullable|array',
            'atanan_ids.*' => 'integer|exists:yoneticiler,id',
        ], [
            'atanan_ids.*.exists' => 'Seçilen kişilerden biri sistemde bulunamadı.',
        ]);

        $idler = collect($validated['atanan_ids'] ?? [])
            ->map(fn ($x) => (int) $x)->unique()->values();

        $adlar = $idler->isEmpty()
            ? collect()
            : DB::table('yoneticiler')->whereIn('id', $idler)->get()
                ->mapWithKeys(fn ($y) => [$y->id => $y->adi ?: $y->kullaniciadi]);

        if (Schema::hasTable('calisan_ticket_atananlar')) {
            // Önce hepsini sil, sonra seçilenleri yaz — "listedekiler geçerli"
            // kuralı böylece tek adımda uygulanır (çıkarılanlar da temizlenir).
            DB::table('calisan_ticket_atananlar')->where('ticket_id', $ticket->id)->delete();

            if ($idler->isNotEmpty()) {
                DB::table('calisan_ticket_atananlar')->insertOrIgnore(
                    $idler->map(fn ($yid) => [
                        'ticket_id'    => $ticket->id,
                        'yonetici_id'  => $yid,
                        'yonetici_adi' => $adlar[$yid] ?? null,
                        'created_at'   => now(),
                    ])->all()
                );
            }
        }

        // Birincil atanan da güncellensin (bu kolonu okuyan raporlar için)
        $birincilId = $idler->first();
        DB::table('calisan_tickets')->where('id', $ticket->id)->update([
            'atanan_id'  => $birincilId,
            'atanan_adi' => $birincilId ? ($adlar[$birincilId] ?? null) : null,
            'updated_at' => now(),
        ]);

        $mesaj = $idler->isEmpty()
            ? 'Ticket artık kimseye atanmış değil.'
            : 'Atama güncellendi: ' . $idler->map(fn ($x) => $adlar[$x] ?? '?')->implode(', ');

        return redirect()->route('admin.tickets.detay', $id)->with('success', $mesaj);
    }

    public function durumDegistir($id, $durum)
    {
        $durumlar = [0 => 'Bekliyor', 1 => 'Çözüldü', 2 => 'İptal'];
        
        if (!isset($durumlar[$durum])) {
            return redirect()->back()->with('error', 'Geçersiz durum.');
        }
        
        DB::table('calisan_tickets')->where('id', $id)->update([
            'durum' => $durum,
        ]);
        
        return redirect()->back()->with('success', 'Ticket durumu "' . $durumlar[$durum] . '" olarak güncellendi.');
    }
    
    public function sil($id)
    {
        DB::table('calisan_tickets')->where('id', $id)->delete();
        DB::table('calisan_ticket_cevaplar')->where('ticket_id', $id)->delete();

        return redirect()->route('admin.tickets.index')->with('success', 'Ticket başarıyla silindi.');
    }

    /**
     * Bildirim listesi (yeni cevap gelen ticketlar)
     */
    public function bildirimler()
    {
        $adminId = session('admin_id');
        $adminRol = session('admin_rol');

        $query = DB::table('calisan_tickets')
            ->leftJoin('yoneticiler as olusturan', 'olusturan.id', '=', 'calisan_tickets.olusturan_id')
            ->leftJoin('yoneticiler as atanan', 'atanan.id', '=', 'calisan_tickets.atanan_id')
            ->select(
                'calisan_tickets.*',
                'olusturan.adi as olusturan_adi',
                'atanan.adi as atanan_adi'
            );

        // Patron değilse sadece kendi ticketlarını görsün
        if ($adminRol != 1) {
            $query->where(function ($q) use ($adminId) {
                $q->where('calisan_tickets.olusturan_id', $adminId)
                  ->orWhere($this->banaAtananKosulu($adminId));
            });
        }

        $bildirimler = $query->where('calisan_tickets.durum', 0)
            ->orderByDesc('calisan_tickets.updated_at')
            ->paginate(20);

        return view('admin.tickets.bildirimler', compact('bildirimler'));
    }

    /**
     * Bildirimi okundu olarak işaretle
     */
    public function bildirimOkundu($id)
    {
        DB::table('calisan_tickets')->where('id', $id)->update([
            'durum' => 1,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
