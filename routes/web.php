<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/* Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard'); */

/* Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile'); */

Volt::route('/', 'inicio')->name('inicio');
Volt::route('/dashboard', 'dashboard')->middleware(['auth'])->name('dashboard');
Volt::route('info', 'info')->middleware(['auth'])->name('info');

/* Admin */
Volt::route('usuarios', 'admin.base.user')->middleware(['auth', 'permission:mostrar usuarios'])->name('usuarios');
Volt::route('roles', 'admin.base.role')->middleware(['auth', 'permission:mostrar roles'])->name('roles');
Volt::route('permisos', 'admin.base.permission')->middleware(['auth', 'permission:mostrar permisos'])->name('permisos');

/* General */
Volt::route('/estudiantes', 'admin.estudiante')->middleware(['auth', 'permission:mostrar estudiantes'])->name('estudiantes');
Volt::route('/actividades', 'admin.actividad')->middleware(['auth', 'permission:mostrar actividades'])->name('actividades');
Volt::route('/escuelas', 'admin.escuela')->middleware(['auth', 'permission:mostrar escuelas'])->name('escuelas');
Volt::route('/periodos', 'admin.periodo')->middleware(['auth', 'permission:mostrar periodos'])->name('periodos');

require __DIR__ . '/auth.php';