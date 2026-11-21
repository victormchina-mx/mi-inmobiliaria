# TARZO 2.0

Sistema de gestión para taller automotriz: recepción con check-in fotográfico, cotización de
mecánica y hojalatería/pintura con aseguradoras, tablero Kanban de piso, almacén, seguimiento al
cliente por WhatsApp y facturación CFDI 4.0.

## Estado

**Módulo 2 · Cotización y valuación (listo):**

- Cotizador de mecánica (operaciones con horas estándar, refacciones con margen, paquetes de servicio).
- Hojalatería y pintura por pieza: horas de laminado y pintura, materiales por m² según tipo de pintura
  y nivel de daño, pieza nueva si se sustituye, ciclos de cabina.
- Aseguradoras: tabulador con tarifas por área y descuentos; expediente del siniestro con ajustador,
  deducible (monto o porcentaje), demérito, salvamento y orden de admisión; reparto automático
  entre lo que paga la aseguradora y lo que paga el cliente.
- Versiones y suplementos: lo ya autorizado se conserva; solo se pide autorización de lo nuevo.
- Aprobación remota por enlace (cliente y ajustador por separado), partida por partida, con evidencia;
  aviso al asesor, registro de autorizaciones telefónicas y recordatorio a las 24 h.
- Hallazgos del diagnóstico pasan a la cotización con un clic. PDF de presupuesto y de valuación.

**Módulo 1 · Recepción y diagnóstico (listo):**

- Clientes (personas y empresas) con aviso de duplicados, datos fiscales CFDI 4.0 y constancia en PDF.
- Flotillas con convenio (crédito, descuentos) y contactos autorizados.
- Vehículos con VIN validado y decodificado (NHTSA), pólizas con aviso de vencimiento, historial y cambio de dueño.
- Recibir vehículo: orden con folio, tipo (particular, seguro, flotilla, garantía) y datos del siniestro.
- Check-in para tableta: fotos, daños sobre diagrama, gasolina, kilometraje, testigos, inventario, video y firma.
- Firma del cliente en tableta o por enlace (WhatsApp) con evidencia: fecha, IP, dispositivo y huella del contenido.
- PDF del inventario de ingreso, enviado por correo al cliente.
- Diagnóstico: hallazgos con semáforo de prioridad, códigos OBD-II y fotos.
- Búsqueda global por folio, placas, VIN, siniestro, nombre, teléfono o RFC.

**Fase 0 · Fundaciones:**

- Laravel 13 + Filament 5, en español y con zona horaria de México.
- Roles y permisos del taller (dueño, asesor, valuador, jefe de taller, técnico, almacén, contabilidad).
- Verificación en dos pasos obligatoria en producción para dueño y contabilidad.
- Modelo de datos base: clientes, flotillas, datos fiscales (CFDI 4.0), vehículos, pólizas,
  aseguradoras, órdenes de servicio, etapas del tablero e historial.
- Catálogos administrables: áreas y tarifas, bahías y cabinas, técnicos, etapas, aseguradoras, usuarios.
- Auditoría de cambios, cabeceras de seguridad, folios consecutivos.
- Listo para hosting compartido de Hostinger y preparado para migrar a VPS (`deploy/`).

## Desarrollo local

```bash
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan tarzo:crear-usuario tu@correo.com --rol=dueno
php artisan serve
```

Abre http://localhost:8000/panel.

## Producción

- Hosting compartido: [deploy/hostinger-compartido/INSTALACION.md](deploy/hostinger-compartido/INSTALACION.md)
- Migración a VPS: [deploy/vps/MIGRACION-A-VPS.md](deploy/vps/MIGRACION-A-VPS.md)
