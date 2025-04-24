// JS pour afficher uniquement les ingrédients sélectionnés ou tous dans le <select multiple>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.querySelector('.multi-ingredient-select');
    const toggleBtn = document.getElementById('toggle-ingredients-btn');
    let filtered = false;

    if (!select || !toggleBtn) return;

    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (!filtered) {
            // Masquer les options non sélectionnées
            Array.from(select.options).forEach(opt => {
                if (!opt.selected) opt.style.display = 'none';
            });
            toggleBtn.textContent = 'Afficher tous les ingrédients';
            filtered = true;
        } else {
            // Réafficher toutes les options
            Array.from(select.options).forEach(opt => {
                opt.style.display = '';
            });
            toggleBtn.textContent = 'Afficher uniquement la sélection';
            filtered = false;
        }
    });
});
