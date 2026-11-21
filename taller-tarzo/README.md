# TARZO 2.0

Sistema de gestión para taller automotriz: recepción con check-in fotográfico, cotización de
mecánica y hojalatería/pintura con aseguradoras, tablero Kanban de piso, almacén, seguimiento al
cliente por WhatsApp y facturación CFDI 4.0.

## Estado

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
