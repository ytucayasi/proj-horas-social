<?php

namespace App\Livewire;

use App\Models\Actividad;
use App\Models\Estudiante;
use App\Models\EstudianteActividad;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class EstudianteActividadTable extends PowerGridComponent
{
    use WithExport;
    public ?int $actividad_id = null;
    public string $tableName = 'EstudianteActividadTable';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Exportable::make('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()->showSearchInput()->includeViewOnTop('components.table.header-top-estudiante'),
            Footer::make()
                ->pageName('estudianteActividadPage')
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return EstudianteActividad::query()
            ->when(
                $this->actividad_id,
                fn($builder) => $builder->whereHas(
                    'actividad',
                    fn($builder) => $builder->where('actividad_id', $this->actividad_id)
                )
            )
            ->with(['estudiante', 'actividad']);
    }

    public function relationSearch(): array
    {
        return [
            'estudiante' => [
                'codigo',
            ],
        ];
    }

    #[On('addStudents')]
    public function verActividad(Actividad $actividad)
    {
        $this->actividad_id = $actividad->id;
    }

    public function openExcel()
    {
        $this->dispatch('openExcel');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('codigo', fn($estudiante_actividad) => e($estudiante_actividad->estudiante->codigo));
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Estudiante', 'codigo', 'codigo')->searchable(),
            Column::make('Horas', 'horas'),
            Column::action('Action')
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }

    public function deleteEstudianteActividad($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $this->dispatch('deleteEstudianteActividad', ['estudiante' => $estudiante]);
    }
    public function editEstudianteActividad($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $this->dispatch('editEstudianteActividad', ['estudiante' => $estudiante]);
    }

    public function actions(EstudianteActividad $estudiante_actividad): array
    {
        return [
            Button::add('edit')
                ->render(function ($estudiante_actividad) {
                    return Blade::render(<<<HTML
                        <x-mini-button rounded icon="pencil" flat gray interaction="positive" wire:click="editEstudianteActividad('$estudiante_actividad->estudiante_id')" />
                    HTML);
                }),
            Button::add('delete')
                ->render(function ($estudiante_actividad) {
                    return Blade::render(<<<HTML
                        <x-mini-button rounded icon="trash" flat gray interaction="negative" wire:click="deleteEstudianteActividad('$estudiante_actividad->estudiante_id')" />
                    HTML);
                }),
        ];
    }
}