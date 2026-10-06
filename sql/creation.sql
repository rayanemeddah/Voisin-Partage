-- ============================================================
-- VoisinPartage — Script de création de la base de données
-- ============================================================

CREATE DATABASE IF NOT EXISTS voisinpartage
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE voisinpartage;

-- ------------------------------------------------------------
-- Table : utilisateurs
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs (
    id_utilisateur  INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    nom             VARCHAR(100)      NOT NULL,
    prenom          VARCHAR(100)      NOT NULL,
    email           VARCHAR(255)      NOT NULL UNIQUE,
    mot_de_passe    VARCHAR(255)      NOT NULL,
    telephone       VARCHAR(20)       DEFAULT NULL,
    adresse         VARCHAR(255)      DEFAULT NULL,
    quartier        VARCHAR(100)      DEFAULT NULL,
    photo_profil    VARCHAR(255)      NOT NULL DEFAULT 'default.png',
    role            ENUM('utilisateur','administrateur') NOT NULL DEFAULT 'utilisateur',
    actif           TINYINT(1)        NOT NULL DEFAULT 1,
    date_inscription DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_utilisateur)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : categories
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id_categorie INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nom          VARCHAR(100) NOT NULL,
    icone        VARCHAR(100) NOT NULL DEFAULT 'fa-solid fa-box',
    description  TEXT         DEFAULT NULL,
    PRIMARY KEY (id_categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : annonces
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS annonces (
    id_annonce      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    titre           VARCHAR(255)  NOT NULL,
    description     TEXT          NOT NULL,
    type            ENUM('pret','don') NOT NULL DEFAULT 'pret',
    statut          ENUM('disponible','reserve','indisponible') NOT NULL DEFAULT 'disponible',
    photo           VARCHAR(255)  NOT NULL DEFAULT 'no-image.png',
    date_publication DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur  INT UNSIGNED  NOT NULL,
    id_categorie    INT UNSIGNED  NOT NULL,
    PRIMARY KEY (id_annonce),
    CONSTRAINT fk_annonce_user FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_annonce_cat  FOREIGN KEY (id_categorie)
        REFERENCES categories(id_categorie)    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : demandes_emprunt
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS demandes_emprunt (
    id_demande   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_annonce   INT UNSIGNED NOT NULL,
    id_emprunteur INT UNSIGNED NOT NULL,
    message      TEXT         DEFAULT NULL,
    statut       ENUM('en_attente','acceptee','refusee','terminee') NOT NULL DEFAULT 'en_attente',
    date_demande DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_demande),
    CONSTRAINT fk_demande_annonce    FOREIGN KEY (id_annonce)
        REFERENCES annonces(id_annonce)        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_demande_emprunteur FOREIGN KEY (id_emprunteur)
        REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table : messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS messages (
    id_message      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_expediteur   INT UNSIGNED NOT NULL,
    id_destinataire INT UNSIGNED NOT NULL,
    contenu         TEXT         NOT NULL,
    date_envoi      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    lu              TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (id_message),
    CONSTRAINT fk_msg_expediteur   FOREIGN KEY (id_expediteur)
        REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_msg_destinataire FOREIGN KEY (id_destinataire)
        REFERENCES utilisateurs(id_utilisateur) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Index utiles
-- ------------------------------------------------------------
CREATE INDEX idx_annonces_statut      ON annonces(statut);
CREATE INDEX idx_annonces_type        ON annonces(type);
CREATE INDEX idx_annonces_date        ON annonces(date_publication);
CREATE INDEX idx_messages_destinataire ON messages(id_destinataire, lu);
CREATE INDEX idx_messages_expediteur  ON messages(id_expediteur);