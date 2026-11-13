-- Varias inmobiliarias (App\Libraries\Agency)
--
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) sobre la base de datos del
-- sitio. Mientras no se ejecute, el sitio funciona igual que antes (una
-- sola inmobiliaria).
--
-- 1. Crea la tabla de inmobiliarias (logo, colores, contacto, encargado).
-- 2. Agrega a qué inmobiliaria pertenece cada agente y cada propiedad
--    (vacío = agente o propiedad independiente).
-- 3. Crea Grupo Inmobiliario Frices con su azul y dorado, pasa a Frices
--    todas las propiedades y todos los agentes y constructores actuales, y
--    deja como encargada a Minerva Molina (mine.molina27@gmail.com).

CREATE TABLE IF NOT EXISTS `agency` (
    `agency_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`          VARCHAR(120) NOT NULL,
    `slug`          VARCHAR(60)  NOT NULL,
    `logo`          VARCHAR(255) NULL DEFAULT NULL COMMENT 'archivo en public/uploads/agency',
    `color_primary` CHAR(7)      NOT NULL DEFAULT '#0A2342',
    `color_accent`  CHAR(7)      NOT NULL DEFAULT '#D1A84B',
    `tagline`       VARCHAR(160) NULL DEFAULT NULL,
    `phone`         VARCHAR(30)  NULL DEFAULT NULL,
    `whatsapp`      VARCHAR(30)  NULL DEFAULT NULL,
    `email`         VARCHAR(150) NULL DEFAULT NULL,
    `website`       VARCHAR(200) NULL DEFAULT NULL,
    `manager_uid`   INT          NULL DEFAULT NULL COMMENT 'encargado (usuario)',
    `status`        VARCHAR(20)  NOT NULL DEFAULT 'active' COMMENT 'active, paused',
    `created_at`    DATETIME     NULL DEFAULT NULL,
    `updated_at`    DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`agency_id`),
    UNIQUE KEY `agency_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `user`
    ADD COLUMN `agency_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'inmobiliaria (vacío = independiente)',
    ADD KEY `user_agency` (`agency_id`);

ALTER TABLE `property`
    ADD COLUMN `agency_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'inmobiliaria (vacío = independiente)',
    ADD KEY `property_agency` (`agency_id`);

INSERT INTO `agency` (`name`, `slug`, `color_primary`, `color_accent`, `tagline`, `website`, `status`, `created_at`, `updated_at`)
VALUES ('Grupo Inmobiliario Frices', 'frices', '#0A2342', '#D1A84B',
        'Fortaleza • Resiliencia • Integridad • Confianza', 'https://www.frices.com.mx', 'active', NOW(), NOW());

UPDATE `property` SET `agency_id` = (SELECT `agency_id` FROM `agency` WHERE `slug` = 'frices');

UPDATE `user` SET `agency_id` = (SELECT `agency_id` FROM `agency` WHERE `slug` = 'frices')
WHERE `utype` IN ('agent', 'builder');

UPDATE `agency` a
JOIN `user` u ON u.`uemail` = 'mine.molina27@gmail.com'
SET a.`manager_uid` = u.`uid`
WHERE a.`slug` = 'frices';
