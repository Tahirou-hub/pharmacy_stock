-- Script de correction pour la table achats
-- Ajoute la colonne prix_unitaire si elle n'existe pas

USE pharmacy_stock;

-- Vérifier et ajouter prix_unitaire
SET @dbname = DATABASE();
SET @tablename = "achats";
SET @columnname = "prix_unitaire";

-- Vérifier si la colonne existe
SELECT COUNT(*) INTO @column_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_schema = @dbname
  AND table_name = @tablename
  AND column_name = @columnname;

-- Ajouter la colonne si elle n'existe pas
SET @sql = IF(@column_exists = 0,
    CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite'),
    'SELECT "La colonne prix_unitaire existe déjà" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;




