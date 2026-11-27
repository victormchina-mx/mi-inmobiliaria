<?php

use App\Http\Controllers\AlbumEvidenciasController;
use App\Http\Controllers\AprobacionPresupuestoController;
use App\Http\Controllers\ArchivoPrivadoController;
use App\Http\Controllers\CotizacionPdfController;
use App\Http\Controllers\DocumentoIngresoController;
use App\Http\Controllers\EncuestaController;
use App\Http\Controllers\FacturacionArchivosController;
use App\Http\Controllers\FirmaClienteController;
use App\Http\Controllers\PortalClienteController;
use App\Http\Controllers\TableroTvController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

// Firma del inventario por el cliente: enlace firmado que caduca, sin cuenta.
Route::middleware(['signed:relative', 'throttle:20,1'])->group(function () {
    Route::get('/firma/{checkin}', [FirmaClienteController::class, 'mostrar'])->name('firma.mostrar');
    Route::post('/firma/{checkin}', [FirmaClienteController::class, 'guardar'])->name('firma.guardar');

    // Autorización del presupuesto por el cliente o el ajustador.
    Route::get('/presupuesto/{cotizacion}/{rol}', [AprobacionPresupuestoController::class, 'mostrar'])->name('presupuesto.mostrar');
    Route::post('/presupuesto/{cotizacion}/{rol}', [AprobacionPresupuestoController::class, 'guardar'])->name('presupuesto.guardar');

    // Portal de seguimiento, encuesta de satisfacción y baja de recordatorios.
    Route::get('/seguimiento/{orden}', [PortalClienteController::class, 'orden'])->name('portal.orden');
    Route::get('/encuesta/{encuesta}', [EncuestaController::class, 'mostrar'])->name('encuesta.mostrar');
    Route::post('/encuesta/{encuesta}', [EncuestaController::class, 'guardar'])->name('encuesta.guardar');
    Route::get('/baja/{cliente}', [PortalClienteController::class, 'baja'])->name('baja.recordatorios');
});

// Fotos, firmas y documentos privados: enlace firmado sin extensión (ver App\Support\Media).
Route::get('/archivo/{clave}', ArchivoPrivadoController::class)
    ->where('clave', '[A-Za-z0-9_-]+')->middleware(['signed:relative', 'throttle:600,1'])->name('archivo.ver');

// Webhook de WhatsApp (Meta). Sin sesión ni CSRF; se valida la firma.
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verificar']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'recibir'])->middleware('throttle:240,1');
Route::get('/firma-recibida', [FirmaClienteController::class, 'gracias'])->name('firma.gracias');

Route::middleware('auth')->group(function () {
    Route::get('/panel/ordenes/{orden}/ingreso.pdf', DocumentoIngresoController::class)->name('ordenes.pdf-ingreso');
    Route::get('/panel/cotizaciones/{cotizacion}/pdf', CotizacionPdfController::class)->name('cotizaciones.pdf');
    Route::get('/panel/tablero-tv', TableroTvController::class)->name('tablero.tv');
    Route::get('/panel/ordenes/{orden}/evidencias.pdf', AlbumEvidenciasController::class)->name('ordenes.album');
    Route::get('/panel/facturas/{factura}/archivo.{formato}', [FacturacionArchivosController::class, 'factura'])
        ->whereIn('formato', ['xml', 'pdf'])->name('facturas.archivo');
    Route::get('/panel/caja/{pago}/recibo.pdf', [FacturacionArchivosController::class, 'recibo'])->name('caja.recibo');
});

// Aviso de privacidad público (enlazado desde la firma y los correos).
Route::view('/aviso-de-privacidad', 'legal.aviso-privacidad-pagina')->name('aviso-privacidad');
