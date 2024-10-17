<?php

namespace App\Livewire\Tables;

use Carbon\Carbon;
use DateTime;
use Spatie\Permission\Models\Role;
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
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class RoleTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'RoleTable';
    public string $moduleName = 'roles';
    public function setUp(): array
    {
        $this->showCheckBox();

        return [
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
        return Role::query()->whereNotIn('name', ['Super Admin']);
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('name')
            ->add('created_at')
            ->add('updated_at')
            ->add('created_at_formatted', function ($dish) {
                return Carbon::parse($dish->created_at)->format('d/m/Y'); //20/01/2024 10:05
            })->add('updated_at_formatted', function ($dish) {
                return Carbon::parse($dish->updated_at)->format('d/m/Y'); //20/01/2024 10:05
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Nombre', 'name')
                ->sortable()
                ->searchable(),
            Column::make('Creado', 'created_at_formatted', 'created_at')
                ->sortable(),
            Column::make('Actualizado', 'updated_at_formatted', 'updated_at')
                ->sortable(),
            Column::action('Acciones')
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }
    public function deleteRole($id)
    {
        if (!Auth::user()->can('eliminar ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $this->dispatch('deleteRole', ['role' => Role::findOrFail($id)]);
    }
    public function editRole($id)
    {
        if (!Auth::user()->can('editar ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $this->dispatch('editRole', ['role' => Role::findOrFail($id)]);
    }
    public function showPermissions($id)
    {
        $this->dispatch('showPermissions', ['role' => Role::findOrFail($id)]);
    }
    public function open()
    {
        if (!Auth::user()->can('crear ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $this->dispatch('createRole');
    }
    public function actions(Role $role): array
    {
        return [
            Button::add('edit')
                ->render(function ($role) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="pencil" flat gray interaction="positive" wire:click="editRole('$role->id')" />
                        @endcan
                    HTML);
                }),
            Button::add('delete')
                ->render(function ($role) {
                    return Blade::render(<<<HTML
                        @can('eliminar $this->moduleName')
                            <x-mini-button rounded icon="trash" flat gray interaction="negative" wire:click="deleteRole('$role->id')" />
                        @endcan
                    HTML);
                }),
            Button::add('permissions')
                ->render(function ($role) {
                    return Blade::render(<<<HTML
                        <x-mini-button rounded icon="clipboard-document-list" flat gray interaction="lime" wire:click="showPermissions('$role->id')" />
                    HTML);
                }),
        ];
    }
    public function actionRules($row): array
    {
        return [
            Rule::button('edit')
                ->when(fn($row) => $row->id === 2)
                ->hide(),
            Rule::button('permissions')
                ->when(fn($row) => $row->id === 2)
                ->hide(),
            Rule::button('delete')
                ->when(fn($row) => $row->id === 2 || $row->users->count() > 0)
                ->hide(),
        ];
    }
}