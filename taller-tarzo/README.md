# TARZO 2.0

Sistema de gestión para taller automotriz: recepción con check-in fotográfico, cotización de
mecánica y hojalatería/pintura con aseguradoras, tablero Kanban de piso, almacén, seguimiento al
cliente por WhatsApp y facturación CFDI 4.0.

## Estado

**Módulo 6 · Administración, facturación y reportes (listo):**

- Facturación CFDI 4.0 desde la orden con las partidas autorizadas y claves SAT por concepto; XML y PDF
  (con QR de verificación) se guardan y se envían al receptor.
- Orden de seguro: dos facturas automáticas. Aseguradora (PPD, a crédito) por lo cubierto menos deducible,
  demérito y salvamento, y cliente (PUE) por el deducible y lo no cubierto; cuadran con el Cotizador.
- Complemento de recepción de pagos 2.0 al cobrar facturas PPD (parcialidades y saldo insoluto).
- Notas de crédito (relación 01), sustitución (04) y cancelación con motivo SAT 01–04.
- Caja: cobros en efectivo, tarjeta y transferencia, anticipos/saldo a favor y recibo imprimible.
- Cuentas por cobrar por aseguradora, flotilla y particulares con antigüedad 0–30/31–60/61–90/+90,
  contrarrecibo y fecha de pago comprometida.
- Utilidad real por orden (facturado − refacciones del XML − materiales − horas reales × costo hora-hombre
  − subcontratos) con aviso si el margen queda bajo el mínimo; calculadora de costo de hora-hombre.
- Tablero de KPIs con su definición visible: ciclo de reparación, días por etapa y cuello de botella, eficiencia
  y productividad por técnico, retorno, fecha promesa, ticket, margen, aprobación, NPS y rotación;
  filtros por tipo, aseguradora y asesor, tendencia de 6 meses, exportación a Excel y resumen el día 1.

**Configurar el PAC (Facturama):** dar de alta la cuenta API (multiemisor), subir el CSD del taller en su portal
y poner en `.env`: `CFDI_DRIVER=facturama`, `CFDI_AMBIENTE=pruebas` (después `produccion`), `CFDI_USUARIO`,
`CFDI_PASSWORD` y los datos del emisor (`CFDI_EMISOR_RFC`, `CFDI_EMISOR_NOMBRE`, `CFDI_EMISOR_REGIMEN`,
`CFDI_LUGAR_EXPEDICION`). Con `CFDI_DRIVER=simulado` se factura para capacitación sin validez fiscal.
Las claves SAT por tipo de partida (`config/tarzo.php → cfdi.claves`) deben confirmarse con el contador.

**Módulo 5 · Comunicación y CRM (listo):**

- Avisos automáticos al cliente por WhatsApp Business (API oficial de Meta) o correo, según su preferencia:
  vehículo recibido, presupuesto listo (con enlace de autorización), avance de etapa, listo para entrega y entregado.
- Bitácora de cada mensaje con su estado (enviado, entregado, leído, fallido u omitido y por qué) y reintento.
- Portal de seguimiento para el cliente (sin cuenta): etapa actual, historial, fotos autorizadas y garantías.
- Bandeja de conversaciones: los mensajes del cliente llegan al panel y avisan al asesor; respuesta dentro de 24 h.
- Garantías automáticas al entregar (mecánica, pintura, refacciones, plazos en Parámetros), aviso 15 días antes
  de vencer y reclamo que abre una orden de garantía.
- Recordatorios de mantenimiento por kilometraje estimado o tiempo y de verificación vehicular por terminación de placa.
- Encuesta NPS 24 h después de la entrega; un detractor genera aviso de seguimiento; promotores ven el enlace
  de reseña en Google. Indicador por asesor y aseguradora.
- Consentimiento: los recordatorios y encuestas respetan el aviso de privacidad y cada correo trae enlace de baja.

**Configurar WhatsApp:** crear la app en Meta for Developers, poner `WHATSAPP_DRIVER=cloud`, `WHATSAPP_TOKEN`,
`WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_APP_SECRET` y `WHATSAPP_VERIFY_TOKEN` en `.env`, registrar el webhook
`https://<dominio>/webhooks/whatsapp` y aprobar las plantillas con los nombres de *Postventa → Plantillas de mensajes*.
Mientras no esté configurado, todo sale por correo (`WHATSAPP_DRIVER=log` sirve para probar sin enviar).

**Módulo 4 · Almacén y refacciones (listo):**

- Inventario con clave, código de barras, ubicación, mínimo y máximo; kárdex por producto con costo promedio
  ponderado; valor del inventario, productos bajo mínimo y sin movimiento en 90 días.
- Entradas, salidas (siempre a una orden o a gasto general), devoluciones y ajustes; conteo físico con lector.
- Alerta a almacén al bajar del mínimo y orden de compra de reposición con cantidad sugerida.
- Piezas por pedir generadas de las refacciones autorizadas; la aseguradora puede surtirlas.
- Órdenes de compra por proveedor con envío por WhatsApp, confirmación, seguimiento y aviso de atraso.
- Recepción total o parcial con foto y XML de la factura: el costo real se toma del CFDI y llega a la partida.
- Cuando una orden tiene todas sus piezas, sale sola de "Espera de refacciones" y se avisa al asesor y al jefe.

**Módulo 3 · Operación y piso (listo):**

- Tablero Kanban por etapas con arrastre, filtros, semáforo de días, límite de autos y refresco automático;
  modo TV para una pantalla del taller.
- Reglas por etapa: cotización autorizada para entrar a reparación, control de calidad antes de "Listo",
  checklist firmado para entregar, evidencia obligatoria en órdenes de seguro; excepción con comentario.
- Tareas generadas de las partidas autorizadas; asignación de técnico, bahía y fecha; carga de trabajo
  por técnico y agenda de la cabina de pintura.
- Pantalla del técnico para celular: iniciar, pausar con motivo, terminar y subir fotos; horas reales vs.
  estándar y eficiencia.
- Evidencias con marca de agua (folio, fecha, momento), visibles al cliente a elección, álbum PDF.
- Aviso por correo al cliente al entrar a etapas marcadas (pintura, listo, entregado).

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
