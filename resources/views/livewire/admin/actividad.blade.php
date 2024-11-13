<?php

use App\Imports\EstudianteActividadImport;
use App\Livewire\Forms\ActividadForm;
use App\Livewire\Forms\EstudianteForm;
use App\Models\Actividad;
use App\Models\Estudiante;
use App\Models\Log;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

new
#[Layout('layouts.app')]
class extends Component {
    use WithFileUploads;

    public ActividadForm $actividadForm;
    public string $modelName = 'Actividad';
    public bool $modal = false;
    public bool $modalDelete = false;
    public bool $modalStudents = false;
    public bool $modalExcel = false;
    public $disabledDates = [];
    public $excelFile;

    protected function createLog(string $action, $model, ?array $oldValues = null, ?array $newValues = null)
    {
        Log::create([
            'action' => $action,
            'model_type' => $model,
            'model_id' => 1,
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip()
        ]);
    }

    #[On('createActividad')]
    public function open()
    {
        $this->resetForm();
        $this->modal = true;
    }

    #[On('openExcel')]
    public function openExcel()
    {
        $this->resetValidation();
        $this->modalExcel = true;
    }
    public function uploadExcel()
    {
        $this->validate([
            'excelFile' => 'required|mimes:xlsx,xls|max:5120', // 5MB Max
        ]);
    
        try {
            $import = new EstudianteActividadImport($this->actividadForm->id);
            
            // Intenta leer el contenido del archivo antes de importar
            $data = Excel::toArray($import, $this->excelFile);
            
            // Verifica si hay datos en el archivo
            if (empty($data) || empty($data[0])) {
                throw new \Exception("El archivo Excel está vacío o no tiene el formato correcto.");
            }
    
            // Realiza la importación
            Excel::import($import, $this->excelFile);
    
            $this->modalExcel = false;
            $this->excelFile = null;
            $this->dispatch('pg:eventRefresh-EstudianteActividadTable');
            $this->dispatch('notify', ['message' => 'Archivo importado con éxito', 'type' => 'success']);
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = collect($failures)->map(function ($failure) {
                return "Fila {$failure->row()}: {$failure->errors()[0]}";
            })->join(', ');
            $this->dispatch('notify', ['message' => 'Error en la validación: ' . $errors, 'type' => 'error']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['message' => 'Error al importar: ' . $e->getMessage(), 'type' => 'error']);
        }
    }
    #[On('editActividad')]
    public function setActividad(Actividad $actividad)
    {
        $this->resetForm();
        $this->actividadForm->setActividad($actividad);
        $this->actividadForm->activate_horas = $this->actividadForm->horas ? true : false;
        $this->modal = true;
    }

    #[On('deleteActividad')]
    public function removeActividad(Actividad $actividad)
    {
        $this->actividadForm->setActividad($actividad);
        $this->modalDelete = true;
    }

    /* Esto falta */
    #[On('addStudents')]
    public function addStudents(Actividad $actividad)
    {
        $this->resetStudent();
        $this->loadStudents();
        $this->actividadForm->setActividad($actividad);
        $this->modalStudents = true;
    }
    public function loadStudents()
    {
        $user = Auth::user();
        $roles = $user->roles;

        // Si el usuario es super-admin, mostrar todos los estudiantes
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            $students = Estudiante::all();
        } else {
            // Obtener los IDs de las escuelas asociadas a los roles del usuario
            $escuelaIds = $roles->pluck('escuela_id')->filter()->unique()->toArray();

            // Si no hay escuelas asociadas, devolver una colección vacía
            if (empty($escuelaIds)) {
                $students = collect();
            } else {
                // Filtrar estudiantes por las escuelas del usuario
                $students = Estudiante::whereHas('escuela', function ($query) use ($escuelaIds) {
                    $query->whereIn('id', $escuelaIds);
                })->get();
            }
        }

        $this->actividadForm->students = $students->map(function ($student) {
            return [
                'name' => $student->codigo,
                'id' => $student->id
            ];
        });
    }
    #[On('activeActividad')]
    public function activeActividad(Actividad $actividad)
    {
        $this->actividadForm->setActividad($actividad);
        if ($this->actividadForm->publicado == 1) {
            $this->actividadForm->publicado = 2;
            $this->actividadForm->update();
        } else if ($this->actividadForm->publicado == 2) {
            $this->actividadForm->publicado = 1;
            $this->actividadForm->update();
        }
        $this->dispatch('pg:eventRefresh-ActividadTable');
    }

    public function delete()
    {
        $this->actividadForm->delete();
        $this->createLog(
            'DELETE',
            model: "ACTIVIDAD"
        );
        $this->dispatch('pg:eventRefresh-ActividadTable');
        $this->modalDelete = false;
    }

    public function clear()
    {
        $this->resetForm();
    }

    public function save()
    {
        $this->actividadForm->id
            ? $this->update()
            : $this->store();
    }

    /* Validación de los campos */
    public function validateForm()
    {
        $this->actividadForm->validate();
    }

    /* Regsitrar Usuario General */
    public function store()
    {
        $this->validateForm();
        $this->actividadForm->store();
        $this->createLog(
            'CREATE',
            model: "ACTIVIDAD"
        );
        $this->resetForm();
        $this->dispatch('pg:eventRefresh-ActividadTable');
        $this->modal = false;
    }

    public function update()
    {
        $this->validateForm();
        $this->actividadForm->update();
        $this->createLog(
            'UPDATE',
            model: "ACTIVIDAD"
        );
        $this->dispatch('pg:eventRefresh-ActividadTable');
        $this->modal = false;
    }

    public function resetForm()
    {
        $this->actividadForm->resetValidation();
        $this->actividadForm->reset();
    }

    public function updated($property, $value)
    {
        if ($property === 'actividadForm.fecha_inicio') {
            if (!$value) {
                $this->actividadForm->fecha_fin = null;
            } else {
                // Limpiar fecha_fin cuando se actualiza fecha_inicio
                $this->actividadForm->fecha_fin = null;
            }
        } else if ($property === 'actividadForm.activate_horas') {
            $this->actividadForm->horas = null;
        } else if ($property === 'actividadForm.action_id') {
            $this->actividadForm->changeStudents($value);
        }
    }
    #[On('editEstudianteActividad')]
    public function editEstudianteActividad(Estudiante $estudiante)
    {
        $this->actividadForm->setEstudiante($estudiante);
    }
    #[On('deleteEstudianteActividad')]
    public function deleteEstudianteActividad(Estudiante $estudiante)
    {
        $this->actividadForm->deleteStudent($estudiante);
        $this->dispatch('pg:eventRefresh-EstudianteActividadTable');
    }
    public function saveStudent()
    {
        !$this->actividadForm->estudiante_update_id ?
            $this->actividadForm->addStudent() : $this->actividadForm->updateStudent();
        $this->dispatch('pg:eventRefresh-EstudianteActividadTable');
    }

    public function resetStudent()
    {
        $this->actividadForm->estudiante_update_id = null;
        $this->actividadForm->horas_actuales = null;
        $this->actividadForm->horas_actualizados = null;
        $this->actividadForm->estudiante_id = null;
        $this->actividadForm->horas_estudiante = null;
    }
    public function check()
    {
        if (!Auth::user()->can('mostrar actividades')) {
            return redirect()->route('dashboard');
        }
    }
}; ?>
<div wire:poll="check">
    <livewire:actividad-table />
    <x-modal wire:model="modalDelete" width="sm">
        <x-card>
            <div class="flex flex-col justify-center items-center gap-4">
                <div class="bg-warning-400 dark:border-4 dark rounded-full p-4">
                    <x-phosphor.icons::regular.warning class="text-white w-16 h-16" />
                </div>
                <span class="text-center font-semibold text-xl">¿Desea eliminar la actividad?</span>
                <span class="text-center">Recuerda que se eliminarán los registros del estudiante</span>
                <div class="flex gap-2">
                    <x-button flat label="Cancelar" x-on:click="close" />
                    <x-button flat negative label="Eliminar" wire:click="delete" />
                </div>
            </div>
        </x-card>
    </x-modal>
    <x-modal-card title="{{($actividadForm->id ? 'Editar' : 'Registrar') . ' ' . $modelName}}" wire:model="modal"
        width="sm">
        <div class="grid grid-cols-1 gap-4">

            <!-- Motivo -->
            <x-input label="Motivo" placeholder="Ingresar" wire:model="actividadForm.motivo" />

            <!-- Fecha Inicio -->
            <x-datetime-picker only label="Fecha Inicio" placeholder="Seleccionar"
                wire:model.live="actividadForm.fecha_inicio" format="Y-m-d" />

            <!-- Fecha Fin -->
            <x-datetime-picker :disabled="!$actividadForm->fecha_inicio" only format="Y-m-d" label="Fecha Fin"
                placeholder="Seleccionar" wire:model="actividadForm.fecha_fin"
                min="{{ $actividadForm->fecha_inicio ? \Carbon\Carbon::parse($actividadForm->fecha_inicio)->format('Y-m-d H:i:s') : '' }}" />
            <!-- Activar Hora -->
            <div class="flex justify-end items-center">
                <x-checkbox id="left-label" left-label="¿Agregar un horario?"
                    wire:model.live="actividadForm.activate_horas" value="left-label" />
            </div>
            @if ($actividadForm->activate_horas || ($actividadForm->horas && $actividadForm->id))
                <!-- Hora -->
                <x-time-picker id="interval" wire:model.live="actividadForm.horas" label="Horario" placeholder="Ingresar"
                    without-seconds />
            @endif
            <x-select label="¿Publicar?" placeholder="Seleccionar" :options="[['name' => 'No Publicar', 'id' => 1], ['name' => 'Publicar', 'id' => 2]]" option-label="name" option-value="id"
                wire:model="actividadForm.publicado" />
            <!-- Descripcion -->
            <x-textarea label="Descripción" placeholder="..." wire:model="actividadForm.descripcion" />
        </div>
        <x-slot name="footer" class="flex justify-between items-center gap-x-4">

            <!-- Botón de Eliminar -->
            @if (!$actividadForm->id)
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
    <x-modal-card title="Estudiantes" wire:model="modalStudents" width="5xl" persistent>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="flex flex-col gap-4">
                <h1>Agregar Estudiante</h1>
                <!-- Estudiante -->
                <x-select label="Estudiante" placeholder="Seleccionar" :options="$actividadForm->students"
                    option-label="name" option-value="id" wire:model="actividadForm.estudiante_id" />

                <!-- Horas -->
                <x-input type="number" step="0.1" label="Horas" placeholder="Ingresar" wire:model="actividadForm.horas_estudiante" />
                <!-- Actions -->
                <div class="flex gap-2">
                    <x-button icon="plus" class="w-full" flat positive label="Agregar" wire:click="saveStudent" />
                </div>

                @if ($actividadForm->estudiante_update_id)
                    <div class="dark:bg-secondary-900 bg-slate-100 p-4 rounded-md flex gap-4 flex-col">
                        <!-- Editar estudiante -->
                        <h1>Editar Estudiante</h1>
                        <x-input disabled label="Estudiante" placeholder="Ingresar"
                            wire:model="actividadForm.estudiante_codigo" />
                        <x-input disabled label="Horas Actuales" placeholder="Ingresar"
                            wire:model="actividadForm.horas_actuales" />
                        <x-input label="Actualización de Horas" placeholder="Ingresar"
                            wire:model="actividadForm.horas_actualizados" />
                        <div class="flex gap-2">
                            <x-button icon="pencil" class="w-full" flat warning label="Editar" wire:click="saveStudent" />
                        </div>
                    </div>
                @endif
                <x-button flat icon="no-symbol" negative class="w-full" label="Limpiar" wire:click="resetStudent" />

            </div>
            <div class="col-span-2">
                <livewire:estudiante-actividad-table />
            </div>
        </div>
    </x-modal-card>
    <x-modal-card title="Subir Archivo de Estudiantes" wire:model="modalExcel" max-width="md" persistent>
        <form wire:submit.prevent="uploadExcel">
            <div class="mb-4">
                <h3 class="font-bold mb-2">Instrucciones para el archivo Excel:</h3>
                <ul class="list-disc list-inside mb-4">
                    <li>El archivo debe ser en formato .xlsx o .xls</li>
                    <li>La primera fila debe contener los siguientes encabezados:
                        <ul class="list-circle list-inside ml-4">
                            <li>codigo_estudiante</li>
                            <li>horas</li>
                        </ul>
                    </li>
                    <li>El código del estudiante debe coincidir exactamente con el registrado en el sistema</li>
                    <li>Las horas pueden ser números decimales (use punto como separador)</li>
                </ul>
                <x-input type="file" wire:model="excelFile" label="Seleccionar archivo Excel" accept=".xlsx,.xls" />
                @error('excelFile') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="flex justify-end gap-x-4">
                <x-button flat label="Cancelar" x-on:click="close" />
                <x-button type="submit" primary label="Subir" />
            </div>
        </form>
    </x-modal-card>
</div>