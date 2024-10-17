<?php

namespace App\Livewire;

use App\Models\Periodo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Responsive;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class PeriodoTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'PeriodoTable';
    public string $moduleName = 'periodos';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Responsive::make(),
            Exportable::make('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()->showSearchInput()->includeViewOnTop('components.table.header-top'),
            Footer::make()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Periodo::query();
    }

    public function open()
    {
        $this->dispatch('createPeriodo');
    }

    public function deletePeriodo($id)
    {
        $periodo = Periodo::findOrFail($id);
        $this->dispatch('deletePeriodo', ['periodo' => $periodo]);
    }

    public function editPeriodo($id)
    {
        $periodo = Periodo::findOrFail($id);
        $this->dispatch('editPeriodo', ['periodo' => $periodo]);
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('nombre');
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Nombre', 'nombre')
                ->sortable()
                ->searchable(),
            Column::action('Acciones')
        ];
    }

    public function filters(): array
    {
        return [];
    }

    public function actions(Periodo $periodo): array
    {
        return [
            Button::add('edit')
                ->render(function ($periodo) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="pencil" flat gray interaction="positive" wire:click="editPeriodo('{{ $periodo->id }}')" />
                        @endcan
                    HTML);
                }),
            Button::add('delete')
                ->render(function ($periodo) {
                    return Blade::render(<<<HTML
                        @can('eliminar $this->moduleName')
                            <x-mini-button rounded icon="trash" flat gray interaction="negative" wire:click="deletePeriodo('$periodo->id')" />
                        @endcan
                    HTML);
                })
        ];
    }
}