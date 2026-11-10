-- Datos para la ficha técnica y la agenda (App\Libraries\PropertyExtras)
--
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) sobre la base de datos del
-- sitio. Las propiedades existentes quedan sin estos datos (NULL) y la ficha
-- técnica simplemente no los muestra hasta que se llenen.

ALTER TABLE `property`
    ADD COLUMN `land_size`   INT UNSIGNED      NULL DEFAULT NULL COMMENT 'm² de terreno'        AFTER `size`,
    ADD COLUMN `parking`     TINYINT UNSIGNED  NULL DEFAULT NULL COMMENT 'estacionamientos'     AFTER `land_size`,
    ADD COLUMN `age_years`   SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT 'antigüedad en años'   AFTER `parking`,
    ADD COLUMN `keys_status` VARCHAR(30)       NULL DEFAULT NULL COMMENT 'dónde están las llaves' AFTER `age_years`,
    ADD COLUMN `keys_note`   VARCHAR(120)      NULL DEFAULT NULL COMMENT 'p. ej. llavero 12'    AFTER `keys_status`;
