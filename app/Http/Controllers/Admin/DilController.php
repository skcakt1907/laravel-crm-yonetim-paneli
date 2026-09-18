<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DilController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('diller')) {
            // Tablo yoksa boş paginator döndür
            $diller = \Illuminate\Pagination\LengthAwarePaginator::make([], 0, 20);
        } else {
            // Kolon isimlerini kontrol et ve uygun kolonları seç
            $columns = Schema::getColumnListing('diller');
            $selectColumns = ['id'];
            
            // Ad kolonu için olası isimleri kontrol et
            if (in_array('ad', $columns)) {
                $selectColumns[] = 'ad';
            } elseif (in_array('adi', $columns)) {
                $selectColumns[] = 'adi';
            } elseif (in_array('isim', $columns)) {
                $selectColumns[] = 'isim';
            } elseif (in_array('name', $columns)) {
                $selectColumns[] = 'name';
            } elseif (in_array('dil_adi', $columns)) {
                $selectColumns[] = 'dil_adi';
            }
            
            // Kod kolonu için olası isimleri kontrol et
            if (in_array('kod', $columns)) {
                $selectColumns[] = 'kod';
            } elseif (in_array('dil_kodu', $columns)) {
                $selectColumns[] = 'dil_kodu';
            } elseif (in_array('code', $columns)) {
                $selectColumns[] = 'code';
            }
            
            // Durum kolonu
            if (in_array('durum', $columns)) {
                $selectColumns[] = 'durum';
            }
            
            $diller = DB::table('diller')
                ->select($selectColumns)
                ->orderBy('id', 'asc')
                ->paginate(20);
            
            // Eğer hiç dil yoksa varsayılan dilleri ekle
            if ($diller->isEmpty()) {
                $this->ekleVarsayilanDiller();
                // Tekrar çek
                $diller = DB::table('diller')
                    ->select($selectColumns)
                    ->orderBy('id', 'asc')
                    ->paginate(20);
            }
        }
        
        return view('admin.diller.index', compact('diller'));
    }
    
    private function ekleVarsayilanDiller()
    {
        if (!Schema::hasTable('diller')) {
            return;
        }
        
        $columns = Schema::getColumnListing('diller');
        $adColumn = 'ad';
        $kodColumn = 'kod';
        $durumColumn = 'durum';
        
        // Kolon isimlerini bul
        if (in_array('ad', $columns)) {
            $adColumn = 'ad';
        } elseif (in_array('adi', $columns)) {
            $adColumn = 'adi';
        } elseif (in_array('isim', $columns)) {
            $adColumn = 'isim';
        } elseif (in_array('name', $columns)) {
            $adColumn = 'name';
        } elseif (in_array('dil_adi', $columns)) {
            $adColumn = 'dil_adi';
        }
        
        if (in_array('kod', $columns)) {
            $kodColumn = 'kod';
        } elseif (in_array('dil_kodu', $columns)) {
            $kodColumn = 'dil_kodu';
        } elseif (in_array('code', $columns)) {
            $kodColumn = 'code';
        }
        
        // Mevcut dilleri kontrol et
        $mevcutDiller = DB::table('diller')->pluck($kodColumn)->toArray();
        
        $varsayilanDiller = [
            ['ad' => 'Türkçe', 'kod' => 'tr'],
            ['ad' => 'English', 'kod' => 'en'],
            ['ad' => 'العربية', 'kod' => 'ar'],
        ];
        
        foreach ($varsayilanDiller as $dil) {
            if (!in_array($dil['kod'], $mevcutDiller)) {
                $insertData = [
                    $adColumn => $dil['ad'],
                    $kodColumn => $dil['kod'],
                ];
                
                if (in_array('durum', $columns)) {
                    $insertData['durum'] = 1;
                }
                
                DB::table('diller')->insert($insertData);
            }
        }
    }
    
    public function ekle()
    {
        return view('admin.diller.ekle');
    }
    
    public function eklePost(Request $request)
    {
        $request->validate([
            'ad' => 'required|string|max:255',
            'kod' => 'required|string|max:10',
        ]);
        
        if (!Schema::hasTable('diller')) {
            return redirect()->back()->with('error', 'diller tablosu bulunamadı!');
        }
        
        $columns = Schema::getColumnListing('diller');
        $insertData = [];
        
        // Ad kolonu için uygun kolonu bul
        if (in_array('ad', $columns)) {
            $insertData['ad'] = $request->ad;
        } elseif (in_array('adi', $columns)) {
            $insertData['adi'] = $request->ad;
        } elseif (in_array('isim', $columns)) {
            $insertData['isim'] = $request->ad;
        } elseif (in_array('name', $columns)) {
            $insertData['name'] = $request->ad;
        } elseif (in_array('dil_adi', $columns)) {
            $insertData['dil_adi'] = $request->ad;
        }
        
        // Kod kolonu için uygun kolonu bul
        if (in_array('kod', $columns)) {
            $insertData['kod'] = $request->kod;
        } elseif (in_array('dil_kodu', $columns)) {
            $insertData['dil_kodu'] = $request->kod;
        } elseif (in_array('code', $columns)) {
            $insertData['code'] = $request->kod;
        }
        
        // Durum kolonu
        if (in_array('durum', $columns)) {
            $insertData['durum'] = $request->durum ?? 1;
        }

        // sira default - zorunlu kolon
        if (in_array('sira', $columns) && !isset($insertData['sira'])) {
            $maxSira = DB::table('diller')->max('sira');
            $insertData['sira'] = ((int) $maxSira) + 1;
        }

        // anadil default
        if (in_array('anadil', $columns) && !isset($insertData['anadil'])) {
            $insertData['anadil'] = 0;
        }

        DB::table('diller')->insert($insertData);

        return redirect()->route('admin.diller.index')->with('success', 'Dil başarıyla eklendi.');
    }

    /**
     * Dil silme
     */
    public function sil($id)
    {
        if (!Schema::hasTable('diller')) {
            return redirect()->back()->with('error', 'diller tablosu bulunamadı!');
        }

        // Anadil siliniyorsa engelle
        $dil = DB::table('diller')->where('id', $id)->first();
        if (!$dil) {
            return redirect()->route('admin.diller.index')->with('error', 'Dil bulunamadı.');
        }
        if (!empty($dil->anadil)) {
            return redirect()->route('admin.diller.index')->with('error', 'Anadil silinemez.');
        }

        DB::table('diller')->where('id', $id)->delete();
        return redirect()->route('admin.diller.index')->with('success', 'Dil silindi.');
    }

    /**
     * Dil durum aktif/pasif
     */
    public function durumDegistir($id)
    {
        $dil = DB::table('diller')->where('id', $id)->first();
        if (!$dil) return redirect()->back()->with('error', 'Dil bulunamadı.');
        DB::table('diller')->where('id', $id)->update(['durum' => $dil->durum ? 0 : 1]);
        return redirect()->back()->with('success', 'Durum güncellendi.');
    }
}

