// inventaire.js — Préfixe automatique selon le type de badge


document.addEventListener('DOMContentLoaded', function() {

    const prefixes = { 'Ela': 'ELA-', 'Salto': 'BLEU-' };
    const selectType = document.getElementById('type_badge');

    if (selectType) {
        selectType.addEventListener('change', function() {
            const champ = document.getElementById('identifiant_interne');
            const prefixe = prefixes[this.value] ?? '';
            const valeur = champ.value;
            const anciensPrefixes = Object.values(prefixes);

            if (valeur === '' || anciensPrefixes.includes(valeur)) {
                champ.value = prefixe;
                champ.placeholder = prefixe ? 'Ex : ' + prefixe + '001' : 'Ex : BLEU-001, ELA-6489';
            }
            champ.focus();
        });
    }

});