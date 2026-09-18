<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class YoneticiController extends Controller
{
    public function index()
    {
        // Bayi rolündeki hesaplar bu listede GÖSTERİLMEZ: bayiler panel
        // yöneticisi değildir, kendi modülünde (Bayiler) yönetilir.
        // Burada görünmeleri kafa karışıklığı yaratıyordu.
        $bayiRolIds = DB::table('roller')->where('slug', 'bayi')->pluck('id')->all();

        $yoneticiler = DB::table('yoneticiler')
            ->when(!empty($bayiRolIds), fn ($q) => $q->whereNotIn('rol', $bayiRolIds))
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Rol adlarini cekip view'a iletelim (badge icin)
        $rolHaritasi = DB::table('roller')->get(['id', 'ad', 'ikon', 'renk'])->keyBy('id');

        return view('admin.yoneticiler.index', compact('yoneticiler', 'rolHaritasi'));
    }

    private function rolleriGetir()
    {
        return DB::table('roller')
            ->where('durum', 1)
            ->orderBy('id')
            ->get(['id', 'ad', 'aciklama', 'korumali', 'ikon', 'renk']);
    }

    public function ekle()
    {
        $uyeler = DB::table('uyeler')
            ->select('id', 'ad', 'soyad', 'email', 'telefon')
            ->orderBy('ad')
            ->orderBy('soyad')
            ->orderByDesc('id')
            ->limit(500)
            ->get();

        $roller = $this->rolleriGetir();

        return view('admin.yoneticiler.ekle', compact('uyeler', 'roller'));
    }
    
    public function eklePost(Request $request)
    {
        // Sadece patron rol atayabilir
        if (session('admin_rol') != 1) {
            return redirect()->back()->with('error', 'Sadece patron rol atayabilir!');
        }

        $mode = $request->input('account_mode', 'existing');

        $baseRules = [
            'account_mode' => 'nullable|in:existing,new',
            'kullaniciadi' => 'required|string|max:100|unique:yoneticiler,kullaniciadi',
            'rol' => 'required|integer|exists:roller,id',
            'sifre' => 'required|string|min:6|confirmed',
        ];

        if ($mode === 'new') {
            $request->validate($baseRules + [
                'uye_ad' => 'required|string|max:255',
                'uye_soyad' => 'required|string|max:255',
                'uye_email' => 'required|email|unique:uyeler,email',
                'uye_telefon' => 'nullable|string|max:30',
            ]);

            $email = trim((string) $request->uye_email);

            if (DB::table('yoneticiler')->where('email', $email)->exists()) {
                return redirect()->back()->withInput()->with('error', 'Bu e-posta adresiyle zaten bir yönetici mevcut.');
            }

            $hashedPassword = Hash::make($request->sifre);
            $uyeId = DB::table('uyeler')->insertGetId([
                'ad' => $request->uye_ad,
                'soyad' => $request->uye_soyad,
                'email' => $email,
                'telefon' => $request->uye_telefon,
                'sifre' => $hashedPassword,
                'durum' => 1,
                'bakiye' => 0,
                'tarih' => date('Y-m-d H:i:s'),
                'ktarih' => date('Y-m-d H:i:s'),
            ]);

            DB::table('yoneticiler')->insert([
                'kullaniciadi' => $request->kullaniciadi,
                'email' => $email,
                'sifre' => $hashedPassword,
                'adi' => trim($request->uye_ad . ' ' . $request->uye_soyad),
                'telefon' => $request->uye_telefon,
                'yetki' => $request->yetki ?? 1,
                'rol' => $request->rol,
                'durum' => $request->filled('durum') ? 1 : 0,
            ]);

            return redirect()->route('admin.yoneticiler.index')->with(
                'success',
                'Yönetici başarıyla eklendi! (Yeni üye oluşturuldu: #' . $uyeId . ')'
            );
        }

        // existing mode
        $request->validate($baseRules + [
            'uye_id' => 'required|integer|exists:uyeler,id',
        ]);

        $uye = DB::table('uyeler')->where('id', $request->uye_id)->first();

        if (!$uye) {
            return redirect()->back()->withInput()->with('error', 'Seçili üye bulunamadı.');
        }

        if (empty($uye->email)) {
            return redirect()->back()->withInput()->with('error', 'Seçtiğiniz üyenin kayıtlı bir e-posta adresi bulunmuyor.');
        }

        if (DB::table('yoneticiler')->where('email', $uye->email)->exists()) {
            return redirect()->back()->withInput()->with('error', 'Bu e-posta adresiyle zaten bir yönetici mevcut.');
        }

        DB::table('yoneticiler')->insert([
            'kullaniciadi' => $request->kullaniciadi,
            'email' => $uye->email,
            'sifre' => Hash::make($request->sifre),
            'adi' => trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: $request->adi,
            'telefon' => $uye->telefon ?? $request->telefon,
            'yetki' => $request->yetki ?? 1,
            'rol' => $request->rol,
            'durum' => $request->filled('durum') ? 1 : 0,
        ]);

        return redirect()->route('admin.yoneticiler.index')->with('success', 'Yönetici başarıyla eklendi!');
    }
    
    public function duzenle($id)
    {
        $yonetici = DB::table('yoneticiler')->where('id', $id)->first();

        if (!$yonetici) {
            return redirect()->route('admin.yoneticiler.index')->with('error', 'Yönetici bulunamadı!');
        }

        $roller = $this->rolleriGetir();

        return view('admin.yoneticiler.duzenle', compact('yonetici', 'roller'));
    }

    public function duzenlePost(Request $request, $id)
    {
        // Sadece patron rol değiştirebilir
        if (session('admin_rol') != 1) {
            return redirect()->back()->with('error', 'Sadece patron rol değiştirebilir!');
        }

        $request->validate([
            'kullaniciadi' => 'required|string|max:100',
            'email' => 'required|email',
            'rol' => 'required|integer|exists:roller,id',
        ]);

        $data = [
            'kullaniciadi' => $request->kullaniciadi,
            'email' => $request->email,
            'adi' => $request->adi,
            'telefon' => $request->telefon,
            'yetki' => $request->yetki ?? 1,
            'rol' => $request->rol,
            'durum' => $request->durum ?? 1,
        ];

        if ($request->filled('sifre')) {
            $data['sifre'] = Hash::make($request->sifre);
        }

        // ── PROFİL FOTOĞRAFI (kolon varsa) ──
        if (Schema::hasColumn('yoneticiler', 'profil_foto')) {
            $mevcut = DB::table('yoneticiler')->where('id', $id)->first();
            $eskiFoto = $mevcut->profil_foto ?? null;

            if ($request->hasFile('profil_foto')) {
                $request->validate([
                    'profil_foto' => 'image|mimes:jpg,jpeg,png,webp|max:2048',
                ], [
                    'profil_foto.image' => 'Profil fotoğrafı bir resim dosyası olmalı.',
                    'profil_foto.mimes' => 'Sadece JPG, PNG veya WEBP yükleyebilirsiniz.',
                    'profil_foto.max'   => 'Fotoğraf en fazla 2MB olabilir.',
                ]);

                $klasor = public_path('tema/uploads/profil');
                if (!is_dir($klasor)) { @mkdir($klasor, 0755, true); }

                $uzanti = strtolower($request->file('profil_foto')->getClientOriginalExtension() ?: 'jpg');
                $dosyaAdi = 'yonetici_' . $id . '_' . time() . '.' . $uzanti;
                $request->file('profil_foto')->move($klasor, $dosyaAdi);

                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }

                $data['profil_foto'] = 'tema/uploads/profil/' . $dosyaAdi;
            } elseif ($request->boolean('foto_kaldir')) {
                if ($eskiFoto && str_starts_with($eskiFoto, 'tema/uploads/profil/') && is_file(public_path($eskiFoto))) {
                    @unlink(public_path($eskiFoto));
                }
                $data['profil_foto'] = null;
            }
        }

        DB::table('yoneticiler')->where('id', $id)->update($data);

        return redirect()->route('admin.yoneticiler.index')
            ->with('success', 'Yönetici bilgileri güncellendi. Yetki ataması için ilgili "Roller" sayfasını kullanın.');
    }
    
    public function sil($id)
    {
        // Kendi hesabını silemesin
        if (session('admin_id') == $id) {
            return redirect()->route('admin.yoneticiler.index')->with('error', 'Kendi hesabınızı silemezsiniz!');
        }
        
        DB::table('yoneticiler')->where('id', $id)->delete();
        return redirect()->route('admin.yoneticiler.index')->with('success', 'Yönetici başarıyla silindi!');
    }
}