USE voisinpartage;

-- ------------------------------------------------------------
-- Nettoyage dans le bon ordre (tables enfants d'abord)
-- ------------------------------------------------------------
USE voisinpartage;

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM messages;
DELETE FROM demandes_emprunt;
DELETE FROM annonces;
DELETE FROM utilisateurs;
DELETE FROM categories;

ALTER TABLE messages          AUTO_INCREMENT = 1;
ALTER TABLE demandes_emprunt  AUTO_INCREMENT = 1;
ALTER TABLE annonces          AUTO_INCREMENT = 1;
ALTER TABLE utilisateurs      AUTO_INCREMENT = 1;
ALTER TABLE categories        AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Catégories
-- ------------------------------------------------------------
INSERT INTO categories (nom, icone, description) VALUES
('Bricolage & Outils',      'fa-solid fa-screwdriver-wrench', 'Perceuses, scies, marteaux'),
('Jardin & Extérieur',      'fa-solid fa-leaf',               'Tondeuses, taille-haies, arrosoirs'),
('Sport & Loisirs',         'fa-solid fa-futbol',             'Équipements sportifs, vélos'),
('Cuisine & Électroménager','fa-solid fa-utensils',           'Appareils de cuisine, robots'),
('Mobilier & Décoration',   'fa-solid fa-couch',              'Meubles, tableaux, luminaires'),
('Informatique & High-Tech','fa-solid fa-laptop',             'Ordinateurs, tablettes, accessoires'),
('Livres & Jeux',           'fa-solid fa-book',               'Romans, BD, jeux de société'),
('Transport',               'fa-solid fa-car',                'Remorques, vélos, trottinettes'),
('Vêtements & Mode',        'fa-solid fa-shirt',              'Habits, chaussures, accessoires'),
('Divers',                  'fa-solid fa-box',                'Tout le reste');
-- ------------------------------------------------------------
-- Utilisateurs
-- Mot de passe pour TOUS les comptes : password123
-- ------------------------------------------------------------
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, adresse, quartier, role, actif) VALUES
('Dupont',  'Marie',   'marie.dupont@email.com',   '$2y$10$TKh8H1.PfunDStreNOx7.OeCo3bDDtfVpXAoRrq.1Tq7HdXKQMCHi', '0612345678', '12 rue des Lilas',      'Centre-ville', 'administrateur', 1),
('Martin',  'Paul',    'paul.martin@email.com',    '$2y$10$TKh8H1.PfunDStreNOx7.OeCo3bDDtfVpXAoRrq.1Tq7HdXKQMCHi', '0698765432', '5 avenue des Roses',    'Belleville',   'utilisateur',    1),
('Bernard', 'Sophie',  'sophie.bernard@email.com', '$2y$10$TKh8H1.PfunDStreNOx7.OeCo3bDDtfVpXAoRrq.1Tq7HdXKQMCHi', '0678901234', '8 rue du Moulin',       'Les Pins',     'utilisateur',    1),
('Leroy',   'Thomas',  'thomas.leroy@email.com',   '$2y$10$TKh8H1.PfunDStreNOx7.OeCo3bDDtfVpXAoRrq.1Tq7HdXKQMCHi', '0645678901', '3 impasse des Chenes',  'Montmartre',   'utilisateur',    1),
('Moreau',  'Lucie',   'lucie.moreau@email.com',   '$2y$10$TKh8H1.PfunDStreNOx7.OeCo3bDDtfVpXAoRrq.1Tq7HdXKQMCHi', '0667890123', '27 bd de la Paix',      'Saint-Michel', 'utilisateur',    1),
('Petit',   'Antoine', 'antoine.petit@email.com',  '$2y$10$TKh8H1.PfunDStreNOx7.OeCo3bDDtfVpXAoRrq.1Tq7HdXKQMCHi', '0689012345', '14 rue Gambetta',       'Vieux-Port',   'utilisateur',    0);

-- ------------------------------------------------------------
-- Annonces
-- ------------------------------------------------------------
INSERT INTO annonces (titre, description, type, statut, photo, id_utilisateur, id_categorie) VALUES
('Perceuse Bosch professionnelle',  'Perceuse visseuse sans fil 18V avec 2 batteries et chargeur.',          'pret', 'disponible',   'no-image.png', 2, 1),
('Tondeuse electrique Flymo',       'Tondeuse a gazon electrique 1400W, largeur de coupe 34cm.',             'pret', 'disponible',   'no-image.png', 3, 2),
('Appareil a raclette 8 personnes', 'Raclette electrique pour 8 personnes avec tous les poelons.',           'pret', 'reserve',      'no-image.png', 2, 4),
('Velo adulte 26 pouces',           'Velo de ville 7 vitesses, cadre aluminium, tres bon etat.',             'pret', 'disponible',   'no-image.png', 4, 3),
('Lot de livres cuisine',           'Collection de 15 livres de cuisine varies. Don definitif.',             'don',  'disponible',   'no-image.png', 5, 7),
('Escabeau 6 marches',              'Escabeau en aluminium 6 marches, tres stable, charge max 150kg.',       'pret', 'disponible',   'no-image.png', 3, 1),
('Console Nintendo Switch',         'Nintendo Switch avec 5 jeux. Tous les accessoires inclus.',             'pret', 'indisponible', 'no-image.png', 2, 6),
('Remorque voiture 750kg',          'Remorque legere 750kg, dimensions 2m x 1.20m, avec bache.',             'pret', 'disponible',   'no-image.png', 4, 8),
('Machine a coudre Singer',         'Machine a coudre electrique Singer, 25 points differents.',             'don',  'disponible',   'no-image.png', 5, 9),
('Barbecue Weber charbon',          'Barbecue a charbon Weber 57cm, excellent etat, grilles inox.',          'pret', 'disponible',   'no-image.png', 3, 2),
('Kit de peinture murale',          'Rouleaux, bacs, pinceaux de differentes tailles. Materiel propre.',     'don',  'disponible',   'no-image.png', 4, 1),
('Jeux de societe assortiment',     'Lot de 8 jeux : Catan, Pandemic, 7 Wonders, Carcassonne. Complets.',   'pret', 'disponible',   'no-image.png', 5, 7);
-- ------------------------------------------------------------
-- Demandes d'emprunt
-- ------------------------------------------------------------
INSERT INTO demandes_emprunt (id_annonce, id_emprunteur, message, statut) VALUES
(1, 3, 'Bonjour, je souhaiterais emprunter votre perceuse ce week-end.', 'acceptee'),
(3, 4, 'Bonjour, je voudrais emprunter la raclette pour samedi soir.',   'en_attente'),
(4, 5, 'Bonjour, je cherche un velo pour quelques jours.',               'refusee'),
(2, 2, 'Bonjour, ma tondeuse est en panne. Puis-je emprunter la votre ?','terminee');



-- ------------------------------------------------------------
-- Messages
-- ------------------------------------------------------------
INSERT INTO messages (id_expediteur, id_destinataire, contenu, lu) VALUES
(3, 2, 'Bonjour Marie ! Je suis interessee par votre perceuse.',          1),
(2, 3, 'Bonjour Sophie ! Oui disponible. Quand voulez-vous passer ?',     1),
(3, 2, 'Je pourrais passer vendredi vers 18h ?',                          1),
(2, 3, 'Parfait, vendredi 18h. Mon adresse : 5 avenue des Roses.',        0),
(4, 5, 'Bonjour Lucie, les livres sont-ils encore disponibles ?',         1),
(5, 4, 'Bonjour Thomas ! Oui bien sur, venez quand vous voulez.',         0),
(5, 3, 'Bonjour Sophie, je suis interessee par la tondeuse.',             1),
(3, 5, 'Bonjour Lucie ! Disponible. Quel jour vous arrange ?',            0);