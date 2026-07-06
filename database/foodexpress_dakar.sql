-- ============================================================
-- FOODEXPRESS DAKAR - Base de données
-- À importer dans phpMyAdmin (WAMP)
-- ============================================================

CREATE DATABASE IF NOT EXISTS foodexpress_dakar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE foodexpress_dakar;

-- ============================================================
-- TABLE : users
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(30) DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    quartier VARCHAR(100) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client','admin') NOT NULL DEFAULT 'client',
    avatar VARCHAR(255) DEFAULT NULL,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_expires DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : categories
-- ============================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description TEXT,
    icone VARCHAR(80) DEFAULT 'restaurant',
    image VARCHAR(255) DEFAULT NULL,
    ordre INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : plats
-- ============================================================
CREATE TABLE IF NOT EXISTS plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categorie_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT,
    ingredients TEXT,
    prix DECIMAL(10,2) NOT NULL,
    prix_promo DECIMAL(10,2) DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    temps_preparation INT DEFAULT 30 COMMENT 'en minutes',
    calories INT DEFAULT NULL,
    epice TINYINT(1) DEFAULT 0,
    vedette TINYINT(1) DEFAULT 0,
    disponible TINYINT(1) DEFAULT 1,
    note_moyenne DECIMAL(2,1) DEFAULT 0.0,
    nb_ventes INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_categorie (categorie_id),
    INDEX idx_vedette (vedette),
    INDEX idx_disponible (disponible)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : commandes
-- ============================================================
CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero VARCHAR(20) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    sous_total DECIMAL(10,2) NOT NULL,
    frais_livraison DECIMAL(10,2) DEFAULT 1000.00,
    reduction DECIMAL(10,2) DEFAULT 0.00,
    code_promo VARCHAR(50) DEFAULT NULL,
    total DECIMAL(10,2) NOT NULL,
    adresse_livraison VARCHAR(255) NOT NULL,
    quartier VARCHAR(100) NOT NULL,
    telephone VARCHAR(30) NOT NULL,
    instructions TEXT,
    statut ENUM('en_attente','confirmee','en_preparation','en_livraison','livree','annulee') DEFAULT 'en_attente',
    methode_paiement ENUM('especes','wave','orange_money','carte') DEFAULT 'especes',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_statut (statut),
    INDEX idx_numero (numero)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : details_commandes
-- ============================================================
CREATE TABLE IF NOT EXISTS details_commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    plat_id INT NOT NULL,
    nom_plat VARCHAR(150) NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    quantite INT NOT NULL,
    sous_total DECIMAL(10,2) NOT NULL,
    options VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (plat_id) REFERENCES plats(id) ON DELETE RESTRICT,
    INDEX idx_commande (commande_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : paiements
-- ============================================================
CREATE TABLE IF NOT EXISTS paiements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    methode ENUM('especes','wave','orange_money','carte') NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    reference VARCHAR(100) DEFAULT NULL,
    statut ENUM('en_attente','valide','echoue','rembourse') DEFAULT 'en_attente',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    INDEX idx_commande (commande_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : livraisons
-- ============================================================
CREATE TABLE IF NOT EXISTS livraisons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    livreur VARCHAR(100) DEFAULT NULL,
    telephone_livreur VARCHAR(30) DEFAULT NULL,
    statut ENUM('en_attente','assignee','en_route','livree','echec') DEFAULT 'en_attente',
    heure_depart DATETIME DEFAULT NULL,
    heure_livraison DATETIME DEFAULT NULL,
    notes TEXT,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE,
    INDEX idx_commande (commande_id),
    INDEX idx_statut (statut)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : avis
-- ============================================================
CREATE TABLE IF NOT EXISTS avis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plat_id INT DEFAULT NULL,
    commande_id INT DEFAULT NULL,
    note INT NOT NULL CHECK (note BETWEEN 1 AND 5),
    commentaire TEXT,
    approuve TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plat_id) REFERENCES plats(id) ON DELETE CASCADE,
    INDEX idx_plat (plat_id)
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : promotions
-- ============================================================
CREATE TABLE IF NOT EXISTS promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    type ENUM('pourcentage','montant_fixe') DEFAULT 'pourcentage',
    valeur DECIMAL(10,2) NOT NULL,
    montant_min DECIMAL(10,2) DEFAULT 0,
    date_debut DATE,
    date_fin DATE,
    utilisations_max INT DEFAULT NULL,
    utilisations INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

-- ============================================================
-- TABLE : options_plats (suppléments / personnalisation)
-- ============================================================
CREATE TABLE IF NOT EXISTS options_plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plat_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    prix_supplement DECIMAL(10,2) DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (plat_id) REFERENCES plats(id) ON DELETE CASCADE,
    INDEX idx_plat (plat_id)
) ENGINE=InnoDB;

-- ============================================================
-- DONNÉES INITIALES
-- ============================================================

-- Utilisateur admin par défaut : admin@foodexpress.sn / admin123
INSERT INTO users (nom, prenom, email, telephone, adresse, quartier, password_hash, role) VALUES
('Admin', 'FoodExpress', 'admin@foodexpress.sn', '+221 77 000 00 00', 'Siège FoodExpress', 'Plateau', '$2y$10$EZQYz0xpJOH4mh6Y1.LQK.7lEqXQHGmZQwGZpQGZpQGZpQGZpQGZ.', 'admin'),
('Ndiaye', 'Mouhamed', 'mouhamed@example.com', '+221 77 123 45 67', 'Avenue Bourguiba', 'Plateau', '$2y$10$EZQYz0xpJOH4mh6Y1.LQK.7lEqXQHGmZQwGZpQGZpQGZpQGZpQGZ.', 'client'),
('Diop', 'Aïssatou', 'aissatou@example.com', '+221 76 234 56 78', 'Rue Mohamed V', 'Almadies', '$2y$10$EZQYz0xpJOH4mh6Y1.LQK.7lEqXQHGmZQwGZpQGZpQGZpQGZpQGZ.', 'client'),
('Fall', 'Ousmane', 'ousmane@example.com', '+221 78 345 67 89', 'Cité Keur Gorgui', 'Point E', '$2y$10$EZQYz0xpJOH4mh6Y1.LQK.7lEqXQHGmZQwGZpQGZpQGZpQGZpQGZ.', 'client');

-- NOTE : les hash ci-dessus sont des placeholders.
-- Le script install.php régénérera les vrais hash automatiquement.

-- Catégories
INSERT INTO categories (nom, slug, description, icone, ordre) VALUES
('Plats Traditionnels', 'plats-traditionnels', 'Le meilleur de la cuisine sénégalaise', 'ramen_dining', 1),
('Grillades', 'grillades', 'Viandes et poissons grillés au feu de bois', 'outdoor_grill', 2),
('Riz & Céréales', 'riz-cereales', 'Tous nos plats de riz et céréales', 'rice_bowl', 3),
('Boissons', 'boissons', 'Bissap, gingembre, jus de baobab et plus', 'local_bar', 4),
('Desserts', 'desserts', 'Douceurs et pâtisseries', 'icecream', 5),
('Petit-déjeuner', 'petit-dejeuner', 'Bien commencer la journée', 'bakery_dining', 6);

-- Plats (avec images de la maquette)
INSERT INTO plats (categorie_id, nom, slug, description, ingredients, prix, image, temps_preparation, calories, epice, vedette, note_moyenne, nb_ventes) VALUES
(1, 'Thieboudienne Royal', 'thieboudienne-royal', 'Le plat national sénégalais. Riz rouge mijoté à la sauce tomate, accompagné de poisson thiof farci au persil et de légumes du marché : carotte, chou, aubergine et manioc.', 'Riz, poisson thiof, tomate, carotte, chou, aubergine, manioc, persil, ail, piment', 4500.00, 'thieboudienne.jpg', 45, 720, 1, 1, 4.9, 247),
(1, 'Yassa au Poulet', 'yassa-poulet', 'Poulet mariné au citron et oignons caramélisés, servi avec riz blanc parfumé. Une explosion de saveurs.', 'Poulet, oignons, citron, moutarde, riz, piment, laurier', 3500.00, 'yassa-poulet.jpg', 35, 650, 1, 1, 4.8, 198),
(1, 'Mafé à la Viande', 'mafe-viande', 'Sauce onctueuse à la pâte d''arachide et bœuf tendre, légumes mijotés. Le classique réconfortant.', 'Bœuf, pâte d''arachide, tomate, oignons, patate douce, carotte', 3800.00, 'mafe.jpg', 50, 780, 0, 1, 4.7, 156),
(1, 'Domoda au Poisson', 'domoda-poisson', 'Sauce arachide avec poisson frais et légumes verts.', 'Poisson, pâte d''arachide, gombo, épinards', 3600.00, 'domoda.jpg', 40, 620, 0, 0, 4.6, 89),
(1, 'Caldou au Poisson', 'caldou', 'Riz blanc accompagné d''un poisson en sauce citronnée.', 'Riz, poisson, citron, persil, piment', 3200.00, 'caldou.jpg', 35, 580, 0, 0, 4.5, 67),

(2, 'Poulet Yassa Grillé', 'poulet-grille', 'Cuisses de poulet marinées et grillées au feu de bois, sauce yassa à part.', 'Poulet, oignons, citron, épices', 4200.00, 'poulet-grille.jpg', 30, 540, 1, 1, 4.8, 134),
(2, 'Brochettes de Bœuf', 'brochettes-boeuf', 'Brochettes de bœuf marinées, grillées et servies avec frites et salade.', 'Bœuf, poivron, oignon, épices, frites', 3500.00, 'brochettes.jpg', 25, 620, 0, 0, 4.6, 92),
(2, 'Thiof Grillé', 'thiof-grille', 'Poisson thiof entier grillé, accompagné de riz et de sauce moutarde.', 'Thiof, riz, moutarde, citron', 5500.00, 'thiof.jpg', 35, 480, 0, 1, 4.9, 78),

(3, 'Riz au Gras (Ceeb u Yapp)', 'ceeb-yapp', 'Riz mijoté avec viande de bœuf et légumes assortis.', 'Riz, bœuf, légumes, tomate', 3800.00, 'ceeb-yapp.jpg', 50, 740, 0, 0, 4.7, 112),
(3, 'Couscous Sénégalais', 'couscous-senegalais', 'Couscous de mil avec sauce arachide et viande.', 'Mil, viande, pâte d''arachide, légumes', 3400.00, 'couscous.jpg', 45, 690, 0, 0, 4.5, 56),

(4, 'Bissap Frais', 'bissap', 'Boisson rafraîchissante à base de fleurs d''hibiscus, parfumée à la menthe.', 'Hibiscus, sucre, menthe, eau', 800.00, 'bissap.jpg', 5, 90, 0, 1, 4.9, 312),
(4, 'Jus de Gingembre', 'gingembre', 'Boisson piquante et énergisante au gingembre frais.', 'Gingembre, citron, sucre', 800.00, 'gingembre.jpg', 5, 75, 1, 0, 4.7, 178),
(4, 'Jus de Bouye (Baobab)', 'bouye', 'Jus crémeux à base de fruit du baobab, riche en vitamines.', 'Bouye, sucre, eau, vanille', 1000.00, 'bouye.jpg', 5, 110, 0, 0, 4.8, 145),
(4, 'Ataya (Thé à la menthe)', 'ataya', 'Thé vert à la menthe servi traditionnellement en trois passages.', 'Thé vert, menthe, sucre', 1500.00, 'ataya.jpg', 20, 60, 0, 0, 4.6, 89),

(5, 'Thiakry', 'thiakry', 'Couscous de mil au lait caillé sucré, vanille et raisins secs.', 'Mil, lait caillé, sucre, vanille, raisins', 1500.00, 'thiakry.jpg', 10, 320, 0, 1, 4.8, 167),
(5, 'Sombi', 'sombi', 'Riz au lait crémeux à la vanille et fleur d''oranger.', 'Riz, lait, sucre, vanille', 1200.00, 'sombi.jpg', 15, 280, 0, 0, 4.5, 78),

(6, 'Café Touba', 'cafe-touba', 'Café épicé traditionnel au poivre de Guinée (djar).', 'Café, poivre djar, clou de girofle, sucre', 700.00, 'cafe-touba.jpg', 8, 25, 1, 1, 4.9, 234),
(6, 'Bouillie de Mil (Lakh)', 'lakh', 'Bouillie de mil sucrée au lait caillé, parfumée à la vanille.', 'Mil, lait caillé, sucre, vanille', 1200.00, 'lakh.jpg', 15, 290, 0, 0, 4.6, 89);

-- Promotions
INSERT INTO promotions (code, description, type, valeur, montant_min, date_debut, date_fin, utilisations_max, actif) VALUES
('DAKAR20', 'Réduction de 20% sur la première commande', 'pourcentage', 20.00, 5000.00, '2026-01-01', '2026-12-31', 1000, 1),
('TERANGA', 'Bienvenue chez nous : -10% sur votre commande', 'pourcentage', 10.00, 3000.00, '2026-01-01', '2026-12-31', NULL, 1),
('LIVRAISON', 'Livraison offerte dès 8000 FCFA', 'montant_fixe', 1000.00, 8000.00, '2026-01-01', '2026-12-31', NULL, 1);

-- Quelques commandes de démo
INSERT INTO commandes (numero, user_id, sous_total, frais_livraison, total, adresse_livraison, quartier, telephone, statut, methode_paiement, created_at) VALUES
('CMD-2026-0001', 2, 8000.00, 1000.00, 9000.00, 'Avenue Bourguiba, Imm. Khadim', 'Plateau', '+221 77 123 45 67', 'livree', 'wave', '2026-05-19 12:30:00'),
('CMD-2026-0002', 3, 5500.00, 1000.00, 6500.00, 'Rue Mohamed V, Villa 24', 'Almadies', '+221 76 234 56 78', 'en_livraison', 'orange_money', '2026-05-21 11:45:00'),
('CMD-2026-0003', 4, 7200.00, 1000.00, 8200.00, 'Cité Keur Gorgui, Lot 12', 'Point E', '+221 78 345 67 89', 'en_preparation', 'especes', '2026-05-21 12:15:00'),
('CMD-2026-0004', 2, 3500.00, 1000.00, 4500.00, 'Avenue Bourguiba, Imm. Khadim', 'Plateau', '+221 77 123 45 67', 'confirmee', 'wave', '2026-05-21 12:50:00');

INSERT INTO details_commandes (commande_id, plat_id, nom_plat, prix_unitaire, quantite, sous_total) VALUES
(1, 1, 'Thieboudienne Royal', 4500.00, 1, 4500.00),
(1, 11, 'Bissap Frais', 800.00, 2, 1600.00),
(1, 15, 'Thiakry', 1500.00, 1, 1500.00),
(2, 8, 'Thiof Grillé', 5500.00, 1, 5500.00),
(3, 2, 'Yassa au Poulet', 3500.00, 2, 7000.00),
(3, 11, 'Bissap Frais', 800.00, 1, 800.00),
(4, 2, 'Yassa au Poulet', 3500.00, 1, 3500.00);

INSERT INTO livraisons (commande_id, livreur, telephone_livreur, statut, heure_depart, heure_livraison) VALUES
(1, 'Cheikh Diallo', '+221 77 888 99 00', 'livree', '2026-05-19 12:50:00', '2026-05-19 13:25:00'),
(2, 'Modou Sène', '+221 70 555 11 22', 'en_route', '2026-05-21 12:20:00', NULL),
(3, NULL, NULL, 'en_attente', NULL, NULL);

-- Paiements des commandes de démo
INSERT INTO paiements (commande_id, methode, montant, statut, reference, created_at) VALUES
(1, 'wave', 9000.00, 'valide', 'WAVE-7X4K2P', '2026-05-19 12:31:00'),
(2, 'orange_money', 6500.00, 'valide', 'OM-3M9Q1Z', '2026-05-21 11:46:00'),
(3, 'especes', 8200.00, 'en_attente', NULL, '2026-05-21 12:16:00'),
(4, 'wave', 4500.00, 'valide', 'WAVE-8B2N5T', '2026-05-21 12:51:00');

-- Avis clients
INSERT INTO avis (user_id, plat_id, commande_id, note, commentaire, approuve) VALUES
(2, 1, 1, 5, 'Livraison impeccable au Plateau. Mon Thieboudienne arrive toujours chaud et le goût est exactement comme celui de ma grand-mère.', 1),
(3, 8, 2, 5, 'Le Thiof grillé est divin ! Service rapide, je recommande.', 1),
(4, 2, NULL, 4, 'Très bon Yassa, oignons bien caramélisés. À refaire !', 1);

-- Options / suppléments des plats
INSERT INTO options_plats (plat_id, nom, prix_supplement, actif) VALUES
(1, 'Extra poisson thiof', 1500.00, 1),
(1, 'Piment fort supplémentaire', 0.00, 1),
(1, 'Riz supplémentaire', 800.00, 1),
(2, 'Extra poulet', 1800.00, 1),
(2, 'Oignons caramélisés en plus', 500.00, 1),
(3, 'Extra viande de bœuf', 2000.00, 1),
(3, 'Sauce arachide supplémentaire', 700.00, 1),
(6, 'Cuisse de poulet en plus', 1500.00, 1),
(6, 'Frites supplémentaires', 1000.00, 1),
(8, 'Demi-thiof en plus', 2800.00, 1);
