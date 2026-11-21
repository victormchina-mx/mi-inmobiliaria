<?php

use App\Http\Controllers\AprobacionPresupuestoController;
use App\Http\Controllers\CotizacionPdfController;
use App\Http\Controllers\DocumentoIngresoController;
use App\Http\Controllers\FirmaClienteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

// Firma del inventario por el cliente: enlace firmado que caduca, sin cuenta.
Route::middleware(['signed:relative', 'throttle:20,1'])->group(function () {
    Route::get('/firma/{checkin}', [FirmaClienteController::class, 'mostrar'])->name('firma.mostrar');
    Route::post('/firma/{checkin}', [FirmaClienteController::class, 'guardar'])->name('firma.guardar');

    // Autorización del presupuesto por el cliente o el ajustador.
    Route::get('/presupuesto/{cotizacion}/{rol}', [AprobacionPresupuestoController::class, 'mostrar'])->name('presupuesto.mostrar');
    Route::post('/presupuesto/{cotizacion}/{rol}', [AprobacionPresupuestoController::class, 'guardar'])->name('presupuesto.guardar');
});
Route::get('/firma-recibida', [FirmaClienteController::class, 'gracias'])->name('firma.gracias');

Route::middleware('auth')->group(function () {
    Route::get('/panel/ordenes/{orden}/ingreso.pdf', DocumentoIngresoController::class)->name('ordenes.pdf-ingreso');
    Route::get('/panel/cotizaciones/{cotizacion}/pdf', CotizacionPdfController::class)->name('cotizaciones.pdf');
});

// Aviso de privacidad público (enlazado desde la firma y los correos).
Route::view('/aviso-de-privacidad', 'legal.aviso-privacidad-pagina')->name('aviso-privacidad');
