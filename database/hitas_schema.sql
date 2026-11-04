-- =========================================================
-- HITAS — Institut Hanséatique de Technologie et de Sciences Appliquées
-- SCHÉMA UNIQUE ET COMPLET — remplace hitas_complete.sql et TOUS les
-- fichiers add_*.sql / migration_*.sql / cleanup_*.sql du dossier database/.
--
-- N'IMPORTEZ QUE CE FICHIER, aucun autre.
-- Sur une base neuve :
--   mysql -u root -p < database/hitas_schema.sql
--
-- Sur une base existante : sauvegardez d'abord vos données (elles seront
-- écrasées), ou demandez une version "diff" si vous préférez ne pas tout
-- réimporter.
--
-- Ce fichier reflète l'état réel et actuel du site : un seul campus
-- (Douala), pas de newsletter, filières phares, mise à niveau par blocs,
-- équipe à 3 niveaux (Or/Argent/Bronze), fiches "campus annexe" avec
-- localisation.
-- =========================================================

DROP DATABASE IF EXISTS hitas_db;
CREATE DATABASE hitas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hitas_db;

-- ---------------------------------------------------------
-- Campus (un seul, Douala) — coordonnées utilisées dans le pied de page
-- et pour les emails/WhatsApp des demandes reçues. Géré depuis
-- /admin/campuses (une seule fiche, pas une liste).
-- ---------------------------------------------------------
CREATE TABLE campuses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255),
    phone VARCHAR(100),
    email VARCHAR(150),
    maps_query VARCHAR(255),
    sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

INSERT INTO campuses (code, name, address, phone, email, maps_query, sort_order) VALUES
('douala', 'Campus de Douala', '740 rue Richard Bell, Bali-Doula (montée maga bell) / P.O box 5696 Douala', '658595959 / 651555858', 'contact@hita.org / registrar@hitas.org', '740 rue Richard Bell, Bali-Doula (montée maga bell) / P.O box 5696 Douala', 1);

-- ---------------------------------------------------------
-- Utilisateurs admin (accès complet pour tous — plus de distinction
-- par campus ; le rôle "super_admin" donne accès à Utilisateurs et
-- aux Informations du campus)
-- ---------------------------------------------------------
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(30) NOT NULL DEFAULT 'agent',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL
) ENGINE=InnoDB;

-- Compte super-admin par défaut : admin / admin123 -> à changer immédiatement après connexion
INSERT INTO admin_users (username, email, password_hash, role) VALUES
('admin', 'admin@hitas.edu', '$2b$10$XmqKLMiRZoSqy/k8q/00ZOS7ji8SM8ohVz3YvwuWydmpkzvwqaBfS', 'super_admin');

-- ---------------------------------------------------------
-- Filières (programmes de formation)
-- ---------------------------------------------------------
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL UNIQUE,
    meta_title VARCHAR(200) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    og_image VARCHAR(255) DEFAULT NULL,
    short_description VARCHAR(500),
    description TEXT,
    duration VARCHAR(50),
    prerequisites TEXT,
    outcomes TEXT,
    image VARCHAR(255),
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    featured_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Blocs de mise à niveau (page Programmes) : regroupent plusieurs
-- filières partageant le même parcours préparatoire, avec leur durée.
-- ---------------------------------------------------------
CREATE TABLE prep_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    duration_label VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE prep_block_programs (
    prep_block_id INT NOT NULL,
    program_id INT NOT NULL,
    PRIMARY KEY (prep_block_id, program_id),
    FOREIGN KEY (prep_block_id) REFERENCES prep_blocks(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Universités / établissements partenaires à l'étranger
-- ---------------------------------------------------------
CREATE TABLE partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    country VARCHAR(100) NOT NULL,
    flag_emoji VARCHAR(10) DEFAULT NULL,
    logo VARCHAR(255),
    description TEXT,
    website VARCHAR(255),
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Relation N-N entre filières et partenaires (gérable depuis les deux côtés)
CREATE TABLE program_partners (
    program_id INT NOT NULL,
    partner_id INT NOT NULL,
    PRIMARY KEY (program_id, partner_id),
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Actualités / Blog (avec lien externe pour les publicités)
-- ---------------------------------------------------------
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(210) NOT NULL UNIQUE,
    meta_title VARCHAR(200) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    og_image VARCHAR(255) DEFAULT NULL,
    excerpt VARCHAR(500),
    content TEXT,
    image VARCHAR(255),
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    is_advertisement TINYINT(1) NOT NULL DEFAULT 0,
    external_link VARCHAR(500) DEFAULT NULL,
    published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Équipe (3 catégories : Or = Direction, Argent = Responsables,
-- Bronze = Employés) — contact affiché pour toutes les catégories,
-- description détaillée + lien de vérification réservés à l'Or.
-- ---------------------------------------------------------
CREATE TABLE team_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    role VARCHAR(150),
    tier VARCHAR(10) NOT NULL DEFAULT 'bronze',
    contact_email VARCHAR(150) DEFAULT NULL,
    contact_whatsapp VARCHAR(50) DEFAULT NULL,
    photo VARCHAR(255),
    bio TEXT,
    verify_link VARCHAR(255) DEFAULT NULL,
    order_index INT DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Témoignages (alumni)
-- ---------------------------------------------------------
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    photo VARCHAR(255),
    video VARCHAR(255),
    destination_country VARCHAR(100),
    program VARCHAR(150),
    content TEXT NOT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Galerie photo (vie étudiante / campus)
-- ---------------------------------------------------------
CREATE TABLE gallery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150),
    image VARCHAR(255) NOT NULL,
    video VARCHAR(255) NULL,
    category VARCHAR(100),
    sort_order INT NOT NULL DEFAULT 0,
    description TEXT,
    program_id INT DEFAULT NULL,
    partner_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Fiches "campus annexe" (page À propos, sous "Notre mission") — table
-- indépendante de "campuses" : sert uniquement à présenter un campus
-- secondaire (photo, localisation, mini description, détails).
-- ---------------------------------------------------------
CREATE TABLE campus_highlights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    location VARCHAR(255) DEFAULT NULL,
    title VARCHAR(200) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    short_description VARCHAR(300) DEFAULT NULL,
    full_description TEXT DEFAULT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- FAQ
-- ---------------------------------------------------------
CREATE TABLE faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(300) NOT NULL,
    answer TEXT NOT NULL,
    order_index INT DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

INSERT INTO faq (question, answer, order_index, is_published) VALUES
('Comment postuler à HITAS ?', 'Rendez-vous sur la page Postulez et complétez le formulaire en ligne, ou déposez votre dossier de candidature complet au secrétariat, conformément aux conditions détaillées sur la page Admissions.', 1, 1),
('Quelles sont les conditions d''admission ?', 'L''admission se fait sur dossier : lettre de candidature, acte de naissance, GCE A/Level ou baccalauréat, bulletins scolaires, photos d''identité et frais de traitement de dossier. Le détail complet et le calendrier figurent sur la page Admissions.', 2, 1),
('Combien de temps dure la préparation à HITAS ?', 'Selon la filière choisie, la préparation dure une à deux années avant le départ vers l''une de nos universités partenaires à l''étranger.', 3, 1),
('Quelles universités partenaires accueillent les diplômés HITAS ?', 'HITAS travaille avec plusieurs universités partenaires en Europe et en Asie. La liste complète et les filières associées sont présentées sur la page Partenaires.', 4, 1),
('Comment contacter HITAS pour plus d''informations ?', 'Vous pouvez nous écrire par e-mail ou WhatsApp aux coordonnées indiquées en pied de page, ou nous laisser un message via le formulaire Postulez.', 5, 1);

-- ---------------------------------------------------------
-- Pages statiques / éditables (contenu + SEO)
-- ---------------------------------------------------------
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    meta_title VARCHAR(200) DEFAULT NULL,
    meta_description VARCHAR(300) DEFAULT NULL,
    og_image VARCHAR(255) DEFAULT NULL,
    content TEXT,
    image VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO pages (slug, title, content) VALUES
('a-propos', 'À propos de HITAS', '<p>Contenu à compléter via l\'admin.</p>'),
('mission', 'Notre mission', '<p>Contenu à compléter via l\'admin.</p>'),
('admissions', 'Admissions', '<p>Contenu à compléter via l\'admin.</p>'),
('accueil', 'Accueil', NULL),
('programmes', 'Programmes', NULL),
('partenaires', 'Nos universités partenaires', NULL),
('actualites', 'Actualités', NULL),
('equipe', 'Notre équipe', NULL),
('faq', 'Questions fréquentes', NULL);

-- ---------------------------------------------------------
-- Messages reçus (contact / demandes) — suivi par statut
-- (non_traite / en_cours / traite), plus de distinction par campus.
-- ---------------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(50),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    status VARCHAR(20) NOT NULL DEFAULT 'non_traite',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Paramètres généraux (clé / valeur)
-- ---------------------------------------------------------
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'HITAS - Hanseatic Institute of Technology and Applied Sciences'),
('logo', 'hitas-logo.png'),
('primary_color', '#002147'),
('accent_color', '#9c7c3c'),
('tiktok_url', ''),
('home_hero_bg', ''),
('contact_email', 'contact@hita.org / registrar@hitas.org'),
('contact_phone', '658595959 / 651555858'),
('call_phone', ''),
('address', '740 rue Richard Bell, Bali-Doula (montée maga bell) / P.O box 5696 Douala'),
('facebook_url', 'https://www.facebook.com/HanseaticInstituteOfTechnologyAndAppliedSciences'),
('linkedin_url', ''),
('instagram_url', ''),
('whatsapp_channel_url', '');

-- ---------------------------------------------------------
-- Diaporama de l'accueil (sous le menu) — image ou vidéo, titre,
-- sous-titre, bouton, durée par slide
-- ---------------------------------------------------------
CREATE TABLE hero_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    image VARCHAR(255) DEFAULT NULL,
    title VARCHAR(200) DEFAULT NULL,
    subtitle VARCHAR(300) DEFAULT NULL,
    button_text VARCHAR(100) DEFAULT NULL,
    button_link VARCHAR(255) DEFAULT NULL,
    video VARCHAR(255) DEFAULT NULL,
    duration_ms INT NOT NULL DEFAULT 6000,
    caption VARCHAR(255) DEFAULT NULL,
    sort_order INT DEFAULT 0,
    is_published TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Menu de navigation du site public
-- ---------------------------------------------------------
CREATE TABLE nav_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(60) NOT NULL,
    url VARCHAR(255) NOT NULL,
    order_index INT NOT NULL DEFAULT 0,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    page_key VARCHAR(60) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO nav_menu (label, url, order_index, is_visible, page_key) VALUES
('Accueil', '/index.php', 1, 1, 'accueil'),
('À propos', '/a-propos.php', 2, 1, 'a-propos'),
('Programmes', '/programmes.php', 3, 1, 'programmes'),
('Partenaires', '/partenaires.php', 4, 1, 'partenaires'),
('Actualités', '/actualites.php', 5, 1, 'actualites'),
('Équipe', '/equipe.php', 6, 1, 'equipe'),
('FAQ', '/faq.php', 7, 1, 'faq');

-- ---------------------------------------------------------
-- Blocs de la page d'accueil : activation, ordre d'affichage
-- (pas de bloc "news" séparé : les actualités vivent dans le hero)
-- ---------------------------------------------------------
CREATE TABLE home_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_key VARCHAR(40) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO home_sections (section_key, label, sort_order, is_active) VALUES
('hero',         'Hero (bandeau principal + diaporama + actualités)', 1, 1),
('stats',        'Chiffres clés',                                    2, 1),
('programs',     'Filières phares',                                  3, 1),
('partners',     'Partenaires',                                      4, 1),
('pillars',      'Nos trois piliers',                                5, 1),
('testimonials', 'Témoignages',                                      6, 1),
('cta',          'Bandeau d\'appel à l\'action final',               7, 1);

-- ---------------------------------------------------------
-- Statistiques (chiffres clés, bande défilante)
-- ---------------------------------------------------------
CREATE TABLE stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    value VARCHAR(50) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO stats (label, value, icon, sort_order, is_active) VALUES
('Filières', '14', 'bi-mortarboard', 1, 1),
('Universités partenaires', '8', 'bi-globe2', 2, 1),
('Pays de destination', '5', 'bi-geo-alt', 3, 1);

-- ---------------------------------------------------------
-- Photos d'une actualité (plusieurs par article, dont une de couverture)
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS news_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    image VARCHAR(255) NOT NULL,
    is_cover TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_news_id (news_id),
    CONSTRAINT fk_news_images_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
