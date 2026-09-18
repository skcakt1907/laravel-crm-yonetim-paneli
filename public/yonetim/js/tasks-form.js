// Tasks form - Opportunity ve Customer ilişkisi
(function () {
  const opportunityMapElement = document.getElementById('opportunityMapData');
  if (!opportunityMapElement) return;

  const opportunityMap = JSON.parse(opportunityMapElement.textContent);
  const musterSelect = document.getElementById('musteriSelect');
  const firsatSelect = document.getElementById('firsatSelect');

  if (!musterSelect || !firsatSelect) {
    return;
  }

  firsatSelect.addEventListener('change', () => {
    const selectedOpportunity = firsatSelect.value;
    if (selectedOpportunity && opportunityMap[selectedOpportunity]) {
      musterSelect.value = opportunityMap[selectedOpportunity];
    }
  });
})();

