<?php

use App\Livewire\Forms\EstudianteForm;
use App\Models\Escuela;
use App\Models\Estudiante;
use App\Models\Periodo;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;

new 
#[Layout('layouts.app')]
class extends Component {
    public EstudianteForm $estudianteForm;
    public string $modelName = 'Estudiante';
    public bool $modal = false;
    public bool $modalDelete = false;
    public bool $modalActividades = false;
    public $periodos = [];
    public $escuelas = [];
    public $actividades = [];
    public $estudiante = [];

    public function mount() {
        $this->periodos = Periodo::all()->map(function ($periodo) {
            return [
                'id' => $periodo->id,
                'name' => $periodo->nombre,
            ];
        });
        
        $user = Auth::user();
        
        if ($user->hasRole(['Super Admin', 'Admin'])) {
            // Si es Super Admin o Admin, puede ver todas las escuelas
            $this->escuelas = Escuela::all()->map(function ($escuela) {
                return [
                    'id' => $escuela->id,
                    'name' => $escuela->nombre,
                ];
            });
        } else {
            // Para otros roles, obtener las escuelas asociadas a los roles del usuario
            $userRoles = $user->roles()->pluck('name');
            $this->escuelas = Escuela::whereHas('roles', function($query) use ($userRoles) {
                $query->whereIn('name', $userRoles);
            })->get()->map(function ($escuela) {
                return [
                    'id' => $escuela->id,
                    'name' => $escuela->nombre,
                ];
            });
        }
    }
    #[On('createEstudiante')]
    public function open()
    {
        $this->resetForm();
        $this->modal = true;
    }

    #[On('editEstudiante')]
    public function setEstudiante(Estudiante $estudiante)
    {
        $this->resetForm();
        $this->estudianteForm->setEstudiante($estudiante);
        $this->modal = true;
    }

    #[On('mostrarActividades')]
    public function mostrarActividades(Estudiante $estudiante) {
        $this->estudianteForm->setEstudiante($estudiante);
        $this->estudiante = $this->estudianteForm->estudiante;
        $this->actividades = $this->estudianteForm->estudiante->estudianteActividades()->with('actividad')->get();
        $this->modalActividades = true;
    } 

    #[On('deleteEstudiante')]
    public function removeEstudiante(Estudiante $estudiante)
    {
        $this->estudianteForm->setEstudiante($estudiante);
        $this->modalDelete = true;
    }

    public function delete()
    {
        $this->estudianteForm->delete();
        $this->dispatch('pg:eventRefresh-EstudianteTable');
        $this->modalDelete = false;
    }

    public function clear()
    {
        $this->resetForm();
    }

    public function save()
    {
        $this->estudianteForm->id
            ? $this->update()
            : $this->store();
    }

    /* Validación de los campos */
    public function validateForm()
    {
        $this->estudianteForm->validate();
    }

    /* Regsitrar Usuario General */
    public function store()
    {
        $this->validateForm();
        $this->estudianteForm->store();
        $this->resetForm();
        $this->dispatch('pg:eventRefresh-EstudianteTable');
        $this->modal = false;
    }

    public function update()
    {
        $this->validateForm();
        $this->estudianteForm->update();
        $this->dispatch('pg:eventRefresh-EstudianteTable');
        $this->modal = false;
    }

    public function resetForm()
    {
        $this->estudianteForm->resetValidation();
        $this->estudianteForm->reset();
    }
    public function updated($property, $value)
    {
        if ($property === 'estudianteForm.horas_base' && $value < 0) {
            $this->estudianteForm->horas_base = 0;
        }
    }
    public function check()
    {
        if (!Auth::user()->can('mostrar estudiantes')) {
            return redirect()->route('dashboard');
        }
    }
}; ?>
<div wire:poll="check">
    <livewire:estudiante-table />
    <x-modal wire:model="modalDelete" width="sm">
        <x-card>
            <div class="flex flex-col justify-center items-center gap-4">
                <div class="bg-warning-400 dark:border-4 dark rounded-full p-4">
                    <x-phosphor.icons::regular.warning class="text-white w-16 h-16" />
                </div>
                <span class="text-center font-semibold text-xl">¿Desea eliminar al estudiante?</span>
                <span class="text-center">Recuerda que se eliminarán los registros del estudiante</span>
                <div class="flex gap-2">
                    <x-button flat label="Cancelar" x-on:click="close" />
                    <x-button flat negative label="Eliminar" wire:click="delete" />
                </div>
            </div>
        </x-card>
    </x-modal>
    <x-modal wire:model="modalActividades" width="6xl">
        <x-card title="Actividades del Estudiante">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Motivo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Inicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha Fin</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Horas</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($actividades as $estudianteActividad)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $estudianteActividad->actividad->motivo }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $estudianteActividad->actividad->fecha_inicio ? \Carbon\Carbon::parse($estudianteActividad->actividad->fecha_inicio)->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $estudianteActividad->actividad->fecha_fin ? \Carbon\Carbon::parse($estudianteActividad->actividad->fecha_fin)->format('d/m/Y') : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $estudianteActividad->horas }} horas
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No hay actividades registradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-slot name="footer">
                <div class="flex justify-end gap-x-2">
                    <x-button flat label="Cerrar" x-on:click="close" />
                </div>
            </x-slot>
        </x-card>
    </x-modal>
    <x-modal-card title="{{($estudianteForm->id ? 'Editar' : 'Registrar') . ' ' . $modelName}}" wire:model="modal"
        width="sm">
        <div class="grid grid-cols-1 gap-3">

            <!-- DNI -->
            <x-maskable label="DNI" placeholder="Ingresar" wire:model="estudianteForm.dni" mask="########" />

            <!-- Código -->
            <x-maskable label="Código" placeholder="Ingresar" wire:model="estudianteForm.codigo" mask="#########" />

            <!-- Activar Horas -->
            @if (!$estudianteForm->id)
                <div class="flex justify-end">
                    <x-checkbox id="left-label" left-label="¿Añadir horas iniciales?"
                        wire:model.live="estudianteForm.activate_horas" value="left-label" />
                </div>
                <!-- Horas -->
                <x-input :disabled="!$estudianteForm->activate_horas" label="Horas" placeholder="Ingresar"
                    wire:model="estudianteForm.horas_base" suffix="horas" />
            @else
                <!-- Horas -->
                <x-input label="Horas" placeholder="Ingresar" wire:model="estudianteForm.horas_base" suffix="horas" />
            @endif
            <!-- Escuela Académica: ID -->
            <x-select label="Escuela Académica" placeholder="Seleccionar" :options="$this->escuelas"
                option-label="name" option-value="id" wire:model="estudianteForm.escuela_id" />

            <!-- Periodo: ID -->
            <x-select label="Periodo" placeholder="Seleccionar" :options="$this->periodos" option-label="name"
                option-value="id" wire:model="estudianteForm.periodo_id" />
        </div>
        <x-slot name="footer" class="flex justify-between items-center gap-x-4">

            <!-- Botón de Eliminar -->
            @if (!$estudianteForm->id)
                <x-mini-button flat negative rounded icon="trash" wire:click="clear" />
            @endif
            <div>
                
            </div>
            <div class="flex gap-x-2">

                <!-- Botón de Cancelar -->
                <x-button flat label="Cancelar" x-on:click="close" />

                <!-- Botón de Guardar -->
                <x-button flat positive label="Guardar" wire:click="save" />
            </div>
        </x-slot>
    </x-modal-card>
    
</div>