<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Customer;
use App\Models\CRM\Opportunity;
use App\Models\CRM\Task;
use App\Models\CRM\TaskMessage;
use App\Models\Yonetici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query = Task::query()
            ->with(['musteri', 'firsat', 'atanan', 'olusturan', 'atananlar']);

        if ($request->filled('durum')) {
            $query->where('durum', $request->string('durum'));
        }

        if ($request->filled('atanan_id')) {
            // Coklu atama: kisi birincil atanan OLMASA da listeye girsin
            $this->banaAtananKosulu($query, $request->integer('atanan_id'));
        }

        if ($request->filled('musteri_id')) {
            $query->where('musteri_id', $request->integer('musteri_id'));
        }

        if ($request->filled('firsat_id')) {
            $query->where('firsat_id', $request->integer('firsat_id'));
        }

        if ($request->boolean('yalnizca_geciken')) {
            $query->where('durum', '!=', 'tamamlandi')
                ->whereNotNull('son_tarih')
                ->where('son_tarih', '<', now());
        }

        $tasks = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $customers = Customer::orderBy('adi')->get(['id', 'adi']);
        $opportunities = Opportunity::orderBy('baslik')->get(['id', 'baslik']);
        // Görev sorumlusu = iç ekip; bayi (rol 3) + müşteri (rol 4) + pasifler hariç
        $yoneticiler = Yonetici::where('durum', 1)->whereNotIn('rol', [3, 4])
            ->orderBy('adi')->get(['id', 'adi', 'kullaniciadi', 'rol']);

        return view('admin.crm.tasks.index', compact('tasks', 'customers', 'opportunities', 'yoneticiler'));
    }

    public function create()
    {
        $customers = Customer::orderBy('adi')->get(['id', 'adi']);
        $opportunities = Opportunity::orderBy('baslik')->get(['id', 'baslik', 'musteri_id']);
        // Görev sorumlusu = iç ekip; bayi (rol 3) + müşteri (rol 4) + pasifler hariç
        $yoneticiler = Yonetici::where('durum', 1)->whereNotIn('rol', [3, 4])
            ->orderBy('adi')->get(['id', 'adi', 'kullaniciadi', 'rol']);

        return view('admin.crm.tasks.create', compact('customers', 'opportunities', 'yoneticiler'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'musteri_id' => 'nullable|integer|exists:crm_customers,id',
            'firsat_id' => 'nullable|integer|exists:crm_opportunities,id',
            'atanan_ids'   => 'nullable|array',
            'atanan_ids.*' => 'integer|exists:yoneticiler,id',
            'atanan_id' => 'nullable|integer|exists:yoneticiler,id',
            'konu' => 'required|string|max:180',
            'tip' => 'nullable|string|max:60',
            'departman' => 'nullable|string|max:60',
            'oncelik' => ['nullable', Rule::in(['dusuk', 'normal', 'yuksek'])],
            'durum' => ['nullable', Rule::in(['beklemede', 'devam', 'musteri_bekleniyor', 'tamamlandi'])],
            'son_tarih' => 'nullable|date',
            'aciklama' => 'nullable|string',
        ], [
            'atanan_ids.*.exists' => 'Seçilen kişilerden biri sistemde bulunamadı.',
        ]);

        $atananIdler = $this->atananIdleriTopla($validated);

        $payload = $validated;
        unset($payload['atanan_ids']);
        // Birincil atanan = listedeki ilk kisi (atananlariGuncelle de ayni sonucu yazar)
        $payload['atanan_id'] = $atananIdler[0] ?? null;

        if (empty($payload['musteri_id']) && !empty($payload['firsat_id'])) {
            $payload['musteri_id'] = Opportunity::where('id', $payload['firsat_id'])->value('musteri_id');
        }
        // Formdan durum gelmediyse yeni gorev "beklemede" baslar.
        $payload['durum'] = $payload['durum'] ?? 'beklemede';
        $payload['tamamlandi_at'] = $payload['durum'] === 'tamamlandi' ? now() : null;

        // Olusturan kisiyi kaydet (model fillable: olusturan_id + olusturan_adi)
        if (session('admin_id')) {
            $payload['olusturan_id'] = (int) session('admin_id');
            $olusturan = Yonetici::find($payload['olusturan_id']);
            if ($olusturan) {
                $payload['olusturan_adi'] = $olusturan->adi ?: $olusturan->kullaniciadi;
            }
        }

        $task = Task::create($payload);
        $this->handleFileUploads($request, $task->id);

        // Coklu atamayi yaz (birincil atanan dahil hepsi)
        $this->atananlariGuncelle($task, $atananIdler);

        // Atanan HERKESE bildirim gonder
        foreach ($atananIdler as $yid) {
            try { $this->gorevBildirimGonder($task, (int) $yid, 'yeni'); } catch (\Throwable $e) {}
        }

        return redirect()->route('admin.crm.gorevler.index')
            ->with('success', 'Görev oluşturuldu.');
    }

    /**
     * Görevi bir iç ekip üyesine ata (detay sayfasındaki "Görevi Ata" kutusu).
     */
    public function ata(Request $request, int $id)
    {
        $data = $request->validate([
            'atanan_ids'   => 'nullable|array',
            'atanan_ids.*' => 'integer|exists:yoneticiler,id',
            'atanan_id'    => 'nullable|integer|exists:yoneticiler,id',
        ], [
            'atanan_ids.*.exists' => 'Seçilen kişilerden biri sistemde bulunamadı.',
        ]);

        $idler = $this->atananIdleriTopla($data);

        if (!$idler) {
            return back()->with('error', 'En az bir kişi seçmelisiniz.');
        }

        $task = Task::findOrFail($id);

        // Yalnizca YENI eklenenlere bildirim gider; zaten atanmis kisi
        // her kaydetmede tekrar bildirim almasin.
        $yeniEklenenler = $this->atananlariGuncelle($task, $idler);

        if (!$yeniEklenenler) {
            return back()->with('info', 'Atama zaten güncel.');
        }

        foreach ($yeniEklenenler as $yid) {
            try { $this->gorevBildirimGonder($task, (int) $yid, 'yeni'); } catch (\Throwable $e) {}
        }

        return back()->with('success', count($idler) > 1
            ? 'Görev ' . count($idler) . ' kişiye atandı.'
            : 'Görev başarıyla atandı.');
    }

     protected function handleFileUploads(Request $request, int $taskId): void
    {
        if (!$request->hasFile('dosyalar')) return;
 
        $dir = public_path('uploads/crm_tasks');
 
        // FIX: Klasör garanti et + üst klasör de
        $uploadsDir = public_path('uploads');
        if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0775, true);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
 
        foreach ($request->file('dosyalar') as $file) {
            if (!$file || !$file->isValid()) continue;
 
            $orig = $file->getClientOriginalName();
            $safe = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
 
            // FIX: move()'dan ÖNCE size + mime al — tmp silindikten sonra erişilemiyor
            $fileSize = 0;
            $fileMime = null;
            try {
                $fileSize = $file->getSize() ?: 0;
                $fileMime = $file->getMimeType();
            } catch (\Throwable $e) {
                // Tmp ile ilgili sorun olursa devam et
            }
 
            // Şimdi taşı
            try {
                $file->move($dir, $safe);
            } catch (\Throwable $e) {
                \Log::error('Dosya taşıma başarısız: ' . $e->getMessage(), [
                    'task_id' => $taskId,
                    'file' => $orig,
                ]);
                continue;
            }
 
            // Eğer move öncesi size alınamadıysa, taşınmış dosyadan oku
            if ($fileSize === 0) {
                $abs = $dir . DIRECTORY_SEPARATOR . $safe;
                if (file_exists($abs)) {
                    $fileSize = @filesize($abs) ?: 0;
                }
            }
 
            \DB::table('crm_task_files')->insert([
                'task_id'       => $taskId,
                'file_path'     => 'uploads/crm_tasks/' . $safe,
                'original_name' => $orig,
                'size'          => $fileSize,
                'mime'          => $fileMime,
                'uploader_id'   => session('admin_id'),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function deleteFile(int $taskId, int $fileId)
    {
        $row = \DB::table('crm_task_files')->where('id', $fileId)->where('task_id', $taskId)->first();
        if ($row) {
            $abs = public_path($row->file_path);
            if (file_exists($abs)) @unlink($abs);
            \DB::table('crm_task_files')->where('id', $fileId)->delete();
        }
        return back()->with('success', 'Dosya silindi.');
    }

    public function edit(int $id)
    {
        $task = Task::findOrFail($id);
        $customers = Customer::orderBy('adi')->get(['id', 'adi']);
        $opportunities = Opportunity::orderBy('baslik')->get(['id', 'baslik', 'musteri_id']);
        // Görev sorumlusu = iç ekip; bayi (rol 3) + müşteri (rol 4) + pasifler hariç
        $yoneticiler = Yonetici::where('durum', 1)->whereNotIn('rol', [3, 4])
            ->orderBy('adi')->get(['id', 'adi', 'kullaniciadi', 'rol']);

        return view('admin.crm.tasks.edit', compact('task', 'customers', 'opportunities', 'yoneticiler'));
    }

    public function update(Request $request, int $id)
    {
        $task = Task::findOrFail($id);
        $validated = $request->validate([
            'musteri_id' => 'nullable|integer|exists:crm_customers,id',
            'firsat_id' => 'nullable|integer|exists:crm_opportunities,id',
            'atanan_ids'   => 'nullable|array',
            'atanan_ids.*' => 'integer|exists:yoneticiler,id',
            'atanan_id' => 'nullable|integer|exists:yoneticiler,id',
            'konu' => 'required|string|max:180',
            'tip' => 'nullable|string|max:60',
            'departman' => 'nullable|string|max:60',
            'oncelik' => ['nullable', Rule::in(['dusuk', 'normal', 'yuksek'])],
            'durum' => ['nullable', Rule::in(['beklemede', 'devam', 'musteri_bekleniyor', 'tamamlandi'])],
            'son_tarih' => 'nullable|date',
            'aciklama' => 'nullable|string',
        ]);

        $payload = $validated;
        if (empty($payload['musteri_id']) && !empty($payload['firsat_id'])) {
            $payload['musteri_id'] = Opportunity::where('id', $payload['firsat_id'])->value('musteri_id');
        }
        // Durum formdan geldiyse isle (tamamlandi_at gecisleri dahil);
        // gelmediyse mevcut durum/tamamlandi_at'a dokunma.
        if (!empty($payload['durum'])) {
            if ($payload['durum'] === 'tamamlandi' && $task->durum !== 'tamamlandi') {
                $payload['tamamlandi_at'] = now();
            } elseif ($payload['durum'] !== 'tamamlandi') {
                $payload['tamamlandi_at'] = null;
            }
        } else {
            unset($payload['durum']);
        }

        $atananIdler = $this->atananIdleriTopla($validated);
        unset($payload['atanan_ids']);
        $payload['atanan_id'] = $atananIdler[0] ?? null;

        $task->update($payload);
        $this->handleFileUploads($request, $task->id);

        // Yalnizca YENI eklenenlere bildirim; mevcut atananlar her
        // duzenlemede tekrar bildirim almasin.
        $yeniEklenenler = $this->atananlariGuncelle($task, $atananIdler);
        foreach ($yeniEklenenler as $yid) {
            try { $this->gorevBildirimGonder($task, (int) $yid, 'atama'); } catch (\Throwable $e) {}
        }

        return redirect()->route('admin.crm.gorevler.index')
            ->with('success', 'Görev güncellendi.');
    }

    /**
     * Goreve atanan sorumlu yoneticiye in-app bildirim gonderir.
     * admin_bildirimler tablosuna yazar (AdminBildirimController ile uyumlu).
     *
     * @param  Task    $task       Ilgili gorev
     * @param  int     $yoneticiId Bildirim gidecek sorumlu
     * @param  string  $tetik      'yeni' | 'atama'
     */
    protected function gorevBildirimGonder(Task $task, int $yoneticiId, string $tetik = 'yeni'): void
    {
        try {
            if (!Schema::hasTable('admin_bildirimler')) return;

            // Gorevi olusturan/guncelleyen admin kendine bildirim almasin
            $aktifAdminId = (int) session('admin_id');
            if ($aktifAdminId && $aktifAdminId === $yoneticiId) return;

            $konu = \Illuminate\Support\Str::limit($task->konu ?? 'Görev', 120);
            $baslik = $tetik === 'atama'
                ? '✅ Size bir görev atandı'
                : '✅ Yeni görev oluşturuldu';

            $mesaj = $konu;
            if (!empty($task->son_tarih)) {
                try {
                    $mesaj .= ' — Son tarih: ' . \Carbon\Carbon::parse($task->son_tarih)->format('d.m.Y');
                } catch (\Throwable $e) {}
            }

            $row = [
                'tip'          => 'gorev',
                'baslik'       => $baslik,
                'mesaj'        => $mesaj,
                'okundu'       => 0,
                'created_at'   => now(),
            ];

            // Opsiyonel kolonlar — sadece tabloda varsa yaz
            if (Schema::hasColumn('admin_bildirimler', 'updated_at')) {
                $row['updated_at'] = now();
            }
            if (Schema::hasColumn('admin_bildirimler', 'ilgili_id')) {
                $row['ilgili_id'] = $task->id;
            }
            if (Schema::hasColumn('admin_bildirimler', 'ilgili_tablo')) {
                $row['ilgili_tablo'] = 'crm_tasks';
            }
            // yonetici_id varsa kisiye ozel; yoksa NULL = tum adminler gorur
            if (Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
                $row['yonetici_id'] = $yoneticiId;
            }

            DB::table('admin_bildirimler')->insert($row);
        } catch (\Throwable $e) {
            \Log::warning('Gorev bildirimi gonderilemedi', [
                'task_id' => $task->id ?? null,
                'err'     => $e->getMessage(),
            ]);
        }

        // E-posta gonderimi — sorumlu yoneticiye
        try {
            $yonetici = DB::table('yoneticiler')
                ->where('id', $yoneticiId)
                ->first(['id', 'adi', 'kullaniciadi', 'email', 'eposta']);

            if (!$yonetici) return;

            // Email kolonu email veya eposta'da olabilir
            $to = $yonetici->email ?: ($yonetici->eposta ?? null);
            if (!$to) return;

            $alici  = $yonetici->adi ?: $yonetici->kullaniciadi;
            $konu   = e(\Illuminate\Support\Str::limit($task->konu ?? 'Görev', 150));
            $url    = \Illuminate\Support\Facades\Route::has('admin.crm.gorevler.show')
                ? route('admin.crm.gorevler.show', $task->id)
                : url('/admin/crm/gorevler');

            $sonTarihHtml = '';
            if (!empty($task->son_tarih)) {
                try {
                    $sonTarihHtml = '<div style="margin-top:10px;font-weight:700;color:#1f2419">📅 Son Tarih: '
                        . \Carbon\Carbon::parse($task->son_tarih)->format('d.m.Y') . '</div>';
                } catch (\Throwable $e) {}
            }

            $subject = $tetik === 'atama'
                ? '✅ Size bir görev atandı: ' . $konu
                : '✅ Yeni görev: ' . $konu;

            $html = '<h3 style="color:#1f2419">Merhaba ' . e($alici) . ',</h3>'
                  . '<p>Size bir görev ' . ($tetik === 'atama' ? 'atandı' : 'oluşturuldu') . ':</p>'
                  . '<div style="background:#f7f8f3;border-left:4px solid #b8b62e;border-radius:8px;padding:16px 18px;margin:14px 0">'
                  . '<strong style="font-size:16px;color:#6f7320">' . $konu . '</strong>'
                  . (!empty($task->aciklama) ? '<div style="margin-top:8px;font-size:14px;color:#3a4133;line-height:1.6">' . nl2br(e(\Illuminate\Support\Str::limit(strip_tags($task->aciklama), 400))) . '</div>' : '')
                  . $sonTarihHtml
                  . '</div>'
                  . '<p><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#b8b62e;color:#1f2419;text-decoration:none;border-radius:10px;font-weight:700">Görevi Aç →</a></p>';

            \App\Services\EmailNotificationService::send($to, $subject, $html);
        } catch (\Throwable $e) {
            \Log::warning('Gorev maili gonderilemedi', [
                'task_id'     => $task->id ?? null,
                'yonetici_id' => $yoneticiId,
                'err'         => $e->getMessage(),
            ]);
        }
    }

    /**
     * Tek tikla tamamlandi / geri al.
     * (Durum secimi formdan kaldirildi; durum bu ucla yonetilir.)
     */
    public function tamamlaToggle(int $id)
    {
        $task = Task::findOrFail($id);

        if ($task->durum === 'tamamlandi') {
            $task->update(['durum' => 'beklemede', 'tamamlandi_at' => null]);
            return back()->with('success', 'Görev tekrar açıldı.');
        }

        $task->update(['durum' => 'tamamlandi', 'tamamlandi_at' => now()]);
        return back()->with('success', 'Görev tamamlandı olarak işaretlendi. 🎉');
    }

    /**
     * Liste/form üzerinden durum seçerek değiştirme (Beklemede/Devam/Tamamlandı).
     * tamamlandi_at geçişlerini de yönetir.
     */
    public function durumDegistir(Request $request, int $id)
    {
        $validated = $request->validate([
            'durum' => ['required', Rule::in(['beklemede', 'devam', 'musteri_bekleniyor', 'tamamlandi'])],
        ]);

        $task = Task::findOrFail($id);
        $yeni = $validated['durum'];

        $payload = ['durum' => $yeni];
        if ($yeni === 'tamamlandi' && $task->durum !== 'tamamlandi') {
            $payload['tamamlandi_at'] = now();
        } elseif ($yeni !== 'tamamlandi') {
            $payload['tamamlandi_at'] = null;
        }

        $task->update($payload);

        $etiketler = ['beklemede' => '📝 Beklemede', 'devam' => '⏳ Devam', 'tamamlandi' => '✅ Tamamlandı'];
        return back()->with('success', 'Görev durumu güncellendi: ' . ($etiketler[$yeni] ?? $yeni));
    }

    public function destroy(int $id)
    {
        $task = Task::findOrFail($id);

        // Coklu atama uyeliklerini elle temizle.
        // NOT: Veritabani MyISAM oldugu icin ON DELETE CASCADE CALISMAZ
        // (MyISAM foreign key'i sessizce yok sayar). Silinmezse yetim
        // satirlar kalir ve kisi silinmis gorevi "bana atanan"da gorur.
        if (Schema::hasTable('crm_task_members')) {
            DB::table('crm_task_members')->where('task_id', $task->id)->delete();
        }

        $task->delete();

        return redirect()->route('admin.crm.gorevler.index')
            ->with('success', 'Görev silindi.');
    }

    /**
     * Görev detay sayfası — bilgiler + ekip yazışması + kanban'a taşı.
     */
    public function show(int $id)
    {
        $task = Task::with(['musteri', 'atanan', 'olusturan', 'atananlar'])->findOrFail($id);

        // "Görüldü": bu görevi açan kişi, kendisi DIŞINDAKİLERİN mesajlarını okumuş sayılır.
        try {
            if (Schema::hasColumn('crm_task_messages', 'okundu')) {
                DB::table('crm_task_messages')
                    ->where('task_id', $task->id)
                    ->where('gonderen_id', '!=', (int) session('admin_id'))
                    ->where('okundu', 0)
                    ->update(['okundu' => 1, 'okundu_at' => now()]);
            }
        } catch (\Throwable $e) {}

        $mesajlar = TaskMessage::where('task_id', $task->id)
            ->orderBy('id')
            ->get();

        $dosyalar = DB::table('crm_task_files')
            ->where('task_id', $task->id)
            ->orderByDesc('id')
            ->get();

        // Erişilebilir kanban panolari (tablo varsa)
        $kanbanBoards = collect();
        try {
            if (Schema::hasTable('crm_kanban_boards')) {
                $kanbanBoards = DB::table('crm_kanban_boards')->orderBy('adi')->get(['id', 'adi']);
            } elseif (Schema::hasTable('kanban_boards')) {
                $kanbanBoards = DB::table('kanban_boards')->orderBy('adi')->get(['id', 'adi']);
            }
        } catch (\Throwable $e) {}

        // Mesaj reaksiyonlari (tablo varsa) — mesaj_id'ye gore gruplu
        $reaksiyonlar = collect();
        try {
            if ($mesajlar->count() && Schema::hasTable('crm_task_message_reactions')) {
                $reaksiyonlar = DB::table('crm_task_message_reactions')
                    ->whereIn('mesaj_id', $mesajlar->pluck('id'))
                    ->orderBy('id')
                    ->get()
                    ->groupBy('mesaj_id');
            }
        } catch (\Throwable $e) {}

        // "Görevi Ata" kutusu için iç ekip listesi (bayi/müşteri/pasif hariç)
        $yoneticiler = Yonetici::where('durum', 1)->whereNotIn('rol', [3, 4])
            ->orderBy('adi')->get(['id', 'adi']);

        return view('admin.crm.tasks.show', compact('task', 'mesajlar', 'dosyalar', 'kanbanBoards', 'reaksiyonlar', 'yoneticiler'));
    }

    /**
     * Göreve ekip mesajı ekle.
     * Karşı tarafa (sorumlu ↔ oluşturan) bildirim + e-posta;
     * mesajda @kullaniciadi etiketi varsa o adminlere ayrıca e-posta.
     */
    public function mesajGonder(Request $request, int $id)
    {
        $validated = $request->validate([
            'mesaj'    => 'nullable|string|max:5000',
            'yanit_id' => 'nullable|integer',
            'dosya'    => 'nullable|file|max:20480',
        ]);

        $mesajMetni = trim((string) ($validated['mesaj'] ?? ''));

        // ── Dosya eki (varsa) ──
        $dosyaPath = $dosyaAd = $dosyaMime = null;
        if ($request->hasFile('dosya') && $request->file('dosya')->isValid()) {
            $file = $request->file('dosya');
            $dir  = public_path('uploads/crm_task_mesaj');
            $up   = public_path('uploads');
            if (!is_dir($up))  @mkdir($up, 0775, true);
            if (!is_dir($dir)) @mkdir($dir, 0775, true);
            $dosyaAd = $file->getClientOriginalName();
            $safe    = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $dosyaAd);
            try { $dosyaMime = $file->getMimeType(); } catch (\Throwable $e) {}
            try {
                $file->move($dir, $safe);
                $dosyaPath = 'uploads/crm_task_mesaj/' . $safe;
            } catch (\Throwable $e) {
                \Log::error('Gorev mesaj dosyasi tasinamadi: ' . $e->getMessage(), ['task_id' => $id]);
                $dosyaPath = $dosyaAd = $dosyaMime = null;
            }
        }

        if ($mesajMetni === '' && !$dosyaPath) {
            return back()->with('error', 'Bir mesaj yaz ya da bir dosya ekle.');
        }

        $task = Task::with(['atanan', 'olusturan'])->findOrFail($id);

        $gonderenId  = (int) session('admin_id');
        $gonderenAdi = session('admin_adi', session('admin_kullanici_adi', 'Admin'));

        // Yanıtlanan mesaj sadece AYNI görevin bir mesajı olabilir
        $yanitId = null;
        if (!empty($validated['yanit_id'])) {
            $yanitId = DB::table('crm_task_messages')
                ->where('id', (int) $validated['yanit_id'])
                ->where('task_id', $task->id)
                ->value('id');
        }

        $satir = [
            'task_id'      => $task->id,
            'gonderen_id'  => $gonderenId ?: null,
            'gonderen_adi' => $gonderenAdi,
            'mesaj'        => $mesajMetni,
            'yanit_id'     => $yanitId,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
        if (Schema::hasColumn('crm_task_messages', 'dosya')) {
            $satir['dosya']      = $dosyaPath;
            $satir['dosya_adi']  = $dosyaAd;
            $satir['dosya_mime'] = $dosyaMime;
        }
        $mesajId = DB::table('crm_task_messages')->insertGetId($satir);

        // Bildirim/mail metni: mesaj boşsa dosya adını kullan
        $bildirimMetni = $mesajMetni !== '' ? $mesajMetni : ('📎 ' . ($dosyaAd ?: 'Dosya'));

        // ── Karşı tarafa bildirim + e-posta ──
        // Gönderen kim ise görevin diğer ucuna gönder (sorumlu <-> oluşturan)
        $hedefIdler = [];
        $atananId    = (int) ($task->atanan_id ?? 0);
        $olusturanId = (int) ($task->olusturan_id ?? 0);

        if ($gonderenId === $atananId && $olusturanId) {
            $hedefIdler[] = $olusturanId;       // sorumlu yazdı → oluşturana
        } elseif ($gonderenId === $olusturanId && $atananId) {
            $hedefIdler[] = $atananId;          // oluşturan yazdı → sorumluya
        } else {
            // Gönderen üçüncü bir kişi (ör. patron) → ikisine de
            if ($atananId)    $hedefIdler[] = $atananId;
            if ($olusturanId) $hedefIdler[] = $olusturanId;
        }
        // Kendine gönderme + tekrarları temizle
        $hedefIdler = array_unique(array_filter($hedefIdler, fn($x) => $x && $x !== $gonderenId));

        foreach ($hedefIdler as $hid) {
            $this->gorevMesajBildirim($task, (int) $hid, $gonderenAdi, $bildirimMetni);
        }

        // ── @kullaniciadi etiketi: o adminlere ayrıca mail ──
        if (preg_match_all('/@([A-Za-z0-9_\.]+)/u', $mesajMetni, $m)) {
            try {
                $etiketliler = DB::table('yoneticiler')
                    ->whereIn('kullaniciadi', array_unique($m[1]))
                    ->where('durum', 1)
                    ->get(['id', 'adi', 'kullaniciadi', 'email', 'eposta']);

                foreach ($etiketliler as $y) {
                    if ((int) $y->id === $gonderenId) continue;          // kendini etiketleme
                    if (in_array((int) $y->id, $hedefIdler, true)) continue; // zaten mail aldı
                    $to = $y->email ?: ($y->eposta ?? null);
                    if (!$to) continue;
                    $url = route('admin.crm.gorevler.show', $task->id);
                    $subject = '🔔 ' . $gonderenAdi . ' sizi bir görev mesajında etiketledi';
                    $html = '<h3 style="color:#1f2419">Merhaba ' . e($y->adi ?: $y->kullaniciadi) . ',</h3>'
                          . '<p><strong style="color:#6f7320">' . e($gonderenAdi) . '</strong> sizi "<strong>' . e($task->konu) . '</strong>" görevinin yazışmasında etiketledi.</p>'
                          . '<blockquote style="border-left:4px solid #b8b62e;padding:12px 16px;background:#f7f8f3;color:#3a4133;border-radius:8px;margin:14px 0">' . nl2br(e($bildirimMetni)) . '</blockquote>'
                          . '<p><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#b8b62e;color:#1f2419;text-decoration:none;border-radius:10px;font-weight:700">Göreve Git →</a></p>';
                    \App\Services\EmailNotificationService::send($to, $subject, $html);
                }
            } catch (\Throwable $e) {
                \Log::warning('Gorev mesaj etiket maili gonderilemedi', ['err' => $e->getMessage()]);
            }
        }

        return back()->with('success', 'Mesaj gönderildi.');
    }

    /**
     * Görev mesajı için tek bir yöneticiye bildirim + e-posta.
     */
    protected function gorevMesajBildirim(Task $task, int $yoneticiId, string $gonderenAdi, string $mesaj): void
    {
        // 1) In-app bildirim (admin_bildirimler)
        try {
            if (Schema::hasTable('admin_bildirimler')) {
                $row = [
                    'tip'        => 'gorev_mesaj',
                    'baslik'     => '💬 ' . $gonderenAdi . ' görev mesajı yazdı',
                    'mesaj'      => \Illuminate\Support\Str::limit($task->konu . ': ' . $mesaj, 180),
                    'okundu'     => 0,
                    'created_at' => now(),
                ];
                if (Schema::hasColumn('admin_bildirimler', 'updated_at'))   $row['updated_at'] = now();
                if (Schema::hasColumn('admin_bildirimler', 'ilgili_id'))    $row['ilgili_id'] = $task->id;
                if (Schema::hasColumn('admin_bildirimler', 'ilgili_tablo')) $row['ilgili_tablo'] = 'crm_tasks';
                if (Schema::hasColumn('admin_bildirimler', 'yonetici_id'))  $row['yonetici_id'] = $yoneticiId;
                DB::table('admin_bildirimler')->insert($row);
            }
        } catch (\Throwable $e) {
            \Log::warning('Gorev mesaj bildirimi yazilamadi', ['err' => $e->getMessage()]);
        }

        // 2) E-posta
        try {
            $yonetici = DB::table('yoneticiler')->where('id', $yoneticiId)->first(['adi', 'kullaniciadi', 'email', 'eposta']);
            if (!$yonetici) return;
            $to = $yonetici->email ?: ($yonetici->eposta ?? null);
            if (!$to) return;

            $url = route('admin.crm.gorevler.show', $task->id);
            $subject = '💬 Görev mesajı: ' . \Illuminate\Support\Str::limit($task->konu, 100);
            $html = '<h3 style="color:#1f2419">Merhaba ' . e($yonetici->adi ?: $yonetici->kullaniciadi) . ',</h3>'
                  . '<p><strong style="color:#6f7320">' . e($gonderenAdi) . '</strong>, "<strong>' . e($task->konu) . '</strong>" görevine yeni bir mesaj yazdı:</p>'
                  . '<blockquote style="border-left:4px solid #b8b62e;padding:12px 16px;background:#f7f8f3;color:#3a4133;border-radius:8px;margin:14px 0">' . nl2br(e(\Illuminate\Support\Str::limit($mesaj, 500))) . '</blockquote>'
                  . '<p><a href="' . $url . '" style="display:inline-block;padding:12px 24px;background:#b8b62e;color:#1f2419;text-decoration:none;border-radius:10px;font-weight:700">Göreve Git & Yanıtla →</a></p>';
            \App\Services\EmailNotificationService::send($to, $subject, $html);
        } catch (\Throwable $e) {
            \Log::warning('Gorev mesaj maili gonderilemedi', ['yonetici_id' => $yoneticiId, 'err' => $e->getMessage()]);
        }
    }

    /**
     * Görev mesajını sil.
     * Yetki: mesajı yazan kişi veya Patron (rol=1).
     */
    public function mesajSil(int $mesajId)
    {
        $adminId  = (int) session('admin_id');
        $adminRol = (int) session('admin_rol', 2);

        $mesaj = DB::table('crm_task_messages')->where('id', $mesajId)->first();

        if (!$mesaj) {
            return back()->with('error', 'Mesaj bulunamadı.');
        }

        // Patron değilse yalnızca kendi yazdığı mesajı silebilir
        if ($adminRol !== 1 && (int) $mesaj->gonderen_id !== $adminId) {
            return back()->with('error', 'Bu mesajı silme yetkiniz yok.');
        }

        // Önce mesajın reaksiyonları (tablo varsa), sonra mesaj
        try {
            if (Schema::hasTable('crm_task_message_reactions')) {
                DB::table('crm_task_message_reactions')->where('mesaj_id', $mesajId)->delete();
            }
        } catch (\Throwable $e) {}

        DB::table('crm_task_messages')->where('id', $mesajId)->delete();
        DB::table('crm_task_messages')->where('yanit_id', $mesajId)->update(['yanit_id' => null]);

        return redirect()
            ->route('admin.crm.gorevler.show', $mesaj->task_id)
            ->with('success', 'Mesaj silindi.');
    }

    /**
     * Görev mesajını düzenle.
     * Yetki: mesajı yazan kişi veya Patron (rol=1).
     */
    public function mesajDuzenle(Request $request, int $mesajId)
    {
        $adminId  = (int) session('admin_id');
        $adminRol = (int) session('admin_rol', 2);

        $validated = $request->validate(['mesaj' => 'required|string|max:5000']);

        $mesaj = DB::table('crm_task_messages')->where('id', $mesajId)->first();
        if (!$mesaj) {
            return back()->with('error', 'Mesaj bulunamadı.');
        }
        if ($adminRol !== 1 && (int) $mesaj->gonderen_id !== $adminId) {
            return back()->with('error', 'Bu mesajı düzenleme yetkiniz yok.');
        }

        DB::table('crm_task_messages')->where('id', $mesajId)->update([
            'mesaj'      => trim($validated['mesaj']),
            'duzenlendi' => 1,
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.crm.gorevler.show', $mesaj->task_id)
            ->with('success', 'Mesaj güncellendi.');
    }

    /**
     * Mesaj reaksiyonu ekle/kaldır (AJAX, toggle).
     * Aynı emojiye ikinci kez basılırsa kaldırılır.
     * Dönen JSON ile o mesajın güncel reaksiyon özeti gelir.
     */
    public function reaksiyonToggle(Request $request, int $id)
    {
        $izinliEmojiler = ['👍', '❤️', '😂', '😮', '🎉', '✅'];

        $validated = $request->validate([
            'mesaj_id' => 'required|integer',
            'emoji'    => 'required|string|max:16',
        ]);

        if (!in_array($validated['emoji'], $izinliEmojiler, true)) {
            return response()->json(['ok' => false, 'hata' => 'Geçersiz emoji.'], 422);
        }

        if (!Schema::hasTable('crm_task_message_reactions')) {
            return response()->json(['ok' => false, 'hata' => 'Reaksiyon tablosu kurulmamış (reaksiyon_kurulum.sql çalıştırılmalı).'], 500);
        }

        // Güvenlik: mesaj gerçekten bu göreve mi ait?
        $mesaj = DB::table('crm_task_messages')
            ->where('id', (int) $validated['mesaj_id'])
            ->where('task_id', $id)
            ->first(['id', 'gonderen_id', 'mesaj']);

        if (!$mesaj) {
            return response()->json(['ok' => false, 'hata' => 'Mesaj bulunamadı.'], 404);
        }

        $adminId  = (int) session('admin_id');
        $adminAdi = session('admin_adi', session('admin_kullanici_adi', 'Admin'));

        if (!$adminId) {
            return response()->json(['ok' => false, 'hata' => 'Oturum bulunamadı.'], 403);
        }

        $mevcut = DB::table('crm_task_message_reactions')
            ->where('mesaj_id', $mesaj->id)
            ->where('yonetici_id', $adminId)
            ->where('emoji', $validated['emoji'])
            ->first(['id']);

        if ($mevcut) {
            // Toggle: ayni emojiye tekrar basti -> kaldir (bildirim YOK)
            DB::table('crm_task_message_reactions')->where('id', $mevcut->id)->delete();
        } else {
            DB::table('crm_task_message_reactions')->insert([
                'mesaj_id'     => $mesaj->id,
                'yonetici_id'  => $adminId,
                'yonetici_adi' => $adminAdi,
                'emoji'        => $validated['emoji'],
                'created_at'   => now(),
            ]);

            // Mesajin sahibine zil bildirimi (kendine basinca gitmez)
            $this->reaksiyonBildirim($id, $mesaj, $validated['emoji'], $adminId, $adminAdi);
        }

        // Bu mesajın güncel reaksiyon özeti: emoji => {adet, benVar, kisiler}
        $liste = DB::table('crm_task_message_reactions')
            ->where('mesaj_id', $mesaj->id)
            ->orderBy('id')
            ->get();

        $ozet = $liste->groupBy('emoji')->map(function ($grup) use ($adminId) {
            return [
                'adet'    => $grup->count(),
                'benVar'  => $grup->contains(function ($r) use ($adminId) {
                    return (int) $r->yonetici_id === $adminId;
                }),
                'kisiler' => $grup->pluck('yonetici_adi')->filter()->values(),
            ];
        });

        return response()->json([
            'ok'           => true,
            'mesaj_id'     => $mesaj->id,
            'reaksiyonlar' => $ozet,
        ]);
    }

    /**
     * Reaksiyon zil bildirimi — mesajın sahibine, kişiye özel.
     * Mail GÖNDERMEZ (her emoji için mail spam olur, zil yeterli).
     * Kurallar: kendi mesajına basana gitmez; reaksiyon kaldırınca gitmez;
     * yonetici_id kolonu yoksa hiç gönderilmez (herkese reaksiyon bildirimi spam olur);
     * aynı kişiye aynı içerikli okunmamış bildirim varsa tekrarlanmaz (toggle spam koruması).
     */
    protected function reaksiyonBildirim(int $taskId, object $mesaj, string $emoji, int $adminId, string $adminAdi): void
    {
        try {
            if (!Schema::hasTable('admin_bildirimler')) return;

            $hedefId = (int) ($mesaj->gonderen_id ?? 0);
            if (!$hedefId || $hedefId === $adminId) return;

            // Kisiye ozel hedefleme yapilamiyorsa gonderme
            if (!Schema::hasColumn('admin_bildirimler', 'yonetici_id')) return;

            $konu   = (string) (DB::table('crm_tasks')->where('id', $taskId)->value('konu') ?? 'Görev');
            $baslik = $emoji . ' ' . $adminAdi . ' mesajınıza reaksiyon bıraktı';
            $icerik = \Illuminate\Support\Str::limit($konu, 80)
                    . ': "' . \Illuminate\Support\Str::limit((string) ($mesaj->mesaj ?? ''), 100) . '"';

            // Toggle spam korumasi: ayni icerikli okunmamis bildirim zaten varsa ekleme
            $varMi = DB::table('admin_bildirimler')
                ->where('yonetici_id', $hedefId)
                ->where('baslik', $baslik)
                ->where('mesaj', $icerik)
                ->where('okundu', 0)
                ->exists();
            if ($varMi) return;

            $row = [
                'tip'         => 'gorev_mesaj',
                'baslik'      => $baslik,
                'mesaj'       => $icerik,
                'okundu'      => 0,
                'created_at'  => now(),
                'yonetici_id' => $hedefId,
            ];
            if (Schema::hasColumn('admin_bildirimler', 'updated_at')) {
                $row['updated_at'] = now();
            }
            if (Schema::hasColumn('admin_bildirimler', 'ilgili_id')) {
                $row['ilgili_id'] = $taskId;
            }
            if (Schema::hasColumn('admin_bildirimler', 'ilgili_tablo')) {
                $row['ilgili_tablo'] = 'crm_tasks';
            }

            DB::table('admin_bildirimler')->insert($row);
        } catch (\Throwable $e) {
            \Log::warning('Reaksiyon bildirimi gonderilemedi', [
                'task_id'  => $taskId,
                'mesaj_id' => $mesaj->id ?? null,
                'err'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Görevi Kanban panosuna kart olarak taşır.
     * NOT: Kanban tablo yapısı projeye göre değişebilir; güvenli şekilde dener.
     */
    public function kanbanaTasi(Request $request, int $id)
    {
        $request->validate(['board_id' => 'required|integer']);
        $task = Task::findOrFail($id);

        try {
            // Pano + ilk liste tespiti (yaygın tablo adlarını dene)
            $boardTable = Schema::hasTable('crm_kanban_boards') ? 'crm_kanban_boards'
                        : (Schema::hasTable('kanban_boards') ? 'kanban_boards' : null);
            $listTable  = Schema::hasTable('crm_kanban_lists') ? 'crm_kanban_lists'
                        : (Schema::hasTable('kanban_lists') ? 'kanban_lists' : null);
            $cardTable  = Schema::hasTable('crm_kanban_cards') ? 'crm_kanban_cards'
                        : (Schema::hasTable('kanban_cards') ? 'kanban_cards' : null);

            if (!$boardTable || !$listTable || !$cardTable) {
                return back()->with('error', 'Kanban tabloları bulunamadı; bu özellik bu kurulumda yapılandırılmamış.');
            }

            $ilkListe = DB::table($listTable)->where('board_id', $request->board_id)->orderBy('id')->first();
            if (!$ilkListe) {
                return back()->with('error', 'Seçilen panoda liste yok. Önce panoya bir liste ekleyin.');
            }

            // Görevin ekli dosyalarını kartın linklerine kopyala (kartta tıklanabilir görünür)
            $linkler = [];
            try {
                if (Schema::hasTable('crm_task_files')) {
                    $gorevDosyalari = DB::table('crm_task_files')->where('task_id', $task->id)->orderBy('id')->get();
                    foreach ($gorevDosyalari as $gd) {
                        $linkler[] = [
                            'baslik' => '📄 ' . ($gd->original_name ?: basename($gd->file_path)),
                            'url'    => asset($gd->file_path),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // dosya kopyalama hatasi karti engellemesin
            }

            // Departman varsa kart etiketi olarak ekle
            $etiketler = [];
            if (!empty($task->departman)) {
                $etiketler[] = Task::departmanLabel($task->departman);
            }

            $kart = [
                'list_id'    => $ilkListe->id,
                'baslik'     => $task->konu,
                'aciklama'   => $task->aciklama,
                'oncelik'    => in_array($task->oncelik ?? '', ['dusuk', 'normal', 'yuksek', 'acil'], true) ? $task->oncelik : 'normal',
                'etiketler'  => !empty($etiketler) ? json_encode($etiketler, JSON_UNESCAPED_UNICODE) : null,
                'linkler'    => !empty($linkler) ? json_encode($linkler, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            $kartId = DB::table($cardTable)->insertGetId(array_intersect_key(
                $kart,
                array_flip(Schema::getColumnListing($cardTable))
            ));

            // Görevde kanban_card_id kolonu varsa işaretle
            if (Schema::hasColumn('crm_tasks', 'kanban_card_id')) {
                DB::table('crm_tasks')->where('id', $task->id)->update(['kanban_card_id' => $kartId]);
            }

            return back()->with('success', 'Görev Kanban panosuna kart olarak eklendi.');
        } catch (\Throwable $e) {
            \Log::warning('Kanbana tasima hatasi', ['task_id' => $task->id, 'err' => $e->getMessage()]);
            return back()->with('error', 'Kanban\'a taşınamadı: ' . $e->getMessage());
        }
    }

    /**
     * Formdan gelen atanan listesini normalize eder.
     *
     * Coklu alan 'atanan_ids[]'; eski tekli 'atanan_id' de kabul
     * ediliyor ki bu paket yuklenmeden once acilmis sekmelerdeki
     * formlar ve dis entegrasyonlar bozulmasin.
     *
     * @return array<int> benzersiz, sirasi korunmus yonetici id listesi
     */
    protected function atananIdleriTopla(array $veri): array
    {
        $liste = collect($veri['atanan_ids'] ?? [])
            ->when(!empty($veri['atanan_id']), fn ($c) => $c->prepend($veri['atanan_id']))
            ->map(fn ($x) => (int) $x)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $liste;
    }

    /**
     * Gorevin atanan listesini verilen haliyle degistirir.
     *
     * BIRINCIL ATANAN = listedeki ilk kisi; crm_tasks.atanan_id'ye
     * yazilir. Eski raporlar/sorgular yalnizca o kolona baktigi icin
     * bu alan her zaman dolu tutulur (liste bossa null).
     *
     * @return array<int> yeni eklenen (daha once atanmamis) kisiler
     */
    protected function atananlariGuncelle(Task $task, array $idler): array
    {
        if (!Schema::hasTable('crm_task_members')) {
            // Tablo henuz yoksa eski davranis: sadece birincil atanan.
            $task->atanan_id = $idler[0] ?? null;
            $task->save();
            return $idler ? [$idler[0]] : [];
        }

        $onceki = DB::table('crm_task_members')
            ->where('task_id', $task->id)
            ->pluck('yonetici_id')
            ->map(fn ($x) => (int) $x)
            ->all();

        DB::table('crm_task_members')->where('task_id', $task->id)->delete();

        $now = now();
        if ($idler) {
            DB::table('crm_task_members')->insertOrIgnore(
                array_map(fn ($yid) => [
                    'task_id'    => $task->id,
                    'yonetici_id' => $yid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $idler)
            );
        }

        $task->atanan_id = $idler[0] ?? null;
        $task->save();

        return array_values(array_diff($idler, $onceki));
    }

    /**
     * "Bu gorev bana atanmis mi" kosulu.
     *
     * Hem birincil atanana hem de coklu atama tablosuna bakar; yoksa
     * ikinci/ucuncu atanan kendi gorevini goremez.
     */
    protected function banaAtananKosulu($query, int $yoneticiId)
    {
        return $query->where(function ($q) use ($yoneticiId) {
            $q->where('crm_tasks.atanan_id', $yoneticiId);

            if (Schema::hasTable('crm_task_members')) {
                $q->orWhereExists(function ($sq) use ($yoneticiId) {
                    $sq->selectRaw('1')
                       ->from('crm_task_members as tm')
                       ->whereColumn('tm.task_id', 'crm_tasks.id')
                       ->where('tm.yonetici_id', $yoneticiId);
                });
            }
        });
    }

}