<?php

namespace App\Livewire;

use App\Models\Actividad;
use DateTime;
use Illuminate\Support\Carbon;
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

final class ActividadTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'ActividadTable';
    public string $moduleName = 'actividades';

    public function open()
    {
        $this->dispatch('createActividad');
    }
    /*  */
    private const ADMIN_ROLES = ['Super Admin', 'Admin'];

    public function datasource(): Builder
    {
        $query = Actividad::query();
        if (!$this->userIsAdmin()) {
            $userRoles = Auth::user()->roles->pluck('name')->toArray();
            $query->whereHas('user.roles', function ($q) use ($userRoles) {
                $q->whereIn('name', $userRoles);
            });
        }
        return $query;
    }

    private function userIsAdmin(): bool
    {
        return Auth::user()->hasAnyRole(self::ADMIN_ROLES);
    }

    private function canAccessActividad(Actividad $actividad): bool
    {
        return $this->userIsAdmin() ||
            Auth::user()->roles->pluck('name')
                ->intersect($actividad->user->roles->pluck('name'))
                ->isNotEmpty();
    }

    private function checkPermissionAndAccess(string $permission, Actividad $actividad): bool
    {
        return Auth::user()->can($permission . ' ' . $this->moduleName) &&
            $this->canAccessActividad($actividad);
    }

    public function deleteActividad($id)
    {
        $actividad = Actividad::findOrFail($id);
        if (!$this->checkPermissionAndAccess('eliminar', $actividad)) {
            return $this->handleUnauthorizedAccess();
        }
        $this->dispatch('deleteActividad', ['actividad' => $actividad]);
    }

    public function editActividad($id)
    {
        $actividad = Actividad::findOrFail($id);
        if (!$this->checkPermissionAndAccess('editar', $actividad)) {
            return $this->handleUnauthorizedAccess();
        }
        $this->dispatch('editActividad', ['actividad' => $actividad]);
    }

    public function activeActividad($id)
    {
        $actividad = Actividad::findOrFail($id);
        if (!$this->checkPermissionAndAccess('editar', $actividad)) {
            return $this->handleUnauthorizedAccess();
        }
        $this->dispatch('activeActividad', ['actividad' => $actividad]);
    }

    public function addStudents($id)
    {
        $actividad = Actividad::findOrFail($id);
        if (!$this->checkPermissionAndAccess('editar', $actividad)) {
            return $this->handleUnauthorizedAccess();
        }
        $this->dispatch('addStudents', ['actividad' => $actividad]);
    }

    private function handleUnauthorizedAccess()
    {
        return redirect()->route($this->moduleName)->with('error', 'No tienes permiso para realizar esta acción.');
    }
    /*  */
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
                ->pageName('actividadPage')
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        $publicados = [
            [
                'id' => 1,
                'name' => 'No Publicado'
            ],
            [
                'id' => 2,
                'name' => 'Publicado'
            ],
        ];
        return PowerGrid::fields()
            ->add('motivo')
            ->add('fecha_inicio')
            ->add('fecha_fin')
            ->add('horas', function ($item) {
                return $item->horas ? (new DateTime($item->horas))->format('H:i') : '-';
            })
            ->add('publicado', function ($item) use ($publicados) {
                $publicado = collect($publicados)->firstWhere('id', $item->publicado);
                return $publicado ? $publicado['name'] : $item->publicado;
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Motivo', 'motivo')
                ->sortable()
                ->searchable(),
            Column::make('Fecha Inicio', 'fecha_inicio')
                ->sortable(),
            Column::make('Fecha Fin', 'fecha_fin')
                ->sortable(),
            Column::make('Horario', 'horas')
                ->sortable(),
            Column::make('Estado', 'publicado')
                ->sortable(),
            Column::action('Acciones')
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }
    public function actions(Actividad $actividad): array
    {
        return [
            Button::add('edit')
                ->render(function ($actividad) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="pencil" flat gray interaction="positive" wire:click="editActividad('{{ $actividad->id }}')" />
                        @endcan
                    HTML);
                }),
            Button::add('delete')
                ->render(function ($actividad) {
                    return Blade::render(<<<HTML
                        @can('eliminar $this->moduleName')
                            <x-mini-button rounded icon="trash" flat gray interaction="negative" wire:click="deleteActividad('$actividad->id')" />
                        @endcan
                    HTML);
                }),
            Button::add('add_students')
                ->render(function ($actividad) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="users" flat gray interaction="purple" wire:click="addStudents('$actividad->id')" />
                        @endcan
                    HTML);
                }),
            Button::add('btn_activate')
                ->render(function ($actividad) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="check-circle" flat gray interaction="blue" wire:click="activeActividad('$actividad->id')" />
                        @endcan
                    HTML);
                }),
            Button::add('btn_inactive')
                ->render(function ($actividad) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="x-circle" flat gray interaction="orange" wire:click="activeActividad('$actividad->id')" />
                        @endcan
                    HTML);
                })
        ];
    }
    public function actionRules(): array
    {
        return [
            Rule::button('btn_activate')
                ->when(fn($actividad) => $actividad->publicado == 2)->hide(),
            Rule::button('btn_inactive')
                ->when(fn($actividad) => $actividad->publicado == 1)->hide(),
        ];
    }
}