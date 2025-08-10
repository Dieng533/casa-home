-- =====================================================
-- SCHEMA BASE DE DONNÉES CASA HOME
-- =====================================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS casa_home_db
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE casa_home_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('tourist', 'family', 'admin') NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des familles d'accueil
CREATE TABLE families (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    nom_famille VARCHAR(100) NOT NULL,
    localisation VARCHAR(255) NOT NULL,
    presentation TEXT,
    tarif DECIMAL(10,2) NOT NULL,
    capacite INT DEFAULT 4,
    equipements TEXT,
    activites TEXT,
    disponibilite BOOLEAN DEFAULT TRUE,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des images
CREATE TABLE images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE
);

-- Table des réservations
CREATE TABLE reservations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    family_id INT NOT NULL,
    tourist_id INT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests INT NOT NULL,
    message TEXT,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (tourist_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des avis et notes
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reservation_id INT NOT NULL,
    family_id INT NOT NULL,
    tourist_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    commentaire TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (family_id) REFERENCES families(id) ON DELETE CASCADE,
    FOREIGN KEY (tourist_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Index pour optimiser les performances
CREATE INDEX idx_families_location ON families(localisation);
CREATE INDEX idx_families_status ON families(status);
CREATE INDEX idx_reservations_dates ON reservations(check_in, check_out);
CREATE INDEX idx_reservations_status ON reservations(status);
CREATE INDEX idx_images_family ON images(family_id);
CREATE INDEX idx_images_primary ON images(is_primary);

-- Insertion de données de test
INSERT INTO users (email, password, role, nom, prenom, telephone) VALUES
('admin@casahome.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'Casa', '+221777777777'),
('famille.diatta@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'family', 'Diatta', 'Mariama', '+221777777778'),
('touriste.jean@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tourist', 'Dupont', 'Jean', '+33123456789');

INSERT INTO families (user_id, nom_famille, localisation, presentation, tarif, capacite, equipements, activites) VALUES
(2, 'Famille Diatta', 'Ziguinchor, Casamance', 'La famille Diatta vous accueille dans leur maison traditionnelle au cœur d\'un village typique de Casamance. Cette famille chaleureuse vous fera découvrir la culture locale, la cuisine traditionnelle et les coutumes de la région.', 12000.00, 4, 'Salle de bain privée,Wi-Fi gratuit,Ventilateur,Draps et serviettes fournis', 'Cuisine locale traditionnelle,Visite du village,Atelier d\'artisanat local,Excursion en forêt');

-- Insertion d'images de test
INSERT INTO images (family_id, filename, original_name, file_path, file_size, mime_type, is_primary) VALUES
(1, 'famille_diatta_1.jpg', 'famille_diatta_1.jpg', '/assets/uploads/families/famille_diatta_1.jpg', 192000, 'image/jpeg', TRUE),
(1, 'famille_diatta_2.jpg', 'famille_diatta_2.jpg', '/assets/uploads/families/famille_diatta_2.jpg', 511000, 'image/jpeg', FALSE),
(1, 'famille_diatta_3.jpg', 'famille_diatta_3.jpg', '/assets/uploads/families/famille_diatta_3.jpg', 664000, 'image/jpeg', FALSE);

-- Vues utiles
CREATE VIEW family_details AS
SELECT 
    f.*,
    u.email,
    u.telephone,
    GROUP_CONCAT(i.file_path) as images,
    AVG(r.rating) as average_rating,
    COUNT(r.id) as total_reviews
FROM families f
LEFT JOIN users u ON f.user_id = u.id
LEFT JOIN images i ON f.id = i.family_id
LEFT JOIN reviews r ON f.id = r.family_id
WHERE f.status = 'approved'
GROUP BY f.id;

-- Procédure pour obtenir les réservations d'une famille
DELIMITER //
CREATE PROCEDURE GetFamilyReservations(IN family_id_param INT)
BEGIN
    SELECT 
        r.*,
        u.nom as tourist_nom,
        u.prenom as tourist_prenom,
        u.email as tourist_email
    FROM reservations r
    JOIN users u ON r.tourist_id = u.id
    WHERE r.family_id = family_id_param
    ORDER BY r.created_at DESC;
END //
DELIMITER ;

-- Procédure pour obtenir les réservations d'un touriste
DELIMITER //
CREATE PROCEDURE GetTouristReservations(IN tourist_id_param INT)
BEGIN
    SELECT 
        r.*,
        f.nom_famille,
        f.localisation,
        u.nom as family_nom,
        u.telephone as family_telephone
    FROM reservations r
    JOIN families f ON r.family_id = f.id
    JOIN users u ON f.user_id = u.id
    WHERE r.tourist_id = tourist_id_param
    ORDER BY r.created_at DESC;
END //
DELIMITER ;
