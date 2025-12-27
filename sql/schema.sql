-- sql/schema.sql

CREATE DATABASE IF NOT EXISTS pharmacy_stock DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
USE pharmacy_stock;

-- Table des médicaments
CREATE TABLE medicaments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    description TEXT,
    prix DECIMAL(10,2) NOT NULL,
    prix_achat DECIMAL(10,2) NOT NULL DEFAULT 0,
    quantite INT NOT NULL DEFAULT 0,
    seuil_rupture INT NOT NULL DEFAULT 10,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_medicaments_nom (nom),
    CONSTRAINT chk_medicaments_prix_positif CHECK (prix >= 0),
    CONSTRAINT chk_medicaments_prix_achat_positif CHECK (prix_achat >= 0),
    CONSTRAINT chk_medicaments_quantite_positif CHECK (quantite >= 0)
);

-- Table des achats
CREATE TABLE achats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    date_achat DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id) ON DELETE CASCADE,
    INDEX idx_achats_date (date_achat),
    CONSTRAINT chk_achats_quantite_positif CHECK (quantite > 0),
    CONSTRAINT chk_achats_prix_positif CHECK (prix_unitaire >= 0)
);

-- Table des ventes
CREATE TABLE ventes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NULL,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL,
    prix_unitaire DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    date_vente DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (medicament_id) REFERENCES medicaments(id) ON DELETE CASCADE,
    INDEX idx_ventes_date (date_vente),
    INDEX idx_ventes_agent (agent_id),
    CONSTRAINT chk_ventes_total_positif CHECK (total >= 0)
);

-- table utilisateurs
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','agent') NOT NULL DEFAULT 'agent',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- table vente_items (pour plusieurs produits par vente)
CREATE TABLE vente_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vente_id INT NOT NULL,
  medicament_id INT NOT NULL,
  quantite INT NOT NULL,
  prix_unitaire DECIMAL(10,2) NOT NULL,
  prix_achat DECIMAL(10,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE,
  FOREIGN KEY (medicament_id) REFERENCES medicaments(id) ON DELETE CASCADE,
  INDEX idx_vente_items_vente (vente_id),
  INDEX idx_vente_items_medicament (medicament_id),
  CONSTRAINT chk_vente_items_quantite_positif CHECK (quantite > 0),
  CONSTRAINT chk_vente_items_prix_positif CHECK (prix_unitaire >= 0)
);

-- table factures pour chaque vente
CREATE TABLE factures (
  id INT AUTO_INCREMENT PRIMARY KEY,
  vente_id INT NOT NULL,
  numero VARCHAR(100) NOT NULL UNIQUE,
  date_facture DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (vente_id) REFERENCES ventes(id) ON DELETE CASCADE
);

