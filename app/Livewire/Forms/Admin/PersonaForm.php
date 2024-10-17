<?php

namespace App\Livewire\Forms\Admin;

use App\Models\Persona;
use Livewire\Attributes\Locked;
use Livewire\Form;

class PersonaForm extends Form
{
    public ?Persona $persona;
    #[Locked]
    public ?int $id = null;
    public ?string $nombre = '';
    public ?string $apellido_paterno = '';
    public ?string $apellido_materno = '';
    public ?string $fecha_nacimiento = null;
    public ?int $genero = null;
    public ?int $dni = null;
    public ?int $tipo_persona = null;
    public ?int $area_id = null;
    public function mount()
    {
        $this->persona = new Persona();
    }
    public function getPersona()
    {
        return $this->persona;
    }
    public function rules()
    {
        return [
            'dni' => 'required|integer|min:10000000',
            'nombre' => 'required|string|min:2|max:30|regex:/^[\p{L}]+$/u',
            'apellido_paterno' => 'required|min:2|max:30|regex:/^[\p{L}]+$/u',
            'apellido_materno' => 'required|min:2|max:30|regex:/^[\p{L}]+$/u',
            'fecha_nacimiento' => 'required|date|before:today',
            'genero' => 'required|integer|min:1',
            'tipo_persona' => 'required|integer|min:1',
            'area_id' => 'required|integer|min:1',
        ];
    }
    public function setPersona(int $id)
    {
        $this->persona = Persona::findOrFail($id);
        $this->id = $this->persona->id;
        $this->dni = $this->persona->dni;
        $this->nombre = $this->persona->nombre;
        $this->apellido_paterno = $this->persona->apellido_paterno;
        $this->apellido_materno = $this->persona->apellido_materno;
        $this->fecha_nacimiento = $this->persona->fecha_nacimiento;
        $this->genero = $this->persona->genero;
        $this->tipo_persona = $this->persona->tipo_persona;
        $this->area_id = $this->persona->area_id;
    }
    public function store()
    {
        $this->nombre = trim($this->nombre);
        $this->apellido_paterno = trim($this->apellido_paterno);
        $this->apellido_materno = trim($this->apellido_materno);
        $persona = new Persona(array_merge(['id' => $this->id], $this->only(['nombre', 'dni', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 'genero', 'tipo_persona', 'area_id'])));
        $persona->save();
    }
    public function update()
    {
        return $this->persona->update($this->only(['nombre', 'dni', 'apellido_paterno', 'apellido_materno', 'fecha_nacimiento', 'genero', 'tipo_persona', 'area_id']));
    }
}