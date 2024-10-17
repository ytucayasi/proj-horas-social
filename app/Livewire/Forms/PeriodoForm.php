<?php

namespace App\Livewire\Forms;

use App\Models\Periodo;
use Illuminate\Validation\Rule;
use Livewire\Form;

class PeriodoForm extends Form
{
    public ?Periodo $periodo = null;
    public ?int $id = null;
    public ?string $nombre = '';

    public function rules()
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('periodos')->ignore($this->periodo),
            ],
        ];
    }

    public function store()
    {
        $nombre = $this->formatNombrePeriodo($this->nombre);
        Periodo::create(['nombre' => $nombre]);
    }

    public function update()
    {
        $nombre = $this->formatNombrePeriodo($this->nombre);
        $this->periodo->update(['nombre' => $nombre]);
    }

    private function formatNombrePeriodo($nombre)
    {
        // Elimina cualquier guión existente y espacios en blanco
        $nombre = str_replace(['-', ' '], '', $nombre);

        // Inserta el guión después del cuarto carácter
        return substr($nombre, 0, 4) . '-' . substr($nombre, 4);
    }

    public function setPeriodo(Periodo $periodo)
    {
        $this->periodo = $periodo;
        $this->id = $periodo->id;
        $this->nombre = $periodo->nombre;
    }

    public function delete()
    {
        $this->periodo->delete();
    }
}