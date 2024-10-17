<?php

namespace App\Livewire\Forms;

use App\Models\Actividad;
use App\Models\Estudiante;
use App\Models\EstudianteActividad;
use Illuminate\Support\Facades\Auth;
use Livewire\Form;

class ActividadForm extends Form
{
    public ?Actividad $actividad = null;
    public ?int $id = null;
    public ?string $descripcion = '';
    public ?string $motivo = '';
    public ?string $fecha_inicio = '';
    public ?string $fecha_fin = '';
    public ?bool $activate_horas = false;
    public ?string $horas = null;
    public ?int $publicado = 2;
    public $students = [];
    public ?int $estudiante_id = null;
    public ?int $action_id = null;
    public ?float $horas_estudiante = null;

    /* Estudiante Edit */
    public ?int $estudiante_update_id = null;
    public ?string $estudiante_codigo = '';
    public ?float $horas_actuales = 0.0;
    public ?float $horas_actualizados = 0.0;
    public function rules()
    {
        $rulesHoras = $this->activate_horas ? 'required' : 'nullable';
        return [
            'descripcion' => 'nullable|string',
            'motivo' => 'required',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date',
            'publicado' => 'required',
            'horas' => $rulesHoras,
        ];
    }
    public function store()
    {
        $data = $this->only(['descripcion', 'motivo', 'fecha_inicio', 'fecha_fin', 'horas', 'publicado']);
        $data['user_id'] = Auth::id();

        Actividad::create($data);
    }

    public function update()
    {
        $data = $this->only(['descripcion', 'motivo', 'fecha_inicio', 'fecha_fin', 'horas', 'publicado']);
        $data['user_id'] = Auth::id();

        $this->actividad->update($data);
    }

    public function setActividad(Actividad $actividad)
    {
        $this->actividad = $actividad;
        $this->id = $actividad->id;
        $this->descripcion = $actividad->descripcion;
        $this->motivo = $actividad->motivo;
        $this->fecha_inicio = $actividad->fecha_inicio;
        $this->fecha_fin = $actividad->fecha_fin;
        $this->horas = $actividad->horas;
        $this->publicado = $actividad->publicado;
    }

    public function setEstudiante(Estudiante $estudiante)
    {
        $this->estudiante_update_id = $estudiante->id;
        $this->estudiante_codigo = $estudiante->codigo;
        $estudianteActividad = EstudianteActividad::where('estudiante_id', $this->estudiante_update_id)->where('actividad_id', $this->id)->first();
        $this->horas_actuales = $estudianteActividad->horas;
        $this->horas_actualizados = $estudianteActividad->horas;
    }

    public function changeStudents(?int $action)
    {
        if ($action == 1) {
            $this->students = Estudiante::all()->map(function ($student) {
                return [
                    'name' => $student->codigo,
                    'id' => $student->id
                ];
            });
        } else if ($action == 2) {

        }
    }

    public function addStudent()
    {
        // Validar los datos
        $this->validate([
            'estudiante_id' => 'required|exists:estudiantes,id',
            'horas_estudiante' => 'required|numeric|min:1',
        ]);

        // Buscar si ya existe el registro del estudiante en la actividad
        $estudianteActividad = EstudianteActividad::where('estudiante_id', $this->estudiante_id)
            ->where('actividad_id', $this->id)
            ->first();

        if ($estudianteActividad) {
            // Si ya existe, sumar las horas
            $estudianteActividad->horas += $this->horas_estudiante;
            $estudianteActividad->save();
        } else {
            // Si no existe, crear un nuevo registro
            EstudianteActividad::create([
                'estudiante_id' => $this->estudiante_id,
                'actividad_id' => $this->id,
                'horas' => $this->horas_estudiante
            ]);
        }

        // Ahora actualizar el campo horas_base del estudiante
        $estudiante = Estudiante::find($this->estudiante_id);
        $estudiante->horas_base += $this->horas_estudiante;
        $estudiante->save();
    }
    public function updateStudent()
    {
        // Validar los datos
        $this->validate([
            'estudiante_update_id' => 'required|exists:estudiantes,id',
            'horas_actualizados' => 'required|numeric|min:1',
        ]);

        // Buscar el registro en la tabla EstudianteActividad
        $estudianteActividad = EstudianteActividad::where('estudiante_id', $this->estudiante_update_id)
            ->where('actividad_id', $this->id)
            ->first();

        if ($estudianteActividad) {
            // Obtener las horas actuales antes de actualizar
            $horasAnteriores = $estudianteActividad->horas;

            // Reemplazar el valor de horas en la tabla EstudianteActividad
            $estudianteActividad->horas = $this->horas_actualizados;
            $estudianteActividad->save();

            // Buscar el estudiante en la tabla Estudiante
            $estudiante = Estudiante::find($this->estudiante_update_id);

            if ($estudiante) {
                // Actualizar el campo horas_base en la tabla Estudiante ajustando la diferencia
                $estudiante->horas_base += ($this->horas_actualizados - $horasAnteriores);
                $estudiante->save();
            }

            // Actualizar `horas_actuales` para que refleje el nuevo valor en el formulario
            $this->horas_actuales = $this->horas_actualizados;
        }
    }
    public function deleteStudent(Estudiante $estudiante)
    {
        // Buscar el registro en la tabla EstudianteActividad
        $estudianteActividad = EstudianteActividad::where('estudiante_id', $estudiante->id)
            ->where('actividad_id', $this->id)
            ->first();

        if ($estudianteActividad) {
            // Obtener las horas actuales antes de eliminar
            $horasActuales = $estudianteActividad->horas;

            // Restar las horas de horas_base en la tabla Estudiante
            $estudiante->horas_base -= $horasActuales;
            $estudiante->save();

            // Eliminar el registro de EstudianteActividad
            $estudianteActividad->delete();
        }
    }
    public function delete()
    {
        // Obtener todos los registros en EstudianteActividad relacionados con la actividad
        $estudiantesActividades = EstudianteActividad::where('actividad_id', $this->actividad->id)->get();

        foreach ($estudiantesActividades as $estudianteActividad) {
            // Buscar el estudiante correspondiente
            $estudiante = Estudiante::find($estudianteActividad->estudiante_id);

            if ($estudiante) {
                // Restar las horas de horas_base en la tabla Estudiante
                $estudiante->horas_base -= $estudianteActividad->horas;
                $estudiante->save();
            }

            // Eliminar el registro de EstudianteActividad
            $estudianteActividad->delete();
        }

        // Finalmente, eliminar la actividad
        $this->actividad->delete();
    }
}
