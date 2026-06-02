
SET FOREIGN_KEY_CHECKS = 0;

-- Personnes
INSERT INTO personnes (id_personne, nom, prenom, service, groupe_personnel, telephone, mail) VALUES
(1, 'Dupont',   'Jean',    'Secrétariat',      'Élu',     '0612345678', 'jean.dupont@mairie.fr'),
(2, 'Curie',    'Marie',   'Technique',        'Agent',   '0623456789', 'marie.curie@mairie.fr'),
(3, 'Valéry',   'Paul',    'Sécurité',         'Agent',   '0634567890', 'paul.valery@mairie.fr'),
(4, 'Martin',   'Claire',  'Association Sport','Bénévole','0645678901', 'claire.martin@mairie.fr'),
(5, 'Bernard',  'Luc',     'Technique',        'Agent',   '0656789012', 'luc.bernard@mairie.fr'),
(6, 'Lefebvre', 'Sophie',  'Secrétariat',      'Agent',   null,         'sophie.lefebvre@mairie.fr');

-- Bâtiments
INSERT INTO batiments (id_batiment, nom_batiment, adresse, commentaire) VALUES
(1, 'Mairie',         '1 place de la République', 'Bâtiment principal'),
(2, 'Salle Aragon',   '2 avenue Aragon',           'Salle événementielle'),
(3, 'Salle des sports','Rue du stade',              'Accès association');

-- Références de clés
INSERT INTO references_cles (id_reference_cle, reference_cle, commentaire) VALUES
(1, 'REF-45',  'Clé bureau urbanisme'),
(2, 'REF-102', 'Clé local technique'),
(3, 'REF-78',  'Clé bureau du maire'),
(4, 'REF-585', 'Clé salle polyvalente'),
(5, 'REF-588', 'Clé entrée principale');

-- Accès clés → bâtiments
INSERT INTO element_acces (type_element, id_reference_cle, id_badge, id_batiment, porte_commentaire) VALUES
('cle', 1, null, 1, 'Bureau urbanisme'),
('cle', 2, null, 1, 'Local technique sous-sol'),
('cle', 3, null, 1, 'Bureau du maire — 1er étage'),
('cle', 4, null, 2, 'Salle principale'),
('cle', 5, null, 1, 'Entrée principale'),
('cle', 5, null, 2, 'Entrée principale');

-- Badges
INSERT INTO badges (id_badge, identifiant_interne, identifiant_officiel, type_badge, statut) VALUES
(1, 'ELA-6489', '6489', 'Ela',   'Attribué'),
(2, 'ELA-6544', '6544', 'Ela',   'Disponible'),
(3, 'BLEU-001', null,   'Salto', 'Attribué'),
(4, 'BLEU-002', null,   'Salto', 'Disponible'),
(5, 'ELA-7001', '7001', 'Ela',   'Perdu');

-- Accès badges → bâtiments
INSERT INTO element_acces (type_element, id_reference_cle, id_badge, id_batiment, porte_commentaire) VALUES
('badge', null, 1, 1, 'Parking Nord'),
('badge', null, 1, 2, 'Entrée principale'),
('badge', null, 2, 1, 'Accès général'),
('badge', null, 3, 1, 'Bureau 1, salle réunion'),
('badge', null, 4, 3, 'Entrée vestiaires'),
('badge', null, 5, 1, 'Accès général');

-- Trousseaux
INSERT INTO trousseaux (id_trousseau, numero_trousseau, statut, commentaire) VALUES
(1, 'TR-001', 'Attribué',   'Trousseau secrétariat'),
(2, 'TR-002', 'Attribué',   'Trousseau technique'),
(3, 'TR-003', 'Disponible', null),
(4, 'TR-004', 'Perdu',      'Déclaré perdu le 15/04/2026'),
(5, 'TR-005', 'Disponible', 'Trousseau association sport');

-- Éléments des trousseaux
INSERT INTO trousseau_elements (id_trousseau, type_element, id_reference_cle, id_badge, date_ajout, statut, commentaire, commentaire_horaires) VALUES
-- TR-001 : Jean Dupont
(1, 'cle',   1,    null, '2026-01-10', 'Présent', null, null),
(1, 'cle',   3,    null, '2026-01-10', 'Présent', null, null),
(1, 'badge', null, 1,    '2026-01-10', 'Présent', null, 'Mairie : 08h00-18h00 | Salle Aragon : 08h00-22h00'),
-- TR-002 : Marie Curie
(2, 'cle',   2,    null, '2026-02-01', 'Présent', null, null),
(2, 'badge', null, 3,    '2026-02-01', 'Présent', null, 'Mairie : 07h00-20h00'),
-- TR-003 : disponible, pré-rempli
(3, 'cle',   5,    null, '2026-03-01', 'Présent', null, null),
(3, 'badge', null, 4,    '2026-03-01', 'Présent', null, null),
-- TR-004 : perdu — éléments retirés
(4, 'cle',   4,    null, '2026-01-15', 'Perdu',   'Déclaré perdu le 2026-04-15', null),
(4, 'badge', null, 5,    '2026-01-15', 'Perdu',   'Déclaré perdu le 2026-04-15', null),
-- TR-005 : disponible
(5, 'cle',   5,    null, '2026-04-01', 'Présent', null, null);

-- Historique des attributions
INSERT INTO historique_trousseaux (id_trousseau, id_personne, date_remise, date_restitution, decharge_signee, statut_evenement, commentaire) VALUES
-- TR-001 : attribué à Jean Dupont
(1, 1, '2026-01-10', null,         1, 'Remis',      null),
-- TR-002 : attribué à Marie Curie
(2, 2, '2026-02-01', null,         1, 'Remis',      null),
-- TR-003 : ancien propriétaire Paul Valéry, maintenant disponible
(3, 3, '2026-01-20', '2026-03-15', 1, 'Restitué',   'Restitution volontaire'),
-- TR-004 : perdu par Luc Bernard
(4, 5, '2026-01-15', null,         0, 'Perdu',      'Trousseau déclaré perdu'),
-- TR-005 : anciennement attribué à Claire Martin, maintenant disponible
(5, 4, '2026-02-10', '2026-04-20', 1, 'Restitué',   null);

SET FOREIGN_KEY_CHECKS = 1;
