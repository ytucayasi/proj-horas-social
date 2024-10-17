<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Estudiante;
use App\Models\Actividad;
use App\Models\EstudianteActividad;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')]
class extends Component
{
    public function with(): array
    {
        return [
            'userCount' => User::count(),
            'roleCount' => Role::count(),
            'estudianteCount' => Estudiante::count(),
            'actividadCount' => Actividad::count(),
            'actividades' => Actividad::withCount(['estudianteActividades as estudiantes_count' => function ($query) {
                $query->where('estado', 1);
            }])->get(),
        ];
    }
}; ?>

<div class="p-6 bg-gray-100">
    <h1 class="text-3xl font-bold text-blue-800 mb-8">Dashboard</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-blue-500">
                <h2 class="text-lg font-semibold text-white">Usuarios</h2>
            </div>
            <div class="p-4">
                <p class="text-3xl font-bold text-gray-600">{{ $userCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-green-500">
                <h2 class="text-lg font-semibold text-white">Roles</h2>
            </div>
            <div class="p-4">
                <p class="text-3xl font-bold text-gray-600">{{ $roleCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-red-500">
                <h2 class="text-lg font-semibold text-white">Estudiantes</h2>
            </div>
            <div class="p-4">
                <p class="text-3xl font-bold text-gray-600">{{ $estudianteCount }}</p>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-4 bg-yellow-500">
                <h2 class="text-lg font-semibold text-white">Actividades</h2>
            </div>
            <div class="p-4">
                <p class="text-3xl font-bold text-gray-600">{{ $actividadCount }}</p>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <div class="p-4 bg-violet-600">
            <h2 class="text-xl font-semibold text-white">Actividades y Estudiantes Activos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-blue-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Actividad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-800 uppercase tracking-wider">Estudiantes Activos</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($actividades as $actividad)
                    <tr class="hover:bg-blue-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap text-blue-700">{{ $actividad->motivo }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-blue-600">{{ $actividad->estudiantes_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>