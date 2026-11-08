-- Punto exacto de cada propiedad en el mapa (App\Libraries\PropertyLocation)
--
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) sobre la base de datos del
-- sitio, ANTES de hacer git pull del cambio que usa estas columnas.
-- Las propiedades existentes quedan sin punto (NULL) y se siguen ubicando
-- con su dirección, como hasta ahora.

ALTER TABLE `property`
    ADD COLUMN `lat` DECIMAL(9,6) NULL DEFAULT NULL AFTER `state`,
    ADD COLUMN `lng` DECIMAL(9,6) NULL DEFAULT NULL AFTER `lat`,
    ADD COLUMN `exact_location` TINYINT(1) NOT NULL DEFAULT 0 AFTER `lng`;
