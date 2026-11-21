<?php

use App\Http\Controllers\DocumentoIngresoController;
use App\Http\Controllers\FirmaClienteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/panel');

// Firma del inventario por el cliente: enlace firmado que caduca, sin cuenta.
Route::middleware(['signed:relative', 'throttle:20,1'])->group(function () {
    Route::get('/firma/{checkin}', [FirmaClienteController::class, 'mostrar'])->name('firma.mostrar');
    Route::post('/firma/{checkin}', [FirmaClienteController::class, 'guardar'])->name('firma.guardar');
});
Route::get('/firma-recibida', [FirmaClienteController::class, 'gracias'])->name('firma.gracias');

Route::middleware('auth')->group(function () {
    Route::get('/panel/ordenes/{orden}/ingreso.pdf', DocumentoIngresoController::class)->name('ordenes.pdf-ingreso');
});

// Aviso de privacidad público (enlazado desde la firma y los correos).
Route::view('/aviso-de-privacidad', 'legal.aviso-privacidad-pagina')->name('aviso-privacidad');
