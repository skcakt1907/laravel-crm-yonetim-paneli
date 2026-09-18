// Opportunities form - Pipeline ve Stage ilişkisi
(function () {
  const pipelineSelect = document.getElementById('pipelineSelect');
  const stageSelect = document.getElementById('stageSelect');
  if (!pipelineSelect || !stageSelect) {
    return;
  }

  const originalOptions = Array.from(stageSelect.options);

  function refreshStageOptions() {
    const pipelineId = pipelineSelect.value;
    const selectedStage = stageSelect.dataset.selected;

    stageSelect.innerHTML = '';
    stageSelect.appendChild(new Option('Seçiniz', ''));

    originalOptions.forEach(option => {
      if (!option.value) {
        return;
      }

      const optionPipeline = option.dataset.pipeline;
      if (!pipelineId || optionPipeline === pipelineId) {
        const clone = option.cloneNode(true);
        if (selectedStage && selectedStage === clone.value) {
          clone.selected = true;
        }
        stageSelect.appendChild(clone);
      }
    });
  }

  pipelineSelect.addEventListener('change', () => {
    stageSelect.dataset.selected = '';
    refreshStageOptions();
  });

  refreshStageOptions();
})();

