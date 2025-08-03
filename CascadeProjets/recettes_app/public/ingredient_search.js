// Script pour filtrer dynamiquement la liste des ingrédients dans le select
// À inclure après le select dans la page concernée (ex: edit_recipe.php)

document.addEventListener('DOMContentLoaded', function() {
    console.log('[ingredient_search.js] Script chargé');
    const select = document.getElementById('ingredient-select');
    if (!select) return;
    const wrapper = select.parentElement;
    // Ne pas dupliquer le champ si déjà présent
    if (wrapper.querySelector('.ingredient-search-input')) return;
    // Créer un champ de recherche juste au-dessus du select
    const searchInput = document.createElement('input');
    searchInput.type = 'text';
    searchInput.placeholder = "🔍 Rechercher un ingrédient...";
    searchInput.className = 'ingredient-search-input';
    searchInput.style.marginBottom = '8px';
    searchInput.style.width = '98%';
    searchInput.style.padding = '7px 12px';
    searchInput.style.border = '1.5px solid #1976d2';
    searchInput.style.borderRadius = '7px';
    searchInput.style.fontSize = '1em';
    searchInput.style.background = '#f8fbff';
    searchInput.style.color = '#222';
    searchInput.style.boxShadow = '0 1px 4px #1976d220';
    wrapper.insertBefore(searchInput, select);

    // Message d'aide si filtré
    const helpMsg = document.createElement('div');
    helpMsg.style.fontSize = '0.95em';
    helpMsg.style.color = '#1976d2';
    helpMsg.style.margin = '3px 0 7px 0';
    helpMsg.style.display = 'none';
    helpMsg.textContent = 'Filtre actif : seuls les ingrédients correspondants sont affichés.';
    wrapper.insertBefore(helpMsg, searchInput.nextSibling);

    // Sauvegarder toutes les options originales
    const allOptions = Array.from(select.options).map(opt => ({value: opt.value, text: opt.text}));

    function filterOptions() {
        const query = searchInput.value.trim().toLowerCase();
        select.innerHTML = '';
        let count = 0;
        allOptions.forEach(opt => {
            if (
                opt.value === '' ||
                opt.value === '__autre__' ||
                opt.text.toLowerCase().includes(query)
            ) {
                const option = document.createElement('option');
                option.value = opt.value;
                option.text = opt.text;
                select.appendChild(option);
                count++;
            }
        });
        helpMsg.style.display = (query.length > 0 && count > 0) ? '' : 'none';
    }

    searchInput.addEventListener('input', filterOptions);
});
