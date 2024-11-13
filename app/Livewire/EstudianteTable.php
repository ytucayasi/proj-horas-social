<?php

namespace App\Livewire;

use App\Models\Estudiante;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log;
use PowerComponents\LivewirePowerGrid\Button;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Exportable;
use PowerComponents\LivewirePowerGrid\Facades\Filter;
use PowerComponents\LivewirePowerGrid\Footer;
use PowerComponents\LivewirePowerGrid\Header;
use PowerComponents\LivewirePowerGrid\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridFields;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\Responsive;
use PowerComponents\LivewirePowerGrid\Traits\WithExport;

final class EstudianteTable extends PowerGridComponent
{
    use WithExport;
    public string $tableName = 'EstudianteTable';
    public string $moduleName = 'estudiantes';
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
        $user = Auth::user();

        // Obtener los roles del usuario
        $roles = $user->roles;

        // Logging para depuración
        Log::info('Datasource debug info', [
            'user_id' => $user->id,
            'roles' => $roles->pluck('name', 'id')->toArray(),
            'roles_data' => $roles->toArray(),
            'escuela_ids' => $roles->pluck('escuela_id')->filter()->unique()->toArray()
        ]);

        // Si el usuario es super-admin, mostrar todos los estudiantes
        if ($user->hasRole('Super Admin') || $user->hasRole('Admin')) {
            return Estudiante::query();
        }

        // Obtener los IDs de las escuelas asociadas a los roles del usuario
        $escuelaIds = $roles->pluck('escuela_id')->filter()->unique()->toArray();

        // Si no hay escuelas asociadas, devolver una consulta vacía
        if (empty($escuelaIds)) {
            return Estudiante::query()->whereRaw('1 = 0');
        }

        // Filtrar estudiantes por las escuelas del usuario
        return Estudiante::query()->whereHas('escuela', function ($query) use ($escuelaIds) {
            $query->whereIn('id', $escuelaIds);
        });
    }

    public function open()
    {
        $this->dispatch('createEstudiante');
    }
    public function deleteEstudiante($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $this->dispatch('deleteEstudiante', ['estudiante' => $estudiante]);
    }
    public function mostrarActividades($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $this->dispatch('mostrarActividades', ['estudiante' => $estudiante]);
    }
    public function editEstudiante($id)
    {
        $estudiante = Estudiante::findOrFail($id);
        $this->dispatch('editEstudiante', ['estudiante' => $estudiante]);
    }
    public function relationSearch(): array
    {
        return [];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('codigo')
            ->add('dni')
            ->add('horas_base', function ($item) {
                return $item->horas_base > 0 ? $item->horas_base : '-';
            })
            ->add('periodo_id', function ($item) {
                return $item->periodo ? $item->periodo->nombre : '-';
            })
            ->add('escuela_id', function ($item) {
                return $item->escuela ? $item->escuela->nombre : '-';
            })
            ->add('estado');
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id'),
            Column::make('Codigo', 'codigo')
                ->sortable()
                ->searchable(),
            Column::make('DNI', 'dni')
                ->sortable()
                ->searchable(),
            Column::make('Horas', 'horas_base')
                ->sortable(),
            Column::make('Periodo', 'periodo_id')
                ->sortable(),
            Column::make('Escuela Académica', 'escuela_id')
                ->sortable(),
            Column::action('Acciones')
        ];
    }

    protected function findNameById(array $array, int $id): string
    {
        foreach ($array as $item) {
            if ($item['id'] === $id) {
                return $item['name'];
            }
        }
        return 'Desconocido'; // Valor por defecto si no se encuentra el ID
    }

    public function filters(): array
    {
        return [
        ];
    }

    #[\Livewire\Attributes\On('edit')]
    public function edit($rowId): void
    {
        $this->js('alert(' . $rowId . ')');
    }

    public function actions(Estudiante $estudiante): array
    {
        return [
            Button::add('edit')
                ->render(function ($estudiante) {
                    return Blade::render(<<<HTML
                        @can('editar $this->moduleName')
                            <x-mini-button rounded icon="pencil" flat gray interaction="positive" wire:click="editEstudiante('{{ $estudiante->id }}')" />
                        @endcan
                    HTML);
                }),
            Button::add('delete')
                ->render(function ($estudiante) {
                    return Blade::render(<<<HTML
                        @can('eliminar $this->moduleName')
                            <x-mini-button rounded icon="trash" flat gray interaction="negative" wire:click="deleteEstudiante('$estudiante->id')" />
                        @endcan
                    HTML);
                }),
            Button::add('mostrar_actividades')
                ->render(function ($estudiante) {
                    return Blade::render(<<<HTML
                        @can('mostrar $this->moduleName')
                            <x-mini-button rounded icon="eye" flat gray interaction="purple" wire:click="mostrarActividades('$estudiante->id')" />
                        @endcan
                    HTML);
                })
        ];
    }

    /*
    public function actionRules($row): array
    {
       return [
            // Hide button edit for ID 1
            Rule::button('edit')
                ->when(fn($row) => $row->id === 1)
                ->hide(),
        ];
    }
    */
}
