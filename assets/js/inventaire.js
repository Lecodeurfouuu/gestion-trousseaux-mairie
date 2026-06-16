// inventaire.js — Gestion des accès dynamiques

// Ajouter une ligne bâtiment/porte
function ajouterLigneAcces(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    const tr = document.createElement('tr');

    let optionsBat = '<option value="">-- Aucun --</option>';
    batiments.forEach(b => {
        optionsBat += `<option value="${b.id}">${b.nom}</option>`;
    });

    const selectPorteId = 'porte-' + tbodyId + '-' + Date.now();

    tr.innerHTML = `
        <td>
            <select name="acces_batiment[]" onchange="mettreAJourPortes(this, '${selectPorteId}')">
                ${optionsBat}
            </select>
        </td>
        <td>
            <select name="acces_porte[]" id="${selectPorteId}">
                <option value="">-- Choisir d'abord un bâtiment --</option>
            </select>
        </td>
        <td>
            <button type="button"
                onclick="this.closest('tr').remove()"
                style="background:#dc2626;color:white;border:none;border-radius:6px;padding:4px 10px;cursor:pointer;">
                ✕
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

// Mettre à jour le select porte selon le bâtiment choisi
function mettreAJourPortes(selectBat, selectPorteId) {
    const idBatiment = parseInt(selectBat.value);
    const selectPorte = document.getElementById(selectPorteId);
    selectPorte.innerHTML = '';

    if (!idBatiment || !portesBatiment[idBatiment] || portesBatiment[idBatiment].length === 0) {
        selectPorte.innerHTML = '<option value="">-- Aucune porte enregistrée --</option>';
        return;
    }

    let options = '<option value="">-- Choisir une porte --</option>';
    options += '<option value="0">Toutes les portes</option>';
    portesBatiment[idBatiment].forEach(p => {
        options += `<option value="${p.id}">${p.nom}</option>`;
    });
    selectPorte.innerHTML = options;
}

// Assurer l'affichage du bon onglet côté client (au cas où le rendu serveur n'aurait
// pas masqué correctement les sections). Utilise le paramètre `onglet` dans l'URL.
document.addEventListener('DOMContentLoaded', function () {
    try {
        const params = new URLSearchParams(window.location.search);
        const onglet = params.get('onglet') || 'cles';

        document.querySelectorAll('.tab-content').forEach(function (el) {
            if (el.id === 'tab-' + onglet) {
                el.style.display = '';
            } else {
                el.style.display = 'none';
            }
        });

        document.querySelectorAll('.tab-lien').forEach(function (link) {
            link.classList.toggle('active', link.href.includes('onglet=' + onglet));
            // Intercepter le clic pour gérer l'affichage côté client sans recharger
            link.addEventListener('click', function (ev) {
                ev.preventDefault();
                const url = new URL(link.href, window.location.origin);
                const newOnglet = url.searchParams.get('onglet') || 'cles';
                // Mettre à jour l'URL sans recharger
                window.history.pushState({ onglet: newOnglet }, '', url.pathname + '?onglet=' + newOnglet);
                // Afficher le bon panneau
                document.querySelectorAll('.tab-content').forEach(function (el) {
                    el.style.display = (el.id === 'tab-' + newOnglet) ? '' : 'none';
                });
                document.querySelectorAll('.tab-lien').forEach(function (l) {
                    l.classList.toggle('active', l === link);
                });
            });
        });

        // Gérer navigation par historique (back/forward)
        window.addEventListener('popstate', function (e) {
            const ongletPop = (e.state && e.state.onglet) || (new URLSearchParams(window.location.search).get('onglet')) || 'cles';
            document.querySelectorAll('.tab-content').forEach(function (el) {
                el.style.display = (el.id === 'tab-' + ongletPop) ? '' : 'none';
            });
            document.querySelectorAll('.tab-lien').forEach(function (l) {
                l.classList.toggle('active', l.href.includes('onglet=' + ongletPop));
            });
        });
    } catch (e) {
        // noop
        console.error("inventaire.js: erreur lors du réglage de l'onglet actif", e);
    }
});
