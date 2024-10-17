<?php

namespace App\Livewire\Tables;

use App\Models\Permission;
use Carbon\Carbon;
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

final class PermissionTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'PermissionTable';
    public string $moduleName = 'permisos';
    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Exportable::make('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()->showSoftDeletes()->showSearchInput()->includeViewOnTop('components.table.header-top'),
            Footer::make()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Permission::query();
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
            ->add('created_at_formatted', function ($dish) {
                return Carbon::parse($dish->created_at)->format('d/m/Y'); //20/01/2024 10:05
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
            Column::action('Acciones')
        ];
    }
    public function filters(): array
    {
        return [
        ];
    }
    public function deletePermission($id)
    {
        if (!Auth::user()->can('eliminar ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $permission = Permission::findOrFail($id);
        $this->dispatch('deletePermission', ['permission' => $permission]);
    }
    public function editPermission($id)
    {
        if (!Auth::user()->can('editar ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $permission = Permission::findOrFail($id);
        $this->dispatch('editPermission', ['permission' => $permission]);
    }
    public function activePermission($id)
    {
        Permission::onlyTrashed()->where('id', $id)->restore();
    }
    public function open()
    {
        if (!Auth::user()->can('crear ' . $this->moduleName)) {
            return redirect()->route($this->moduleName);
        }
        $this->dispatch('createPermission');
    }
    public function actions(Permission $permission): array
    {
        /* dd($permission); */
        return [
            Button::add('edit')
                ->render(function ($permission) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="pencil" flat gray interaction="positive" wire:click="editPermission('{{ $permission->id }}')" />
                        @endcan
                    HTML);
                }),
            Button::add('delete')
                ->render(function ($permission) {
                    return Blade::render(<<<HTML
                        @can('eliminar $this->moduleName')
                            <x-mini-button rounded icon="trash" flat gray interaction="negative" wire:click="deletePermission('$permission->id')" />
                        @endcan
                    HTML);
                }),
            Button::add('active')
                ->render(function ($permission) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="bolt" flat gray interaction="warning" wire:click="activePermission('$permission->id')" />
                        @endcan
                    HTML);
                }),
        ];
    }
    public function actionRules(): array
    {
        return [
            Rule::button('edit')
                ->when(fn($permission) => $permission->trashed())
                ->hide(),
            Rule::button('delete')
                ->when(fn($permission) => $permission->trashed())
                ->hide(),
            Rule::button('active')
                ->when(fn($permission) => !$permission->trashed())
                ->hide(),
            Rule::rows()
                ->when(fn($permission) => $permission->trashed())
                ->setAttribute('class', 'bg-red-200 dark:bg-red-500'),
        ];
    }
}