<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MenuController extends Controller
{
    /**
     * Navbar menü listesi
     */
    public function header()
    {
        if (!Schema::hasTable('menuler')) {
            $menuler = collect([]);
        } else {
            // Navbar menüleri: ustid = 0 olanlar (ana menü)
            $menuler = DB::table('menuler')
                ->where('ustid', 0)
                ->orderBy('sira', 'asc')
                ->get();
            
            // Her menü için alt menüleri çek
            foreach ($menuler as $menu) {
                $menu->altmenu = DB::table('menuler')
                    ->where('ustid', $menu->id)
                    ->orderBy('sira', 'asc')
                    ->get();
            }
        }
        return view('admin.menuler.header', compact('menuler'));
    }
    
    /**
     * Navbar menü ekle
     */
    public function headerEkle(Request $request)
    {
        $request->validate([
            'menu_isim' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'menu_url' => 'nullable|string|max:255',
            'sira' => 'nullable|integer',
            'durum' => 'nullable|integer',
            'sekme' => 'nullable|integer',
        ]);
        
        if (!Schema::hasTable('menuler')) {
            return redirect()->back()->with('error', 'menuler tablosu bulunamadı!');
        }
        
        DB::table('menuler')->insert([
            'menu_isim' => $request->menu_isim,
            'link' => $request->link,
            'menu_url' => $request->menu_url ?? '0',
            'ustid' => 0, // Ana menü
            'sira' => $request->sira ?? DB::table('menuler')->where('ustid', 0)->max('sira') + 1 ?? 0,
            'durum' => $request->durum ?? 1,
            'sekme' => $request->sekme ?? 0,
            'dil' => 1,
        ]);
        
        return redirect()->route('admin.menuler.header')->with('success', 'Navbar menü başarıyla eklendi.');
    }
    
    /**
     * Navbar menü düzenle
     */
    public function headerDuzenle($id)
    {
        if (!Schema::hasTable('menuler')) {
            return redirect()->route('admin.menuler.header')->with('error', 'menuler tablosu bulunamadı!');
        }
        
        $menu = DB::table('menuler')->where('id', $id)->first();
        
        if (!$menu) {
            return redirect()->route('admin.menuler.header')->with('error', 'Menü bulunamadı!');
        }
        
        // Alt menüleri çek
        $altmenuler = DB::table('menuler')
            ->where('ustid', $id)
            ->orderBy('sira', 'asc')
            ->get();
        
        return view('admin.menuler.header-duzenle', compact('menu', 'altmenuler'));
    }
    
    /**
     * Navbar menü düzenle POST
     */
    public function headerDuzenlePost(Request $request, $id)
    {
        $request->validate([
            'menu_isim' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'menu_url' => 'nullable|string|max:255',
            'sira' => 'nullable|integer',
            'durum' => 'nullable|integer',
            'sekme' => 'nullable|integer',
        ]);
        
        if (!Schema::hasTable('menuler')) {
            return redirect()->back()->with('error', 'menuler tablosu bulunamadı!');
        }
        
        DB::table('menuler')->where('id', $id)->update([
            'menu_isim' => $request->menu_isim,
            'link' => $request->link,
            'menu_url' => $request->menu_url ?? '0',
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
            'sekme' => $request->sekme ?? 0,
        ]);
        
        return redirect()->route('admin.menuler.header')->with('success', 'Navbar menü başarıyla güncellendi.');
    }
    
    /**
     * Navbar menü sil
     */
    public function headerSil($id)
    {
        if (!Schema::hasTable('menuler')) {
            return redirect()->back()->with('error', 'menuler tablosu bulunamadı!');
        }
        
        // Alt menüleri de sil
        DB::table('menuler')->where('ustid', $id)->delete();
        
        // Ana menüyü sil
        DB::table('menuler')->where('id', $id)->delete();
        
        return redirect()->route('admin.menuler.header')->with('success', 'Navbar menü başarıyla silindi.');
    }
    
    /**
     * Menü sıralama güncelle (AJAX)
     */
    public function headerSiraGuncelle(Request $request)
    {
        $request->validate([
            'menuler' => 'required|array',
            'menuler.*.id' => 'required|integer',
            'menuler.*.sira' => 'required|integer',
        ]);
        
        foreach ($request->menuler as $menu) {
            DB::table('menuler')
                ->where('id', $menu['id'])
                ->update(['sira' => $menu['sira']]);
        }
        
        return response()->json(['success' => true, 'message' => 'Sıralama başarıyla güncellendi.']);
    }
    
    /**
     * Alt menü ekle
     */
    public function altMenuEkle(Request $request, $ustMenuId)
    {
        $request->validate([
            'menu_isim' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'menu_url' => 'nullable|string|max:255',
            'sira' => 'nullable|integer',
            'durum' => 'nullable|integer',
            'sekme' => 'nullable|integer',
        ]);
        
        if (!Schema::hasTable('menuler')) {
            return redirect()->back()->with('error', 'menuler tablosu bulunamadı!');
        }
        
        DB::table('menuler')->insert([
            'menu_isim' => $request->menu_isim,
            'link' => $request->link,
            'menu_url' => $request->menu_url ?? '0',
            'ustid' => $ustMenuId,
            'sira' => $request->sira ?? DB::table('menuler')->where('ustid', $ustMenuId)->max('sira') + 1 ?? 0,
            'durum' => $request->durum ?? 1,
            'sekme' => $request->sekme ?? 0,
            'dil' => 1,
        ]);
        
        return redirect()->route('admin.menuler.header.duzenle', $ustMenuId)->with('success', 'Alt menü başarıyla eklendi.');
    }
    
    /**
     * Alt menü sil
     */
    public function altMenuSil($id)
    {
        if (!Schema::hasTable('menuler')) {
            return redirect()->back()->with('error', 'menuler tablosu bulunamadı!');
        }
        
        $altMenu = DB::table('menuler')->where('id', $id)->first();
        
        if ($altMenu) {
            DB::table('menuler')->where('id', $id)->delete();
            return redirect()->route('admin.menuler.header.duzenle', $altMenu->ustid)->with('success', 'Alt menü başarıyla silindi.');
        }
        
        return redirect()->back()->with('error', 'Alt menü bulunamadı!');
    }
    
    /**
     * Navbar ayarları (Top bar elementleri)
     */
    public function navbarAyarlari()
    {
        if (!Schema::hasTable('navbar_settings')) {
            $settings = null;
        } else {
            $settings = DB::table('navbar_settings')->first();
            
            // Eğer ayar yoksa varsayılan değerlerle oluştur
            if (!$settings) {
                $defaultSettings = [
                    'show_currency_dropdown' => true,
                    'currency_options' => json_encode(['TRY', 'USD', 'EUR', 'AED']),
                    'show_language_dropdown' => true,
                    'language_options' => json_encode(['tr', 'en', 'ar']),
                    'show_login_button' => true,
                    'login_button_text_tr' => 'Giriş Yap',
                    'login_button_text_en' => 'Login',
                    'login_button_text_ar' => 'تسجيل الدخول',
                    'login_button_url' => '/giris',
                    'show_register_button' => true,
                    'register_button_text_tr' => 'Kayıt Ol',
                    'register_button_text_en' => 'Register',
                    'register_button_text_ar' => 'التسجيل',
                    'register_button_url' => '/kayit',
                    'show_account_button' => true,
                    'account_button_text_tr' => 'Hesabım',
                    'account_button_text_en' => 'My Account',
                    'account_button_text_ar' => 'حسابي',
                    'account_button_url' => '/hesabim',
                    'show_cart_button' => true,
                    'cart_button_text_tr' => 'Sepetim',
                    'cart_button_text_en' => 'Cart',
                    'cart_button_text_ar' => 'السلة',
                    'cart_button_url' => '/sepet',
                    'show_logout_button' => true,
                    'logout_button_text_tr' => 'Çıkış Yap',
                    'logout_button_text_en' => 'Logout',
                    'logout_button_text_ar' => 'تسجيل الخروج',
                    'logout_button_url' => '/cikis',
                ];
                
                DB::table('navbar_settings')->insert($defaultSettings);
                $settings = DB::table('navbar_settings')->first();
            }
            
            // JSON alanları decode et
            if ($settings) {
                $settings->currency_options = json_decode($settings->currency_options ?? '[]', true);
                $settings->language_options = json_decode($settings->language_options ?? '[]', true);
            }
        }
        
        return view('admin.menuler.navbar-ayarlari', compact('settings'));
    }
    
    /**
     * Navbar ayarları kaydet
     */
    public function navbarAyarlariKaydet(Request $request)
    {
        $request->validate([
            'show_currency_dropdown' => 'nullable|boolean',
            'currency_options' => 'nullable|array',
            'show_language_dropdown' => 'nullable|boolean',
            'language_options' => 'nullable|array',
            'show_login_button' => 'nullable|boolean',
            'login_button_text_tr' => 'nullable|string|max:255',
            'login_button_text_en' => 'nullable|string|max:255',
            'login_button_text_ar' => 'nullable|string|max:255',
            'login_button_url' => 'nullable|string|max:255',
            'show_register_button' => 'nullable|boolean',
            'register_button_text_tr' => 'nullable|string|max:255',
            'register_button_text_en' => 'nullable|string|max:255',
            'register_button_text_ar' => 'nullable|string|max:255',
            'register_button_url' => 'nullable|string|max:255',
            'show_account_button' => 'nullable|boolean',
            'account_button_text_tr' => 'nullable|string|max:255',
            'account_button_text_en' => 'nullable|string|max:255',
            'account_button_text_ar' => 'nullable|string|max:255',
            'account_button_url' => 'nullable|string|max:255',
            'show_cart_button' => 'nullable|boolean',
            'cart_button_text_tr' => 'nullable|string|max:255',
            'cart_button_text_en' => 'nullable|string|max:255',
            'cart_button_text_ar' => 'nullable|string|max:255',
            'cart_button_url' => 'nullable|string|max:255',
            'show_logout_button' => 'nullable|boolean',
            'logout_button_text_tr' => 'nullable|string|max:255',
            'logout_button_text_en' => 'nullable|string|max:255',
            'logout_button_text_ar' => 'nullable|string|max:255',
            'logout_button_url' => 'nullable|string|max:255',
        ]);
        
        if (!Schema::hasTable('navbar_settings')) {
            return redirect()->back()->with('error', 'navbar_settings tablosu bulunamadı!');
        }
        
        $data = [
            'show_currency_dropdown' => $request->has('show_currency_dropdown') ? 1 : 0,
            'currency_options' => json_encode($request->currency_options ?? ['TRY', 'USD', 'EUR', 'AED']),
            'show_language_dropdown' => $request->has('show_language_dropdown') ? 1 : 0,
            'language_options' => json_encode($request->language_options ?? ['tr', 'en', 'ar']),
            'show_login_button' => $request->has('show_login_button') ? 1 : 0,
            'login_button_text_tr' => $request->login_button_text_tr ?? 'Giriş Yap',
            'login_button_text_en' => $request->login_button_text_en ?? 'Login',
            'login_button_text_ar' => $request->login_button_text_ar ?? 'تسجيل الدخول',
            'login_button_url' => $request->login_button_url ?? '/giris',
            'show_register_button' => $request->has('show_register_button') ? 1 : 0,
            'register_button_text_tr' => $request->register_button_text_tr ?? 'Kayıt Ol',
            'register_button_text_en' => $request->register_button_text_en ?? 'Register',
            'register_button_text_ar' => $request->register_button_text_ar ?? 'التسجيل',
            'register_button_url' => $request->register_button_url ?? '/kayit',
            'show_account_button' => $request->has('show_account_button') ? 1 : 0,
            'account_button_text_tr' => $request->account_button_text_tr ?? 'Hesabım',
            'account_button_text_en' => $request->account_button_text_en ?? 'My Account',
            'account_button_text_ar' => $request->account_button_text_ar ?? 'حسابي',
            'account_button_url' => $request->account_button_url ?? '/hesabim',
            'show_cart_button' => $request->has('show_cart_button') ? 1 : 0,
            'cart_button_text_tr' => $request->cart_button_text_tr ?? 'Sepetim',
            'cart_button_text_en' => $request->cart_button_text_en ?? 'Cart',
            'cart_button_text_ar' => $request->cart_button_text_ar ?? 'السلة',
            'cart_button_url' => $request->cart_button_url ?? '/sepet',
            'show_logout_button' => $request->has('show_logout_button') ? 1 : 0,
            'logout_button_text_tr' => $request->logout_button_text_tr ?? 'Çıkış Yap',
            'logout_button_text_en' => $request->logout_button_text_en ?? 'Logout',
            'logout_button_text_ar' => $request->logout_button_text_ar ?? 'تسجيل الخروج',
            'logout_button_url' => $request->logout_button_url ?? '/cikis',
        ];
        
        $existing = DB::table('navbar_settings')->first();
        
        if ($existing) {
            DB::table('navbar_settings')->where('id', $existing->id)->update($data);
        } else {
            DB::table('navbar_settings')->insert($data);
        }
        
        return redirect()->route('admin.menuler.navbar-ayarlari')->with('success', 'Navbar ayarları başarıyla kaydedildi.');
    }
    
    public function footer()
    {
        if (!Schema::hasTable('menuler')) {
            $menuler = collect([]);
        } else {
            $menuler = DB::table('menuler')->where('tip', 'footer')->orderBy('sira', 'asc')->get();
        }
        return view('admin.menuler.footer', compact('menuler'));
    }
    
    public function footerEkle(Request $request)
    {
        $request->validate([
            'ad' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'sira' => 'nullable|integer',
            'durum' => 'nullable|integer',
        ]);
        
        if (!Schema::hasTable('menuler')) {
            return redirect()->back()->with('error', 'menuler tablosu bulunamadı!');
        }
        
        DB::table('menuler')->insert([
            'tip' => 'footer',
            'menu_isim' => $request->ad,
            'adi' => $request->ad,
            'link' => $request->link,
            'menu_url' => $request->link,
            'sira' => $request->sira ?? 0,
            'durum' => $request->durum ?? 1,
        ]);
        
        return redirect()->route('admin.menuler.footer')->with('success', 'Footer menü başarıyla eklendi.');
    }
}

