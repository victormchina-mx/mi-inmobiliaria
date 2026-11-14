-- Terreno con hasta 2 decimales (p. ej. 496.50 m²)
--
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL). Los valores actuales se
-- conservan (496 → 496.00). Mientras no se ejecute, el terreno se guarda
-- redondeado a entero.

ALTER TABLE `property`
    MODIFY COLUMN `land_size` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'm² de terreno (hasta 2 decimales)';
