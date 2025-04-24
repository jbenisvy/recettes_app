// Script pour gérer la sélection multiple de tags dans le formulaire recette
// Nécessite un <select multiple id="tags-select"> et un champ caché <input type="hidden" name="tags" id="tags-hidden">

document.addEventListener('DOMContentLoaded', function() {
    const tagsSelect = document.getElementById('tags-select');
    const tagsHidden = document.getElementById('tags-hidden');
    if (!tagsSelect || !tagsHidden) return;

    // Met à jour le champ caché avec les IDs sélectionnés
    function updateTagsHidden() {
        const selected = Array.from(tagsSelect.selectedOptions).map(opt => opt.value).join(',');
        tagsHidden.value = selected;
    }
    tagsSelect.addEventListener('change', updateTagsHidden);
    updateTagsHidden();
});
