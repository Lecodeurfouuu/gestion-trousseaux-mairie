// fiche_trousseau.js — Masquage des champs clé/badge selon le type sélectionné

document.addEventListener('DOMContentLoaded', function() {

    var selectType = document.getElementById('type_element');
    if (!selectType) return;

    function basculerChamps(valeur) {
        var champCle   = document.getElementById('champ-cle');
        var champBadge = document.getElementById('champ-badge');
        if (!champCle || !champBadge) return;

        champCle.style.display   = (valeur === 'cle')   ? 'block' : 'none';
        champBadge.style.display = (valeur === 'badge') ? 'block' : 'none';
    }

    selectType.addEventListener('change', function() {
        basculerChamps(this.value);
    });

    basculerChamps(selectType.value);

});

