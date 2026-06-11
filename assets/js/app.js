// app.js — Fonctions JS globales

// Afficher / masquer les photos d'une ligne
function activerPhotos(id, btn) {
    const row = document.getElementById(id);
    const open = row.style.display === 'table-row';
    row.style.display = open ? 'none' : 'table-row';
    btn.innerHTML = open
        ? '<i class="ti ti-camera" aria-hidden="true"></i> Photos'
        : '<i class="ti ti-camera" aria-hidden="true"></i> Masquer';
}
