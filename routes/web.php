<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConcursanteController;
use App\Http\Controllers\EspectadorController;
use App\Http\Controllers\AdminController;

Route::get('/', function () {
    $historial = \App\Models\HistorialEvento::latest()->take(6)->get();
    return view('inicio', compact('historial'));
})->name('inicio');

// ==========================================
// 1. ROL CONCURSANTE (Optimizado para Wi-Fi compartido en Auditorio)
// ==========================================
Route::get('/jugar', [ConcursanteController::class, 'index'])->name('concursante.index');
Route::post('/jugar/salir', [ConcursanteController::class, 'salirEquipo'])->name('concursante.salir.form');
Route::post('/api/salir-equipo', [ConcursanteController::class, 'salirEquipo'])->name('concursante.salir');

// Soporta múltiples equipos registrándose y respondiendo desde la misma red Wi-Fi
Route::post('/api/registrar-equipo', [ConcursanteController::class, 'registrarEquipo'])
    ->middleware('throttle:registro')
    ->name('concursante.registrar');

Route::post('/api/recuperar-sesion', [ConcursanteController::class, 'recuperarEquipo'])
    ->middleware('throttle:pin')
    ->name('concursante.recuperar');

Route::get('/api/estado-juego', [ConcursanteController::class, 'obtenerEstadoYPregunta'])
    ->middleware('throttle:juego-estado')
    ->name('concursante.estado');

// Límites por equipo/sesión (ver AppServiceProvider): todos comparten la IP del Wi-Fi
Route::post('/api/enviar-respuesta', [ConcursanteController::class, 'enviarRespuesta'])
    ->middleware('throttle:juego-respuesta')
    ->name('concursante.responder');

// ==========================================
// 2. ROL ESPECTADOR (Pantalla Gigante Carrera 2D)
// ==========================================
Route::get('/espectador', [EspectadorController::class, 'index'])->name('espectador.index');
Route::get('/api/carrera-en-vivo', [EspectadorController::class, 'datosCarreraEnVivo'])
    ->withoutMiddleware('web')
    ->name('espectador.datos');

// ==========================================
// 3. ROL ADMINISTRADOR (Sin bloqueo de tiempo real)
// ==========================================
Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
Route::get('/admin/login', fn () => redirect('/admin'));
Route::post('/admin/login', [AdminController::class, 'login'])->middleware('throttle:admin-login')->name('admin.login');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

Route::middleware('admin')->group(function () {
    Route::post('/admin/configuracion', [AdminController::class, 'actualizarConfiguracion'])->name('admin.config');
    Route::post('/admin/nivel/{id}', [AdminController::class, 'actualizarNivel'])->name('admin.nivel');
    Route::post('/admin/modulo', [AdminController::class, 'crearModulo'])->name('admin.modulo');
    Route::post('/admin/pregunta', [AdminController::class, 'crearPregunta'])->name('admin.pregunta');
    Route::post('/admin/preguntas/importar', [AdminController::class, 'importarPreguntas'])->name('admin.pregunta.importar');
    Route::post('/admin/desempate', [AdminController::class, 'activarDesempate'])->name('admin.desempate');
    Route::post('/admin/reiniciar', [AdminController::class, 'reiniciarJuego'])->name('admin.reiniciar');

    // Rutas del Simulador de Carrera (solo en entorno local)
    if (app()->isLocal()) {
        Route::post('/admin/simular-equipos', [AdminController::class, 'generarEquiposPrueba'])->name('admin.simular.equipos');
        Route::post('/admin/simular-avance', [AdminController::class, 'simularAvanceCarrera'])->name('admin.simular.avance');
    }

    // Rutas para Edición Manual de Contenido en Tiempo Real
    Route::post('/admin/pregunta/{id}/editar', [AdminController::class, 'actualizarPregunta'])->name('admin.pregunta.editar');
    Route::post('/admin/pregunta/{id}/eliminar', [AdminController::class, 'eliminarPregunta'])->name('admin.pregunta.eliminar');
    Route::post('/admin/modulo/{id}/eliminar', [AdminController::class, 'eliminarModulo'])->name('admin.modulo.eliminar');
    Route::post('/admin/equipo/{id}/eliminar', [AdminController::class, 'eliminarEquipo'])->name('admin.equipo.eliminar');
    Route::post('/admin/historial/guardar', [AdminController::class, 'guardarHistorialEvento'])->name('admin.historial.guardar');
    Route::post('/admin/historial/{id}/eliminar', [AdminController::class, 'eliminarHistorialEvento'])->name('admin.historial.eliminar');
});