<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Verified;
use App\Models\Uye;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /**
     * Email doğrulama sayfasını göster
     */
    public function notice()
    {
        $uye = Auth::guard('uye')->user();
        
        if ($uye && $uye->hasVerifiedEmail()) {
            return redirect()->route('hesabim')->with('info', 'E-posta adresiniz zaten doğrulanmış.');
        }
        
        return view('tema.email-verification-notice');
    }

    /**
     * Email doğrulama linkini işle
     */
    public function verify(Request $request, $id, $hash)
    {
        $uye = Uye::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($uye->email))) {
            abort(403, 'Geçersiz doğrulama linki.');
        }

        if ($uye->hasVerifiedEmail()) {
            return redirect()->route('giris')->with('info', 'E-posta adresiniz zaten doğrulanmış. Giriş yapabilirsiniz.');
        }

        if ($uye->markEmailAsVerified()) {
            event(new Verified($uye));
        }

        return redirect()->route('giris')->with('success', 'E-posta adresiniz başarıyla doğrulandı! Şimdi giriş yapabilirsiniz.');
    }

    /**
     * Yeni doğrulama emaili gönder
     */
    public function resend(Request $request)
    {
        $uye = Auth::guard('uye')->user();

        if ($uye->hasVerifiedEmail()) {
            return redirect()->route('hesabim')->with('info', 'E-posta adresiniz zaten doğrulanmış.');
        }

        $uye->sendEmailVerificationNotification();

        return back()->with('success', 'Doğrulama e-postası tekrar gönderildi. Lütfen e-posta kutunuzu kontrol edin.');
    }
}
