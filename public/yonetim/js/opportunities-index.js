// Opportunities index - Pipeline ve Stage filtreleme
(function () {
  const pipelineSelect = document.getElementById('pipeline_id');
  const stageSelect = document.getElementById('stage_id');
  if (!pipelineSelect || !stageSelect) {
    return;
  }

  const options = Array.from(stageSelect.options);

  function filterStages() {
    const pipelineId = pipelineSelect.value;
    const selectedStage = stageSelect.dataset.selected;

    stageSelect.innerHTML = '';
    stageSelect.appendChild(new Option('Tümü', ''));

    options.forEach(option => {
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
    filterStages();
  });

  filterStages();
})();

