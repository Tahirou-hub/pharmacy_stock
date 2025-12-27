-- Migration: Ajout de la colonne prix_unitaire à la table achats
-- À exécuter si la colonne n'existe pas déjà

USE pharmacy_stock;

-- Ajouter prix_unitaire à achats (si n'existe pas)
ALTER TABLE achats 
ADD COLUMN IF NOT EXISTS prix_unitaire DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite;

-- Ajouter la contrainte CHECK pour prix_unitaire
ALTER TABLE achats 
ADD CONSTRAINT IF NOT EXISTS chk_achats_prix_positif CHECK (prix_unitaire >= 0);

