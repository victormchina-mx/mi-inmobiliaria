-- Agenda de citas compartida entre agentes (App\Libraries\Agenda)
--
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) sobre la base de datos del
-- sitio. Mientras la tabla no exista, la agenda muestra un aviso y el resto
-- del sitio sigue funcionando igual.
--
-- Cada cita tiene 3 partes:
--   1. El inmueble (pid): ID, dirección y zona, agente responsable y llaves
--      se leen de la propiedad.
--   2. La cita: fecha y hora, agente que muestra y datos del cliente.
--   3. El resultado: le interesó / oferta / descartado, comentarios del
--      cliente y próxima acción de seguimiento.

CREATE TABLE IF NOT EXISTS `appointment` (
    `apid`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pid`          INT          NOT NULL                COMMENT 'propiedad',
    `starts_at`    DATETIME     NOT NULL,
    `ends_at`      DATETIME     NOT NULL,
    `shower_uid`   INT          NOT NULL                COMMENT 'agente que muestra',
    `created_by`   INT          NOT NULL                COMMENT 'agente que agendó',
    `client_name`  VARCHAR(120) NOT NULL,
    `client_phone` VARCHAR(30)  NULL DEFAULT NULL,
    `client_email` VARCHAR(150) NULL DEFAULT NULL,
    `client_notes` VARCHAR(500) NULL DEFAULT NULL,
    `result`       VARCHAR(20)  NOT NULL DEFAULT 'pending' COMMENT 'pending, interested, offer, discarded',
    `comments`     TEXT         NULL,
    `next_action`  VARCHAR(160) NULL DEFAULT NULL,
    `next_date`    DATE         NULL DEFAULT NULL,
    `created_at`   DATETIME     NULL DEFAULT NULL,
    `updated_at`   DATETIME     NULL DEFAULT NULL,
    PRIMARY KEY (`apid`),
    KEY `appointment_starts` (`starts_at`),
    KEY `appointment_pid` (`pid`, `starts_at`),
    KEY `appointment_shower` (`shower_uid`, `starts_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
