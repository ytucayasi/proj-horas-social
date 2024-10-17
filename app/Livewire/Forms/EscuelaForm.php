<?php

namespace App\Livewire\Forms;

use App\Models\Escuela;
use Illuminate\Validation\Rule;
use Livewire\Form;

class EscuelaForm extends Form
{
    public ?Escuela $escuela = null;
    public ?int $id = null;
    public ?string $nombre = '';

    public function rules()
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('escuelas')->ignore($this->escuela),
            ],
        ];
    }

    public function store()
    {
        Escuela::create($this->only(['nombre']));
    }

    public function update()
    {
        $this->escuela->update($this->only(['nombre']));
    }

    public function setEscuela(Escuela $escuela)
    {
        $this->escuela = $escuela;
        $this->id = $escuela->id;
        $this->nombre = $escuela->nombre;
    }

    public function delete()
    {
        $this->escuela->delete();
    }
}