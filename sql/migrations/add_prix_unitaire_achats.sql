-- Migration: Ajouter prix_unitaire à la table achats si elle n'existe pas
-- À exécuter si la colonne prix_unitaire est manquante dans la table achats

USE pharmacy_stock;

-- Vérifier et ajouter prix_unitaire si nécessaire
SET @dbname = DATABASE();
SET @tablename = "achats";
SET @columnname = "prix_unitaire";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'La colonne prix_unitaire existe déjà' AS result;",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite;")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;




