-- Texto propio del agente para "¿Por qué vivir aquí?" (App\Libraries\WhyText)
--
-- Ejecutar UNA vez en phpMyAdmin (pestaña SQL) sobre la base de datos del
-- sitio. Las propiedades existentes quedan sin texto (NULL) y siguen usando
-- el texto automático.

ALTER TABLE `property`
    ADD COLUMN `why_text` TEXT NULL DEFAULT NULL AFTER `feature`;
