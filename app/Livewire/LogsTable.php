<?php

namespace App\Livewire;

use App\Models\Log;
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

final class LogsTable extends PowerGridComponent
{
    use WithExport;

    public string $tableName = 'LogsTable';
    public string $moduleName = 'logs';

    public function setUp(): array
    {
        $this->showCheckBox();

        return [
            Responsive::make(),
            Exportable::make('export')
                ->striped()
                ->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV),
            Header::make()->showSearchInput(),
            Footer::make()
                ->showPerPage()
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return Log::query()
            ->join('users', 'logs.user_id', '=', 'users.id')
            ->select('logs.*', 'users.name as user_name');
    }

    public function viewLog($id)
    {
        $log = Log::findOrFail($id);
        $this->dispatch('viewLog', ['log' => $log]);
    }

    public function relationSearch(): array
    {
        return [
            'user' => ['name']
        ];
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('action')
            ->add('model_type')
            ->add('model_id')
            ->add('user_name')
            ->add('created_at_formatted', function ($entry) {
                return $entry->created_at->format('d/m/Y H:i:s');
            });
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable(),
            Column::make('Acción', 'action')
                ->sortable()
                ->searchable(),
            Column::make('Modelo', 'model_type')
                ->sortable()
                ->searchable(),
            Column::make('ID Modelo', 'model_id')
                ->sortable(),
            Column::make('Usuario', 'user_name')
                ->sortable()
                ->searchable(),
            Column::make('Fecha', 'created_at_formatted')
                ->sortable(),
            Column::action('Acciones')
        ];
    }

    public function actions(Log $log): array
    {
        return [
            Button::add('view')
                ->render(function ($log) {
                    return Blade::render(<<<HTML
                        <x-mini-button rounded icon="eye" flat gray interaction="positive" wire:click="viewLog('{{ $log->id }}')" />
                    HTML);
                }),
        ];
    }

    public function filters(): array
    {
        return [
        ];
    }
}