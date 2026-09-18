<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BayiDestekController extends Controller
{
    /**
     * Destek Talepleri (Tickets)
     */
    public function tickets()
    {
        $bayiId = session('admin_id');
        
        // Destek talepleri
        $tickets = DB::table('bayi_destek_tickets')
            ->where('bayi_id', $bayiId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('admin.bayi.destek.tickets', compact('tickets'));
    }
    
    /**
     * Ticket Oluştur (Form)
     */
    public function ticketOlustur()
    {
        // Kategoriler - çeviri destekli
        $kategoriler = [
            'teknik' => __('messages.technical_support'),
            'satis' => __('messages.sales_support'),
            'odeme' => __('messages.payment_issues'),
            'genel' => __('messages.general'),
        ];
        
        return view('admin.bayi.destek.ticket-olustur', compact('kategoriler'));
    }
    
    /**
     * Ticket Oluştur (Post)
     */
    public function ticketOlusturPost(Request $request)
    {
        $request->validate([
            'konu' => 'required',
            'kategori' => 'required',
            'oncelik' => 'required|in:dusuk,normal,yuksek,acil',
            'mesaj' => 'required',
            'dosya' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,rar|max:10240',
        ]);
        
        $bayiId = session('admin_id');
        
        // İlk mesaj için dosya yükleme (varsa) - public/tema/uploads/destek
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
        
        $ticketId = DB::table('bayi_destek_tickets')->insertGetId([
            'bayi_id' => $bayiId,
            'konu' => $request->konu,
            'kategori' => $request->kategori,
            'oncelik' => $request->oncelik,
            'durum' => 'acik',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // İlk mesajı ekle
        DB::table('bayi_destek_mesajlar')->insert([
            'ticket_id' => $ticketId,
            'gonderen_id' => $bayiId,
            'gonderen_tip' => 'bayi',
            'mesaj' => $request->mesaj,
            'dosya' => $dosyaYolu,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('admin.bayi.destek.ticket.detay', $ticketId)->with('success', 'Destek talebi oluşturuldu!');
    }
    
    /**
     * Ticket Detay
     */
    public function ticketDetay($id)
    {
        $bayiId = session('admin_id');
        
        // Ticket bilgisi
        $ticket = DB::table('bayi_destek_tickets')
            ->where('id', $id)
            ->where('bayi_id', $bayiId)
            ->first();
        
        if (!$ticket) {
            return redirect()->route('admin.bayi.destek.tickets')->with('error', 'Ticket bulunamadı!');
        }
        
        // Mesajlar
        $mesajlar = DB::table('bayi_destek_mesajlar')
            ->where('ticket_id', $id)
            ->orderBy('created_at', 'asc')
            ->get();
        
        // Okundu olarak işaretle
        DB::table('bayi_destek_tickets')
            ->where('id', $id)
            ->update(['okundu' => 1]);
        
        return view('admin.bayi.destek.ticket-detay', compact('ticket', 'mesajlar'));
    }
    
    /**
     * Ticket'a Cevap Ver
     */
    public function ticketCevap(Request $request, $id)
    {
        $request->validate([
            'mesaj' => 'required',
            'dosya' => 'nullable|file|mimes:jpg,jpeg,png,gif,pdf,doc,docx,zip,rar|max:10240',
        ]);
        
        $bayiId = session('admin_id');
        
        // Mesaj için dosya yükleme (varsa) - public/tema/uploads/destek
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
        
        // Mesaj ekle
        DB::table('bayi_destek_mesajlar')->insert([
            'ticket_id' => $id,
            'gonderen_id' => $bayiId,
            'gonderen_tip' => 'bayi',
            'mesaj' => $request->mesaj,
            'dosya' => $dosyaYolu,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // Ticket güncelle
        DB::table('bayi_destek_tickets')
            ->where('id', $id)
            ->update([
                'durum' => 'cevaplandi',
                'updated_at' => now(),
            ]);
        
        return redirect()->route('admin.bayi.destek.ticket.detay', $id)->with('success', 'Cevabınız gönderildi!');
    }
    
    /**
     * Sık Sorulan Sorular
     */
    public function sss()
    {
        // SSS listesi - çeviri destekli
        $sorular = [
            [
                'soru' => __('messages.faq_q1'),
                'cevap' => __('messages.faq_a1'),
            ],
            [
                'soru' => __('messages.faq_q2'),
                'cevap' => __('messages.faq_a2'),
            ],
            [
                'soru' => __('messages.faq_q3'),
                'cevap' => __('messages.faq_a3'),
            ],
            [
                'soru' => __('messages.faq_q4'),
                'cevap' => __('messages.faq_a4'),
            ],
            [
                'soru' => __('messages.faq_q5'),
                'cevap' => __('messages.faq_a5'),
            ],
            [
                'soru' => __('messages.faq_q6'),
                'cevap' => __('messages.faq_a6'),
            ],
        ];
        
        return view('admin.bayi.destek.sss', compact('sorular'));
    }
    
}

