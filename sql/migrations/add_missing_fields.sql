-- Migration: Ajout des champs manquants
-- À exécuter si la base de données existe déjà

USE pharmacy_stock;

-- Ajouter prix_achat à medicaments (si n'existe pas)
ALTER TABLE medicaments 
ADD COLUMN IF NOT EXISTS prix_achat DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER prix;

-- Ajouter agent_id et total à ventes (si n'existent pas)
ALTER TABLE ventes 
ADD COLUMN IF NOT EXISTS agent_id INT NULL AFTER id,
ADD COLUMN IF NOT EXISTS total DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER prix_unitaire;

-- Ajouter la clé étrangère pour agent_id
ALTER TABLE ventes 
ADD CONSTRAINT IF NOT EXISTS fk_ventes_agent 
FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE SET NULL;

-- Ajouter prix_achat à vente_items (si n'existe pas)
ALTER TABLE vente_items 
ADD COLUMN IF NOT EXISTS prix_achat DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER prix_unitaire;

-- Ajouter des index pour améliorer les performances
CREATE INDEX IF NOT EXISTS idx_medicaments_nom ON medicaments(nom);
CREATE INDEX IF NOT EXISTS idx_ventes_date ON ventes(date_vente);
CREATE INDEX IF NOT EXISTS idx_ventes_agent ON ventes(agent_id);
CREATE INDEX IF NOT EXISTS idx_vente_items_vente ON vente_items(vente_id);
CREATE INDEX IF NOT EXISTS idx_vente_items_medicament ON vente_items(medicament_id);
CREATE INDEX IF NOT EXISTS idx_achats_date ON achats(date_achat);

-- Ajouter des contraintes CHECK (MySQL 8.0.16+)
-- Note: Ces contraintes peuvent ne pas fonctionner sur les anciennes versions de MySQL
ALTER TABLE medicaments 
ADD CONSTRAINT IF NOT EXISTS chk_medicaments_prix_positif CHECK (prix >= 0),
ADD CONSTRAINT IF NOT EXISTS chk_medicaments_prix_achat_positif CHECK (prix_achat >= 0),
ADD CONSTRAINT IF NOT EXISTS chk_medicaments_quantite_positif CHECK (quantite >= 0);

ALTER TABLE ventes 
ADD CONSTRAINT IF NOT EXISTS chk_ventes_total_positif CHECK (total >= 0);

ALTER TABLE vente_items 
ADD CONSTRAINT IF NOT EXISTS chk_vente_items_quantite_positif CHECK (quantite > 0),
ADD CONSTRAINT IF NOT EXISTS chk_vente_items_prix_positif CHECK (prix_unitaire >= 0);

ALTER TABLE achats 
ADD CONSTRAINT IF NOT EXISTS chk_achats_quantite_positif CHECK (quantite > 0),
ADD CONSTRAINT IF NOT EXISTS chk_achats_prix_positif CHECK (prix_unitaire >= 0);


