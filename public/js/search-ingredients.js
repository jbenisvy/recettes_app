// JS pour la sélection multiple d'ingrédients avec autocomplétion AJAX

document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('ingredient-autocomplete');
    const selectedDiv = document.getElementById('selected-ingredients');
    let dropdown;

    // Créer la dropdown pour suggestions
    function createDropdown() {
        dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
        dropdown.style.position = 'absolute';
        dropdown.style.zIndex = 1000;
        dropdown.style.background = 'white';
        dropdown.style.border = '1px solid #ccc';
        dropdown.style.width = input.offsetWidth + 'px';
        dropdown.style.maxHeight = '200px';
        dropdown.style.overflowY = 'auto';
        dropdown.style.display = 'none';
        input.parentNode.appendChild(dropdown);
    }
    createDropdown();

    // Positionner la dropdown
    function positionDropdown() {
        const rect = input.getBoundingClientRect();
        dropdown.style.left = input.offsetLeft + 'px';
        dropdown.style.top = (input.offsetTop + input.offsetHeight) + 'px';
        dropdown.style.width = input.offsetWidth + 'px';
    }

    // Fetch suggestions AJAX
    async function fetchSuggestions(query) {
        const resp = await fetch('api_ingredients.php?q=' + encodeURIComponent(query));
        if (!resp.ok) return [];
        return await resp.json();
    }

    // Afficher suggestions
    function showSuggestions(list) {
        dropdown.innerHTML = '';
        if (!list.length) {
            dropdown.style.display = 'none';
            return;
        }
        list.forEach(item => {
            const option = document.createElement('div');
            option.className = 'autocomplete-option';
            option.textContent = item.name;
            option.dataset.value = item.name;
            option.style.padding = '0.5em 1em';
            option.style.cursor = 'pointer';
            option.addEventListener('mousedown', function(e) {
                e.preventDefault();
                addTag(item.name);
                input.value = '';
                dropdown.style.display = 'none';
            });
            dropdown.appendChild(option);
        });
        dropdown.style.display = 'block';
        positionDropdown();
    }

    // Ajouter un tag
    function addTag(val) {
        // Ne pas ajouter deux fois le même ingrédient
        if ([...selectedDiv.querySelectorAll('input[name="ingredients[]"]')].some(i => i.value.toLowerCase() === val.toLowerCase())) return;
        const span = document.createElement('span');
        span.className = 'ingredient-tag';
        span.innerHTML = `${val} <button type="button" class="remove-tag" data-value="${val}">×</button>` +
            `<input type="hidden" name="ingredients[]" value="${val}">`;
        selectedDiv.appendChild(span);
    }

    // Gestion suppression d'un tag
    selectedDiv.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-tag')) {
            e.preventDefault();
            const span = e.target.closest('.ingredient-tag');
            if (span) span.remove();
        }
    });

    // Gestion saisie
    input.addEventListener('input', async function(e) {
        const q = input.value.trim();
        if (q.length < 1) {
            dropdown.style.display = 'none';
            return;
        }
        const suggestions = await fetchSuggestions(q);
        showSuggestions(suggestions);
    });

    // Ajout avec entrée
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && input.value.trim() !== '') {
            e.preventDefault();
            addTag(input.value.trim());
            input.value = '';
            dropdown.style.display = 'none';
        }
    });

    // Cacher la dropdown si on clique ailleurs
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && e.target !== input) {
            dropdown.style.display = 'none';
        }
    });

    // Repositionner la dropdown si la fenêtre change
    window.addEventListener('resize', positionDropdown);
    input.addEventListener('focus', positionDropdown);
});
