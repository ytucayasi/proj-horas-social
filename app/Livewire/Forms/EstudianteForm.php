<?php

namespace App\Livewire\Forms;

use App\Models\Estudiante;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class EstudianteForm extends Form
{
    public ?Estudiante $estudiante = null;
    public ?int $id = null;
    public ?string $codigo = '';
    public ?string $dni = '';
    public ?float $horas_base = 0.0;
    public ?int $periodo_id = null;
    public ?int $escuela_id = null;
    public ?string $estado = '';
    public ?bool $activate_horas = false;

    public function rules()
    {
        $rulesHoras = $this->activate_horas ? 'required|numeric|min:1' : 'nullable|numeric';
        return [
            'codigo' => [
                'required',
                Rule::unique('estudiantes')->ignore($this->estudiante),
                'min:9',
                'max:9'
            ],
            'dni' => [
                'required',
                Rule::unique('estudiantes')->ignore($this->estudiante),
                'min:8',
                'max:8'
            ],
            'horas_base' => $rulesHoras,
            'periodo_id' => "required|integer|exists:periodos,id",
            'escuela_id' => "required|integer|exists:escuelas,id",
        ];
    }
    public function store()
    {
        Estudiante::create($this->only(['codigo', 'dni', 'horas_base', 'periodo_id', 'escuela_id']));
    }

    public function update()
    {
        $this->estudiante->update($this->only(['codigo', 'dni', 'horas_base', 'periodo_id', 'escuela_id']));
    }

    public function setEstudiante(Estudiante $estudiante)
    {
        $this->estudiante = $estudiante;
        $this->id = $estudiante->id;
        $this->dni = $estudiante->dni;
        $this->codigo = $estudiante->codigo;
        $this->horas_base = $estudiante->horas_base;
        $this->periodo_id = $estudiante->periodo_id;
        $this->escuela_id = $estudiante->escuela_id;
        $this->estado = $estudiante->estado;
    }

    public function delete()
    {
        $this->estudiante->delete();
    }
}
