<?php

use App\Models\Estudiante;
use App\Models\EstudianteActividad;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new
#[Layout('layouts.guest2')]
class extends Component {
    public $codigoEstudiante = '';
    public $selectedEstudiante = null;
    public $actividades;
    public $horasTotal = 0;
    public $horasBase = 0;
    public $horasRestantes = 0;

    public function updatedCodigoEstudiante()
    {
        $estudiante = Estudiante::where('codigo', $this->codigoEstudiante)->first();

        if ($estudiante) {
            $this->selectedEstudiante = $estudiante->id;
            $this->horasBase = $estudiante->horas_base;
            $this->actividades = EstudianteActividad::where('estudiante_id', $estudiante->id)->with('actividad')->get();

            $this->horasTotal = $this->actividades->sum('horas');
            $this->horasRestantes = $this->horasBase - $this->horasTotal;
        } else {
            // Restablecer los valores si no se encuentra el estudiante
            $this->reset(['selectedEstudiante', 'horasBase', 'horasTotal', 'horasRestantes', 'actividades']);
        }
    }
}; ?>

<div class="p-6">
    <div class="max-w-lg mx-auto">
        <div class="mb-4">
            <div class="flex items-center mt-1">
                <x-maskable label="Código" placeholder="Ingresar" wire:model.live="codigoEstudiante" mask="#########" />
            </div>
        </div>

        @if ($selectedEstudiante)
            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg mb-4">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Detalles del Estudiante</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Código: {{ $codigoEstudiante }}</p>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Horas Base: {{ $horasBase }} horas</p>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Horas Totales en Actividades: {{ $horasTotal }} horas</p>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Horas Restantes: {{ $horasRestantes }} horas</p>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Actividades Relacionadas</h3>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700">
                    <dl>
                        @foreach($actividades as $actividad)
                            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-300">{{ $actividad->actividad->motivo }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:mt-0 sm:col-span-2">{{ $actividad->horas }} horas</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        @endif

        <!-- Mostrar el código que se está buscando -->
        <div class="mt-6">
            @if ($codigoEstudiante)
                <p class="text-sm text-gray-500 dark:text-gray-400">Buscando código: <span class="font-bold text-gray-900 dark:text-gray-100">{{ $codigoEstudiante }}</span></p>
                <a href="#" wire:click.prevent="$set('codigoEstudiante', '')" class="text-indigo-600 dark:text-indigo-400 hover:underline">Ingresar otro código</a>
            @endif
        </div>
    </div>
</div>