<?php

namespace App\Livewire;

use App\Models\Escuela;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;

use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Rule;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Responsive;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class EscuelaTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'EscuelaTable';
    public string $moduleName = 'escuelas';

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
        return Escuela::query();
    }

    public function open()
    {
        $this->dispatch('createEscuela');
    }

    public function deleteEscuela($id)
    {
        if (!Auth::user()->can('eliminar ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $escuela = Escuela::findOrFail($id);
        $this->dispatch('deleteEscuela', ['escuela' => $escuela]);
    }

    public function editEscuela($id)
    {
        if (!Auth::user()->can('editar ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $escuela = Escuela::findOrFail($id);
        $this->dispatch('editEscuela', ['escuela' => $escuela]);
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

    public function actions(Escuela $escuela): array
    {
        return [
            Button::add('edit')
                ->render(function ($escuela) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="pencil" flat gray interaction="positive" wire:click="editEscuela('{{ $escuela->id }}')" />
                        @endcan
                    HTML);
                }),
            Button::add('delete')
                ->render(function ($escuela) {
                    return Blade::render(<<<HTML
                        @can('eliminar $this->moduleName')
                            <x-mini-button rounded icon="trash" flat gray interaction="negative" wire:click="deleteEscuela('$escuela->id')" />
                        @endcan
                    HTML);
                })
        ];
    }
    public function actionRules(): array
    {
        return [
            Rule::button('delete')
                ->when(fn($escuela) => $escuela->roles->count() > 0)
                ->hide(),
        ];
    }
}