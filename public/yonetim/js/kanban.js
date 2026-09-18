// Kanban Board JavaScript — SADECE sürükle-bırak (Sortable).
// NOT: addCard, addList, openCardModal, kart/liste/modal işlemleri ve
// render fonksiyonları KASITLI olarak buradan kaldırıldı. Bunlar
// show.blade.php içindeki vanilla JS tarafından yönetiliyor. Eskiden
// burada da tanımlıydılar (jQuery + Bootstrap modal bekliyorlardı) ve
// blade'in fonksiyonlarını EZİP bozuyorlardı (addCard çakışması →
// "Cannot set properties of null" → kart eklenemiyordu). Çakışma giderildi.

function initKanban() {
  const boardElement = document.getElementById('kanbanBoard') || document.getElementById('kanbanLists');
  if (!boardElement) return;

  const boardId = boardElement.dataset.boardId;
  if (!boardId) return;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const baseUrl = boardElement.dataset.baseUrl || '';

  // ── Liste siralamasi (surukle-birak) ──
  const listsContainer = document.getElementById('kanbanLists');
  if (listsContainer && typeof Sortable !== 'undefined') {
    new Sortable(listsContainer, {
      animation: 0,
      handle: '.kanban-list-header',
      delay: 200,
      delayOnTouchOnly: true,
      touchStartThreshold: 8,
      onEnd: function () {
        const listIds = Array.from(listsContainer.children).map(el => el.dataset.listId);
        fetch(`${baseUrl}/admin/crm/kanban/${boardId}/reorder-lists`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({ list_ids: listIds })
        });
      }
    });
  }

  // ── Her liste icin kart siralamasi / listeler arasi tasima ──
  // iOS Safari notu: animation:0 + native dokunmatik (forceFallback YOK) +
  // basili-tut (delay) -> kabuk/icerik ayrisma glitch'i ve kaydirma cakismasi onlenir.
  document.querySelectorAll('.kanban-list-body').forEach(listBody => {
    if (typeof Sortable === 'undefined') return;
    new Sortable(listBody, {
      group: 'cards',
      animation: 0,
      delay: 200,
      delayOnTouchOnly: true,
      touchStartThreshold: 8,
      scroll: true,
      scrollSensitivity: 80,
      scrollSpeed: 12,
      onEnd: function (evt) {
        const cardId = evt.item.dataset.cardId;
        const newListId = evt.to.dataset.listId;
        const newPosition = Array.from(evt.to.children).indexOf(evt.item);

        fetch(`${baseUrl}/admin/crm/kanban/${boardId}/move-card`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
          body: JSON.stringify({
            card_id: cardId,
            new_list_id: newListId,
            new_position: newPosition
          })
        }).then(() => { location.reload(); });
      }
    });
  });
}

// Baslat
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initKanban);
} else {
  initKanban();
}