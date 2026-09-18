<?php

namespace App\Http\Controllers\Admin\CRM;

use App\Http\Controllers\Controller;
use App\Models\Kanban\KanbanBoard;
use App\Models\Kanban\KanbanList;
use App\Models\Kanban\KanbanCard;
use App\Models\Kanban\KanbanCardComment;
use App\Models\Kanban\KanbanCardChecklist;
use App\Models\Kanban\KanbanChecklistItem;
use App\Models\Kanban\KanbanCardAttachment;
use App\Models\Kanban\KanbanCardMember;
use App\Models\Kanban\KanbanBoardMember;
use App\Models\Kanban\KanbanCardActivity;
use App\Models\Yonetici;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class KanbanBoardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $adminId = session('admin_id');

        // Kullanıcının oluşturduğu veya üye olduğu board'ları getir
        // FIX: Arşivlenmiş kartlar board listesinde sayılmasın
        $withRelations = [
            'olusturan',
            'lists.cards' => function($q) { $q->whereNull('archived_at'); },
        ];

        if (Schema::hasTable('kanban_board_members')) {
            $withRelations[] = 'members';
        }

        // durum=0 olanlar arşivlenmiş (silinmiş) sayılır, listede gösterilmez.
        // Görünürlük: ortak panolar (ozel=0) herkese; özel panolar sahibine + üyelerine.
        $query = KanbanBoard::with($withRelations)
            ->where('durum', 1)
            ->where(function ($q) use ($adminId) {
                $q->where('ozel', 0)->orWhere('olusturan_id', $adminId);
                if (Schema::hasTable('kanban_board_members')) {
                    $q->orWhereHas('members', function ($m) use ($adminId) {
                        $m->where('yonetici_id', $adminId);
                    });
                }
            });

        $boards = $query->orderBy('kategori')->orderBy('created_at', 'desc')->get();

        // Board'ları kategorilere göre grupla
        $kategoriler = $boards->groupBy(function($board) {
            return $board->kategori ?: 'Kategorisiz';
        })->sortKeys();

        return view('admin.crm.kanban.index', compact('boards', 'kategoriler'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $mevcutKategoriler = KanbanBoard::whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->pluck('kategori')
            ->sort()
            ->values();

        return view('admin.crm.kanban.create', compact('mevcutKategoriler'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'kategori' => 'nullable|string|max:255',
            'renk' => 'nullable|string|max:7',
        ]);

        $board = KanbanBoard::create([
            'adi' => $validated['adi'],
            'aciklama' => $validated['aciklama'] ?? null,
            'kategori' => $validated['kategori'] ?? null,
            'renk' => $validated['renk'] ?? '#3498db',
            'olusturan_id' => session('admin_id'),
            'durum' => 1,
            'ozel' => $request->boolean('ozel') ? 1 : 0,
        ]);

        // Varsayılan listeleri oluştur
        $defaultLists = ['Yapılacaklar', 'Devam Ediyor', 'Tamamlandı'];
        foreach ($defaultLists as $index => $listName) {
            KanbanList::create([
                'board_id' => $board->id,
                'adi' => $listName,
                'sira' => $index,
                'durum' => 1,
            ]);
        }

        // Pano kapak görseli (yüklendiyse)
        $this->handleBoardCover($request, $board);

        // AJAX isteği ise JSON döndür
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Proje panosu başarıyla oluşturuldu!',
                'redirect' => route('admin.crm.kanban.show', $board->id),
                'id' => $board->id
            ]);
        }

        return redirect()->route('admin.crm.kanban.show', $board->id)
            ->with('success', 'Proje panosu başarıyla oluşturuldu!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $adminId = session('admin_id');

        // Önce tablo kontrolü yap, yoksa relation'ları ekleme
        $safeRelations = [
            'lists.cards' => function($query) {
                // FIX: Arşivlenmiş kartları board görünümünde gösterme
                $query->whereNull('archived_at')->orderBy('sira');
            },
            'lists.cards.atanan',
            'olusturan',
        ];

        // Tablolar varsa relation'ları ekle
        if (Schema::hasTable('kanban_card_members')) {
            $safeRelations[] = 'lists.cards.members.yonetici';
        }

        if (Schema::hasTable('kanban_board_members')) {
            $safeRelations[] = 'members.yonetici';
        }

        $board = KanbanBoard::with($safeRelations)
            ->findOrFail($id);

        // Tablo yoksa boş collection'ları manuel olarak ayarla
        if (!Schema::hasTable('kanban_card_members')) {
            foreach ($board->lists as $list) {
                foreach ($list->cards as $card) {
                    $card->setRelation('members', collect());
                }
            }
        }

        // Erişim kontrolü: tüm yöneticiler tüm panoları görüntüleyebilir (açma engeli kaldırıldı).

        // Kart üyesi / atanan dropdown'ı: TÜM aktif yöneticiler listelenir.
        $bayiRolIds = [];
        try {
            if (Schema::hasTable('roller')) {
                $bayiRolIds = DB::table('roller')->where('slug', 'bayi')->pluck('id')->all();
            }
        } catch (\Throwable $e) {}

        $yoneticiler = Yonetici::where('durum', 1)
            ->when(!empty($bayiRolIds), function ($q) use ($bayiRolIds) {
                $q->whereNotIn('rol', $bayiRolIds);
            })
            ->orderBy('adi')
            ->get(['id', 'adi', 'kullaniciadi']);

        // Özel pano erişim kontrolü (ozel=0 ise herkes; ozel=1 ise sahip/üye)
        if (!$board->canAccess(session('admin_id'))) {
            abort(403, 'Bu panoya erişim yetkiniz yok.');
        }

        // Kilitliyse sadece sahibi düzenler; özelse sahip/üye; aksi halde herkes.
        $canEdit = $board->canEdit(session('admin_id'));

        return view('admin.crm.kanban.show', compact('board', 'yoneticiler', 'canEdit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $board = KanbanBoard::findOrFail($id);

        // FIX: Mevcut kategorileri blade'e gönder (create() ile aynı mantık)
        $mevcutKategoriler = KanbanBoard::whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->pluck('kategori')
            ->sort()
            ->values();

        return view('admin.crm.kanban.edit', compact('board', 'mevcutKategoriler'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $board = KanbanBoard::findOrFail($id);

        $validated = $request->validate([
            'adi' => 'required|string|max:255',
            'aciklama' => 'nullable|string',
            'kategori' => 'nullable|string|max:255',
            'renk' => 'nullable|string|max:7',
            'durum' => 'nullable|boolean',
        ]);

        // Özel/gizli ayarı SADECE panoyu oluşturan değiştirebilir.
        // Başkası düzenlediğinde sahibinin ozel ayarına dokunulmaz.
        if ($board->olusturan_id == session('admin_id')) {
            $validated['ozel'] = $request->boolean('ozel') ? 1 : 0;
        }

        $board->update($validated);

        // Pano kapak görseli (yükle / değiştir / kaldır)
        $this->handleBoardCover($request, $board);

        // Düzenleme sonrası kanban ANA SAYFASINA dön (board içine değil)
        return redirect()->route('admin.crm.kanban.index')
            ->with('success', 'Proje panosu güncellendi!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $board = KanbanBoard::findOrFail($id);
        // Kalıcı silmek yerine ARŞİVLE (yanlışlıkla silmeye karşı). durum=0 yapılır;
        // listeler/kartlar korunur, gerekirse durum=1 ile geri getirilir.
        $board->update(['durum' => 0]);

        return redirect()->route('admin.crm.kanban.index')
            ->with('success', 'Proje panosu arşivlendi! (Geri getirilebilir)');
    }

    /**
     * Panoyu kilitle / kilidi aç (yalnızca panoyu oluşturan).
     * Kilitliyken sadece sahibi düzenleyebilir (canEdit kontrolü model'de).
     */
    public function toggleLock($boardId)
    {
        $board = KanbanBoard::findOrFail($boardId);

        if ($board->olusturan_id != session('admin_id')) {
            return response()->json([
                'success' => false,
                'message' => 'Kilidi yalnızca panoyu oluşturan değiştirebilir.',
            ], 403);
        }

        $board->kilitli = !$board->kilitli;
        $board->save();

        return response()->json([
            'success' => true,
            'kilitli' => (bool) $board->kilitli,
            'message' => $board->kilitli ? 'Pano kilitlendi.' : 'Pano kilidi açıldı.',
        ]);
    }

    /**
     * Liste oluştur
     */
    public function storeList(Request $request, $boardId)
    {
        try {
            $board = KanbanBoard::findOrFail($boardId);

            $validated = $request->validate([
                'adi' => 'required|string|max:255',
                'aciklama' => 'nullable|string',
                'renk' => 'nullable|string|max:7',
            ]);

            $maxSira = KanbanList::where('board_id', $boardId)->max('sira') ?? -1;

            $list = KanbanList::create([
                'board_id' => $boardId,
                'adi' => $validated['adi'],
                'aciklama' => $validated['aciklama'] ?? null,
                'renk' => $validated['renk'] ?? null,
                'sira' => $maxSira + 1,
                'durum' => 1,
            ]);

            return response()->json([
                'success' => true,
                'list' => $list->load('cards'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasyon hatasi',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Kanban liste ekleme hatasi: ' . $e->getMessage(), [
                'boardId' => $boardId,
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste güncelle
     */
    public function updateList(Request $request, $boardId, $listId)
    {
        $list = KanbanList::where('board_id', $boardId)->findOrFail($listId);

        $validated = $request->validate([
            'adi' => 'sometimes|required|string|max:255',
            'aciklama' => 'nullable|string',
            'renk' => 'nullable|string|max:7',
        ]);

        $list->update($validated);

        return response()->json([
            'success' => true,
            'list' => $list,
        ]);
    }

    /**
     * Liste sil
     */
    public function destroyList($boardId, $listId)
    {
        $list = KanbanList::where('board_id', $boardId)->findOrFail($listId);

        // Listedeki tüm kartları sil (cascade)
        $cardCount = 0;
        if (method_exists($list, 'cards')) {
            $cardCount = $list->cards()->count();
            $list->cards()->delete();
        }

        $list->delete();

        return response()->json([
            'success' => true,
            'message' => 'Liste silindi' . ($cardCount > 0 ? " ({$cardCount} kart da silindi)" : '') . '!',
            'deleted_cards' => $cardCount,
        ]);
    }

    /**
     * Kart oluştur
     */
    public function storeCard(Request $request, $boardId, $listId)
    {
        $list = KanbanList::where('board_id', $boardId)->findOrFail($listId);

        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
            'oncelik' => 'nullable|in:dusuk,normal,yuksek,acil',
            'durum' => 'nullable|in:aktif,tamamlandi,iptal,durduruldu,arsiv',
        ]);

        $maxSira = KanbanCard::where('list_id', $listId)->max('sira') ?? -1;

        $card = KanbanCard::create([
            'list_id' => $listId,
            'baslik' => $validated['baslik'],
            'oncelik' => $validated['oncelik'] ?? 'normal',
            'durum' => $validated['durum'] ?? 'aktif',
            'sira' => $maxSira + 1,
        ]);

        return response()->json([
            'success' => true,
            'card' => $card->load('atanan'),
        ]);
    }

    /**
     * Kart güncelle
     */
    public function updateCard(Request $request, $boardId, $listId, $cardId)
    {
        $card = KanbanCard::where('list_id', $listId)
            ->whereHas('list', function($q) use ($boardId) {
                $q->where('board_id', $boardId);
            })
            ->findOrFail($cardId);

        $validated = $request->validate([
            'baslik' => 'sometimes|required|string|max:255',
            'aciklama' => 'nullable|string',
            'atanan_id' => 'nullable|integer|exists:yoneticiler,id',
            'baslangic_tarihi' => 'nullable|date',
            'son_tarih' => 'nullable|date',
            'oncelik' => 'nullable|in:dusuk,normal,yuksek,acil',
            'durum' => 'nullable|in:aktif,tamamlandi,iptal',
            'etiketler' => 'nullable',
            'etiketler.*' => 'string|max:100',
            'renk' => 'nullable|string|max:50',
        ]);

        // FIX: Etiketler ne formatta gelirse gelsin, JSON string olarak DB'ye yaz
        if (array_key_exists('etiketler', $validated)) {
            if (is_array($validated['etiketler'])) {
                $validated['etiketler'] = json_encode(array_values(array_filter($validated['etiketler'], fn($v) => is_string($v) && trim($v) !== '')));
            } elseif (is_string($validated['etiketler'])) {
                $trimmed = trim($validated['etiketler']);
                if ($trimmed === '') {
                    $validated['etiketler'] = json_encode([]);
                } else {
                    $decoded = json_decode($trimmed, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $validated['etiketler'] = json_encode($decoded);
                    } else {
                        $arr = array_map('trim', explode(',', $trimmed));
                        $arr = array_filter($arr, fn($v) => $v !== '');
                        $validated['etiketler'] = json_encode(array_values($arr));
                    }
                }
            }
        }

        // Başlangıç tarihi: model fillable'ına bağımlı kalmamak için açıkça ata (kolon varsa)
        $baslangicGeldi = array_key_exists('baslangic_tarihi', $validated);
        $baslangicDeger = $validated['baslangic_tarihi'] ?? null;
        unset($validated['baslangic_tarihi']);

        $card->update($validated);

        if ($baslangicGeldi && Schema::hasColumn('kanban_cards', 'baslangic_tarihi')) {
            $card->baslangic_tarihi = $baslangicDeger ?: null;
            $card->save();
        }

        KanbanCardActivity::create([
            'card_id' => $cardId,
            'yazar_id' => session('admin_id'),
            'tip' => 'kart_guncellendi',
            'aciklama' => 'Kart güncellendi',
        ]);

        return response()->json([
            'success' => true,
            'card' => $card->load('atanan'),
        ]);
    }

    /**
     * Kart sil
     */
    public function destroyCard($boardId, $listId, $cardId)
    {
        $card = KanbanCard::where('list_id', $listId)
            ->whereHas('list', function($q) use ($boardId) {
                $q->where('board_id', $boardId);
            })
            ->findOrFail($cardId);

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kart silindi!',
        ]);
    }

    /**
     * Drag & Drop - Kart taşıma
     */
    public function moveCard(Request $request, $boardId)
    {
        $validated = $request->validate([
            'card_id'        => 'required|integer|exists:kanban_cards,id',
            'new_list_id'    => 'required_without:hedef_list_id|nullable|integer|exists:kanban_lists,id',
            'hedef_list_id'  => 'required_without:new_list_id|nullable|integer|exists:kanban_lists,id',
            'new_position'   => 'nullable|integer|min:0',
        ]);

        $newListId = $validated['new_list_id'] ?? $validated['hedef_list_id'] ?? null;
        if (!$newListId) {
            return response()->json(['success' => false, 'message' => 'Hedef liste gerekli'], 422);
        }

        $card = KanbanCard::findOrFail($validated['card_id']);
        $oldListId = $card->list_id;

        if (!isset($validated['new_position']) || $validated['new_position'] === null) {
            $maxSira = KanbanCard::where('list_id', $newListId)->max('sira') ?? -1;
            $newPosition = $maxSira + 1;
        } else {
            $newPosition = (int) $validated['new_position'];
        }

        DB::transaction(function() use ($card, $oldListId, $newListId, $newPosition) {
            if ($oldListId != $newListId) {
                KanbanCard::where('list_id', $oldListId)
                    ->where('sira', '>', $card->sira)
                    ->decrement('sira');
            }

            if ($oldListId == $newListId) {
                if ($card->sira < $newPosition) {
                    KanbanCard::where('list_id', $newListId)
                        ->where('sira', '>', $card->sira)
                        ->where('sira', '<=', $newPosition)
                        ->decrement('sira');
                } else {
                    KanbanCard::where('list_id', $newListId)
                        ->where('sira', '>=', $newPosition)
                        ->where('sira', '<', $card->sira)
                        ->increment('sira');
                }
            } else {
                KanbanCard::where('list_id', $newListId)
                    ->where('sira', '>=', $newPosition)
                    ->increment('sira');
            }

            $card->update([
                'list_id' => $newListId,
                'sira'    => $newPosition,
            ]);
        });

        KanbanCardActivity::create([
            'card_id' => $card->id,
            'yazar_id' => session('admin_id'),
            'tip' => 'kart_tasindi',
            'aciklama' => 'Kart başka listeye taşındı',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kart taşındı!',
        ]);
    }

    /**
     * Drag & Drop - Liste sıralama
     */
    public function reorderLists(Request $request, $boardId)
    {
        $validated = $request->validate([
            'list_ids' => 'required|array',
            'list_ids.*' => 'integer|exists:kanban_lists,id',
        ]);

        DB::transaction(function() use ($boardId, $validated) {
            foreach ($validated['list_ids'] as $index => $listId) {
                KanbanList::where('id', $listId)
                    ->where('board_id', $boardId)
                    ->update(['sira' => $index]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Liste sıralaması güncellendi!',
        ]);
    }

    /**
     * Kart detayını getir (Trello benzeri modal için)
     */
    public function getCardDetails($boardId, $cardId)
    {
        $safeRelations = [
            'list',
            'atanan',
        ];

        if (Schema::hasTable('kanban_card_comments')) {
            $safeRelations[] = 'comments.yazar';
        }
        if (Schema::hasTable('kanban_card_checklists')) {
            $safeRelations[] = 'checklists.items';
        }
        if (Schema::hasTable('kanban_card_attachments')) {
            $safeRelations[] = 'attachments.yukleyen';
        }
        if (Schema::hasTable('kanban_card_members')) {
            $safeRelations[] = 'members.yonetici';
        }
        if (Schema::hasTable('kanban_card_activities')) {
            $safeRelations[] = 'activities.yazar';
        }

        $card = KanbanCard::with($safeRelations)
        ->whereHas('list', function($q) use ($boardId) {
            $q->where('board_id', $boardId);
        })
        ->findOrFail($cardId);

        if (!Schema::hasTable('kanban_card_comments')) {
            $card->setRelation('comments', collect());
        }
        if (!Schema::hasTable('kanban_card_checklists')) {
            $card->setRelation('checklists', collect());
        }
        if (!Schema::hasTable('kanban_card_attachments')) {
            $card->setRelation('attachments', collect());
        }
        if (!Schema::hasTable('kanban_card_members')) {
            $card->setRelation('members', collect());
        }
        if (!Schema::hasTable('kanban_card_activities')) {
            $card->setRelation('activities', collect());
        }

        // Eklere indirme URL'si (dosya_url) iliştir — ilk açılışta da tıklanınca insin
        if ($card->relationLoaded('attachments') && $card->attachments) {
            $card->attachments->each(function ($a) {
                $a->dosya_url = $this->ekDosyaUrl($a->dosya_yolu);
            });
        }

        return response()->json([
            'success' => true,
            'card' => $card,
        ]);
    }

    /**
     * Yorum ekle
     */
    public function addComment(Request $request, $boardId, $cardId)
    {
        $validated = $request->validate([
            'mesaj' => 'required_without:yorum|nullable|string|max:5000',
            'yorum' => 'required_without:mesaj|nullable|string|max:5000',
        ]);

        $metin = $validated['mesaj'] ?? $validated['yorum'] ?? null;
        if (!$metin || trim($metin) === '') {
            return response()->json(['success' => false, 'message' => 'Yorum boş olamaz'], 422);
        }

        $card = KanbanCard::whereHas('list', function($q) use ($boardId) {
            $q->where('board_id', $boardId);
        })->findOrFail($cardId);

        KanbanCardComment::create([
            'card_id' => $cardId,
            'yazar_id' => session('admin_id'),
            'yorum' => $metin,
        ]);

        KanbanCardActivity::create(['card_id' => $cardId, 'yazar_id' => session('admin_id'), 'tip' => 'yorum_eklendi', 'aciklama' => 'Yorum eklendi']);

        $comments = KanbanCardComment::with('yazar')
            ->where('card_id', $cardId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments,
        ]);
    }

    /**
     * Yorum sil
     */
    public function deleteComment($boardId, $cardId, $commentId)
    {
        $comment = KanbanCardComment::where('card_id', $cardId)
            ->where('id', $commentId)
            ->where('yazar_id', session('admin_id'))
            ->firstOrFail();

        $comment->delete();

        $comments = KanbanCardComment::with('yazar')
            ->where('card_id', $cardId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'comments' => $comments,
            'message' => 'Yorum silindi!',
        ]);
    }

    /**
     * Checklist oluştur
     */
    public function createChecklist(Request $request, $boardId, $cardId)
    {
        $validated = $request->validate([
            'baslik' => 'required|string|max:255',
        ]);

        $card = KanbanCard::whereHas('list', function($q) use ($boardId) {
            $q->where('board_id', $boardId);
        })->findOrFail($cardId);

        $maxSira = KanbanCardChecklist::where('card_id', $cardId)->max('sira') ?? -1;

        KanbanCardChecklist::create([
            'card_id' => $cardId,
            'baslik' => $validated['baslik'],
            'sira' => $maxSira + 1,
        ]);

        $checklists = KanbanCardChecklist::with('items')
            ->where('card_id', $cardId)
            ->orderBy('sira')
            ->get();

        return response()->json([
            'success' => true,
            'checklists' => $checklists,
        ]);
    }

    /**
     * Checklist item ekle
     */
    public function addChecklistItem(Request $request, $boardId, $cardId, $checklistId)
    {
        $validated = $request->validate([
            'metin' => 'required|string|max:500',
        ]);

        $checklist = KanbanCardChecklist::where('card_id', $cardId)
            ->findOrFail($checklistId);

        $maxSira = KanbanChecklistItem::where('checklist_id', $checklistId)->max('sira') ?? -1;

        KanbanChecklistItem::create([
            'checklist_id' => $checklistId,
            'metin' => $validated['metin'],
            'sira' => $maxSira + 1,
        ]);

        $checklists = KanbanCardChecklist::with('items')
            ->where('card_id', $cardId)
            ->orderBy('sira')
            ->get();

        return response()->json([
            'success' => true,
            'checklists' => $checklists,
        ]);
    }

    /**
     * Checklist sil
     */
    public function deleteChecklist($boardId, $cardId, $checklistId)
    {
        $checklist = KanbanCardChecklist::where('card_id', $cardId)
            ->findOrFail($checklistId);

        $checklist->delete();

        $checklists = KanbanCardChecklist::with('items')
            ->where('card_id', $cardId)
            ->orderBy('sira')
            ->get();

        return response()->json([
            'success' => true,
            'checklists' => $checklists,
            'message' => 'Checklist silindi!',
        ]);
    }

    /**
     * Checklist item toggle (tamamlandı/tamamlanmadı)
     */
    public function toggleChecklistItem($boardId, $cardId, $checklistId, $itemId)
    {
        $item = KanbanChecklistItem::where('checklist_id', $checklistId)
            ->findOrFail($itemId);

        $newStatus = !$item->tamamlandi;
        $item->update([
            'tamamlandi' => $newStatus,
            'tamamlayan_id' => $newStatus ? session('admin_id') : null,
            'tamamlanma_tarihi' => $newStatus ? now() : null,
        ]);

        $checklists = KanbanCardChecklist::with('items')
            ->where('card_id', $cardId)
            ->orderBy('sira')
            ->get();

        return response()->json([
            'success' => true,
            'checklists' => $checklists,
        ]);
    }

    /**
     * Dosya ekle
     */
    public function addAttachment(Request $request, $boardId, $cardId)
    {
        $validated = $request->validate([
            'dosya' => 'required|file|max:10240', // 10MB max
            'aciklama' => 'nullable|string|max:500',
        ]);

        $card = KanbanCard::whereHas('list', function($q) use ($boardId) {
            $q->where('board_id', $boardId);
        })->findOrFail($cardId);

        $file = $request->file('dosya');
        // Symlink gerektirmemesi için DOĞRUDAN public/ altına kaydet.
        $temizAd = preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $fileName = time() . '_' . $temizAd;
        $dosyaTipi = $file->getClientMimeType();
        $dosyaBoyutu = $file->getSize();
        $klasor = public_path('tema/uploads/kanban');
        if (!is_dir($klasor)) { @mkdir($klasor, 0755, true); }
        $file->move($klasor, $fileName);
        $filePath = 'tema/uploads/kanban/' . $fileName;

        $attachment = KanbanCardAttachment::create([
            'card_id' => $cardId,
            'yukleyen_id' => session('admin_id'),
            'dosya_adi' => $file->getClientOriginalName(),
            'dosya_yolu' => $filePath,
            'dosya_tipi' => $dosyaTipi,
            'dosya_boyutu' => $dosyaBoyutu,
            'aciklama' => $validated['aciklama'] ?? null,
        ]);

        KanbanCardActivity::create(['card_id' => $cardId, 'yazar_id' => session('admin_id'), 'tip' => 'dosya_eklendi', 'aciklama' => 'Dosya eklendi: ' . $file->getClientOriginalName()]);

        return response()->json([
            'success'     => true,
            'attachment'  => $attachment->load('yukleyen'),
            'attachments' => $this->kartEkleri($cardId),
        ]);
    }

    /**
     * Dosya sil
     */
    public function deleteAttachment($boardId, $cardId, $attachmentId)
    {
        $attachment = KanbanCardAttachment::where('card_id', $cardId)
            ->findOrFail($attachmentId);

        // Dosyayı sil — yeni dosyalar public/ altında, eskiler storage'da olabilir
        $yol = $attachment->dosya_yolu;
        if ($yol) {
            if (is_file(public_path($yol))) {
                @unlink(public_path($yol));
            } elseif (Storage::disk('public')->exists($yol)) {
                Storage::disk('public')->delete($yol);
            }
        }

        $attachment->delete();

        return response()->json([
            'success'     => true,
            'message'     => 'Dosya silindi!',
            'attachments' => $this->kartEkleri($cardId),
        ]);
    }

    /**
     * Bir ek dosyanın indirilebilir URL'sini üretir.
     */
    private function ekDosyaUrl(?string $yol): ?string
    {
        if (empty($yol)) return null;
        if (preg_match('#^https?://#i', $yol)) return $yol;
        $yol = ltrim($yol, '/');
        if (is_file(public_path($yol))) return asset($yol);
        if (is_file(storage_path('app/public/' . $yol))) return asset('storage/' . $yol);
        return asset($yol);
    }

    /**
     * Kapak dosyasını sil — yeni dosyalar public/storage altında, eskiler storage diskinde olabilir.
     */
    private function kapakSil(?string $yol): void
    {
        if (empty($yol)) return;
        $temiz = ltrim($yol, '/');
        if (is_file(public_path('storage/' . $temiz))) {
            @unlink(public_path('storage/' . $temiz));
            return;
        }
        try {
            if (Storage::disk('public')->exists($yol)) {
                Storage::disk('public')->delete($yol);
            }
        } catch (\Throwable $e) {}
    }

    /**
     * Kartın tüm eklerini, her birine indirme URL'si (dosya_url) ekleyerek döndürür.
     */
    private function kartEkleri($cardId)
    {
        return KanbanCardAttachment::where('card_id', $cardId)
            ->orderBy('id')
            ->get()
            ->map(function ($a) {
                $a->dosya_url = $this->ekDosyaUrl($a->dosya_yolu);
                return $a;
            });
    }

    /**
     * Üye ekle/kaldır
     */
    public function toggleMember(Request $request, $boardId, $cardId)
    {
        $validated = $request->validate([
            'uye_id'      => 'required_without:yonetici_id|nullable|integer|exists:yoneticiler,id',
            'yonetici_id' => 'required_without:uye_id|nullable|integer|exists:yoneticiler,id',
        ]);

        $yoneticiId = $validated['uye_id'] ?? $validated['yonetici_id'] ?? null;
        if (!$yoneticiId) {
            return response()->json(['success' => false, 'message' => 'Üye ID gerekli'], 422);
        }

        $card = KanbanCard::whereHas('list', function($q) use ($boardId) {
            $q->where('board_id', $boardId);
        })->findOrFail($cardId);

        $member = KanbanCardMember::where('card_id', $cardId)
            ->where('yonetici_id', $yoneticiId)
            ->first();

        if ($member) {
            $member->delete();
            $action = 'removed';
        } else {
            KanbanCardMember::create([
                'card_id'     => $cardId,
                'yonetici_id' => $yoneticiId,
            ]);
            $action = 'added';
        }

        $tip = $action === 'added' ? 'uye_eklendi' : 'uye_cikarildi';
        KanbanCardActivity::create(['card_id' => $cardId, 'yazar_id' => session('admin_id'), 'tip' => $tip, 'aciklama' => ($action === 'added' ? 'Üye eklendi' : 'Üye çıkarıldı')]);

        $members = KanbanCardMember::with('yonetici')
            ->where('card_id', $cardId)
            ->get();

        return response()->json([
            'success' => true,
            'action'  => $action,
            'members' => $members,
        ]);
    }

    /**
     * Kart kopyala
     */
    public function copyCard(Request $request, $boardId, $listId, $cardId)
    {
        $card = KanbanCard::where('list_id', $listId)->findOrFail($cardId);
        $maxSira = KanbanCard::where('list_id', $listId)->max('sira') ?? -1;
        $newCard = KanbanCard::create([
            'list_id' => $listId,
            'baslik' => $card->baslik . ' (Kopya)',
            'aciklama' => $card->aciklama,
            'oncelik' => $card->oncelik,
            'durum' => 'aktif',
            'etiketler' => $card->etiketler,
            'renk' => $card->renk,
            'sira' => $maxSira + 1,
        ]);
        KanbanCardActivity::create(['card_id' => $newCard->id, 'yazar_id' => session('admin_id'), 'tip' => 'kopyalandi', 'aciklama' => '"'.$card->baslik.'" kartından kopyalandı']);
        return response()->json(['success' => true, 'card' => $newCard]);
    }

    /**
     * Kartı arşivle
     */
    public function archiveCard(Request $request, $boardId, $listId, $cardId)
    {
        $card = KanbanCard::where('list_id', $listId)->findOrFail($cardId);
        $card->update(['archived_at' => now()]);
        KanbanCardActivity::create(['card_id' => $cardId, 'yazar_id' => session('admin_id'), 'tip' => 'arsivlendi', 'aciklama' => 'Kart arşivlendi']);
        return response()->json(['success' => true]);
    }

    /**
     * Kartı arşivden çıkar
     */
    public function restoreCard(Request $request, $boardId, $cardId)
    {
        $card = KanbanCard::whereHas('list', fn($q) => $q->where('board_id', $boardId))->findOrFail($cardId);
        $card->update(['archived_at' => null]);
        KanbanCardActivity::create(['card_id' => $cardId, 'yazar_id' => session('admin_id'), 'tip' => 'arsivden_cikarildi', 'aciklama' => 'Kart arşivden çıkarıldı']);
        return response()->json(['success' => true]);
    }

    /**
     * Arşivlenmiş kartları listele
     */
    public function archivedCards($boardId)
    {
        $board = KanbanBoard::findOrFail($boardId);
        $cards = KanbanCard::whereHas('list', fn($q) => $q->where('board_id', $boardId))
            ->whereNotNull('archived_at')
            ->with('list')
            ->orderBy('archived_at', 'desc')
            ->get();
        return view('admin.crm.kanban.arsiv', compact('board', 'cards'));
    }

    /**
     * Takvim görünümü
     */
    public function calendarView($boardId)
    {
        $board = KanbanBoard::with('lists.cards')->findOrFail($boardId);
        $cards = KanbanCard::whereHas('list', fn($q) => $q->where('board_id', $boardId))
            ->whereNull('archived_at')
            ->where(function ($q) {
                $q->whereNotNull('son_tarih')->orWhereNotNull('baslangic_tarihi');
            })
            ->with('list')
            ->orderBy('son_tarih')
            ->get();
        return view('admin.crm.kanban.takvim', compact('board', 'cards'));
    }

    /**
     * Board istatistikleri
     */
    public function boardStats($boardId)
    {
        $board = KanbanBoard::with('lists')->findOrFail($boardId);
        $stats = [];
        foreach ($board->lists as $list) {
            $cards = KanbanCard::where('list_id', $list->id)->whereNull('archived_at');
            $stats[] = [
                'list_id' => $list->id,
                'baslik' => $list->baslik ?? $list->adi,
                'toplam' => $cards->count(),
                'gecmis' => (clone $cards)->where('son_tarih', '<', now())->whereNotNull('son_tarih')->count(),
                'acil' => (clone $cards)->where('oncelik', 'acil')->count(),
                'tamamlandi' => (clone $cards)->where('durum', 'tamamlandi')->count(),
            ];
        }
        return response()->json(['success' => true, 'stats' => $stats]);
    }

    /**
     * Board'a üye ekle/kaldır
     */
    public function toggleBoardMember(Request $request, $boardId)
    {
        $adminId = session('admin_id');
        $board = KanbanBoard::findOrFail($boardId);

        $validated = $request->validate([
            'yonetici_id' => 'required|integer|exists:yoneticiler,id',
            'rol' => 'nullable|in:sahip,editor,izleyici',
        ]);

        $member = KanbanBoardMember::where('board_id', $boardId)
            ->where('yonetici_id', $validated['yonetici_id'])
            ->first();

        if ($member) {
            $member->delete();
            $action = 'removed';
        } else {
            $member = KanbanBoardMember::create([
                'board_id' => $boardId,
                'yonetici_id' => $validated['yonetici_id'],
                'rol' => $validated['rol'] ?? 'editor',
            ]);
            $action = 'added';
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'member' => $member->load('yonetici'),
        ]);
    }

    /**
     * Kategori sil — bu kategorideki tüm board'ların kategorisini NULL yap
     */
    public function deleteKategori(Request $request)
    {
        $validated = $request->validate([
            'kategori' => 'required|string|max:255',
        ]);

        $kategori = trim($validated['kategori']);

        if (strtolower($kategori) === 'kategorisiz' || $kategori === '') {
            return response()->json(['success' => false, 'message' => 'Bu kategori silinemez.'], 422);
        }

        $etkilenen = KanbanBoard::where('kategori', $kategori)
            ->update(['kategori' => null]);

        return response()->json([
            'success'   => true,
            'message'   => "Kategori silindi. {$etkilenen} pano 'Kategorisiz' altına taşındı.",
            'etkilenen' => $etkilenen,
        ]);
    }

    /**
     * Kategori yeniden adlandır
     */
    public function renameKategori(Request $request)
    {
        $validated = $request->validate([
            'eski_kategori' => 'required|string|max:255',
            'yeni_kategori' => 'required|string|max:255',
        ]);

        $eski = trim($validated['eski_kategori']);
        $yeni = trim($validated['yeni_kategori']);

        if ($eski === $yeni) {
            return response()->json(['success' => false, 'message' => 'Yeni isim eskisiyle aynı'], 422);
        }

        $etkilenen = KanbanBoard::where('kategori', $eski)
            ->update(['kategori' => $yeni]);

        return response()->json([
            'success'   => true,
            'message'   => "Kategori yeniden adlandırıldı. {$etkilenen} pano güncellendi.",
            'etkilenen' => $etkilenen,
        ]);
    }


    private function handleBoardCover(Request $request, KanbanBoard $board): void
    {
        // Kaldırma istendiyse
        if ($request->input('kapak_kaldir') == '1') {
            $this->kapakSil($board->kapak);
            $board->kapak = null;
            $board->save();
            return;
        }

        if ($request->hasFile('kapak')) {
            $request->validate([
                'kapak' => 'image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB
            ]);

            $this->kapakSil($board->kapak);

            $file = $request->file('kapak');
            $name = 'board_' . $board->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            // Symlink'e bağımlı kalmamak için DOĞRUDAN public/storage altına yaz
            $file->move(public_path('storage/kanban/board_covers'), $name);
            $board->kapak = 'kanban/board_covers/' . $name;
            $board->save();
        }
    }


    public function uploadCover(Request $request, $boardId, $cardId)
    {
        $request->validate([
            'kapak' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB
        ]);

        $card = KanbanCard::whereHas('list', function ($q) use ($boardId) {
            $q->where('board_id', $boardId);
        })->findOrFail($cardId);

        // Eski kapak varsa sil
        $this->kapakSil($card->kapak);

        $file = $request->file('kapak');
        $fileName = 'kapak_' . $cardId . '_' . time() . '.' . $file->getClientOriginalExtension();
        // Symlink'e bağımlı kalmamak için DOĞRUDAN public/storage altına yaz
        $file->move(public_path('storage/kanban/covers'), $fileName);
        $path = 'kanban/covers/' . $fileName;

        $card->update(['kapak' => $path]);

        KanbanCardActivity::create([
            'card_id' => $cardId,
            'yazar_id' => session('admin_id'),
            'tip' => 'kapak_eklendi',
            'aciklama' => 'Kart kapağı güncellendi',
        ]);

        return response()->json([
            'success' => true,
            'kapak' => $path,
            'kapak_url' => asset('storage/' . ltrim($path, '/')),
        ]);
    }


    public function deleteCover($boardId, $cardId)
    {
        $card = KanbanCard::whereHas('list', function ($q) use ($boardId) {
            $q->where('board_id', $boardId);
        })->findOrFail($cardId);

        $this->kapakSil($card->kapak);
        $card->update(['kapak' => null]);

        return response()->json(['success' => true]);
    }
}
