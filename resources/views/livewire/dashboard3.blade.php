<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Estudiante;
use App\Models\Actividad;
use App\Models\EstudianteActividad;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        $user = Auth::user();
        $roles = $user->roles;
        $adminRoles = ['Super Admin', 'Admin'];
        $isAdmin = $roles->pluck('name')->intersect($adminRoles)->isNotEmpty();

        if ($isAdmin) {
            // 1. Total de Estudiantes por Escuela (Gráfico de Barras)
            $estudiantesPorEscuela = DB::table('estudiantes')
                ->join('escuelas', 'estudiantes.escuela_id', '=', 'escuelas.id')
                ->select('escuelas.nombre', DB::raw('count(*) as total'))
                ->where('estudiantes.estado', '1')
                ->groupBy('escuelas.id', 'escuelas.nombre')
                ->get();

            // 2. Top 5 Actividades con más Inscripciones (Gráfico de Dona)
            $actividadesTop = DB::table('actividades')
                ->join('estudiantes_actividades', 'actividades.id', '=', 'estudiantes_actividades.actividad_id')
                ->select(
                    'actividades.motivo',
                    DB::raw('count(estudiantes_actividades.id) as total')
                )
                ->where('estudiantes_actividades.estado', '1')
                ->groupBy('actividades.id', 'actividades.motivo')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            // 3. Actividades Publicadas vs No Publicadas (Gráfico de Pie)
            $estadoActividades = DB::table('actividades')
                ->select(
                    DB::raw('CASE WHEN publicado = 1 THEN "Publicadas" ELSE "No Publicadas" END as estado'),
                    DB::raw('count(*) as total')
                )
                ->groupBy('publicado')
                ->get();

            // 4. Promedio de Horas por Escuela (Gráfico de Barras Horizontal)
            $promedioHoras = DB::table('estudiantes_actividades')
                ->join('estudiantes', 'estudiantes_actividades.estudiante_id', '=', 'estudiantes.id')
                ->join('escuelas', 'estudiantes.escuela_id', '=', 'escuelas.id')
                ->select(
                    'escuelas.nombre',
                    DB::raw('ROUND(AVG(estudiantes_actividades.horas), 2) as promedio')
                )
                ->where('estudiantes_actividades.estado', '1')
                ->groupBy('escuelas.id', 'escuelas.nombre')
                ->get();

        } else {
            $escuelaIds = $roles->pluck('escuela_id')->unique()->filter();

            // Aplicar los mismos filtros pero limitado a las escuelas del usuario
            $estudiantesPorEscuela = DB::table('estudiantes')
                ->join('escuelas', 'estudiantes.escuela_id', '=', 'escuelas.id')
                ->whereIn('estudiantes.escuela_id', $escuelaIds)
                ->select('escuelas.nombre', DB::raw('count(*) as total'))
                ->where('estudiantes.estado', '1')
                ->groupBy('escuelas.id', 'escuelas.nombre')
                ->get();

            $actividadesTop = DB::table('actividades')
                ->join('estudiantes_actividades', 'actividades.id', '=', 'estudiantes_actividades.actividad_id')
                ->join('estudiantes', 'estudiantes_actividades.estudiante_id', '=', 'estudiantes.id')
                ->whereIn('estudiantes.escuela_id', $escuelaIds)
                ->select(
                    'actividades.motivo',
                    DB::raw('count(estudiantes_actividades.id) as total')
                )
                ->where('estudiantes_actividades.estado', '1')
                ->groupBy('actividades.id', 'actividades.motivo')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $estadoActividades = DB::table('actividades')
                ->join('estudiantes_actividades', 'actividades.id', '=', 'estudiantes_actividades.actividad_id')
                ->join('estudiantes', 'estudiantes_actividades.estudiante_id', '=', 'estudiantes.id')
                ->whereIn('estudiantes.escuela_id', $escuelaIds)
                ->select(
                    DB::raw('CASE WHEN actividades.publicado = 1 THEN "Publicadas" ELSE "No Publicadas" END as estado'),
                    DB::raw('count(DISTINCT actividades.id) as total')
                )
                ->groupBy('actividades.publicado')
                ->get();

            $promedioHoras = DB::table('estudiantes_actividades')
                ->join('estudiantes', 'estudiantes_actividades.estudiante_id', '=', 'estudiantes.id')
                ->join('escuelas', 'estudiantes.escuela_id', '=', 'escuelas.id')
                ->whereIn('estudiantes.escuela_id', $escuelaIds)
                ->select(
                    'escuelas.nombre',
                    DB::raw('ROUND(AVG(estudiantes_actividades.horas), 2) as promedio')
                )
                ->where('estudiantes_actividades.estado', '1')
                ->groupBy('escuelas.id', 'escuelas.nombre')
                ->get();
        }

        return [
            'chartData' => [
                'estudiantesPorEscuela' => [
                    'labels' => $estudiantesPorEscuela->pluck('nombre')->toArray(),
                    'series' => [['data' => $estudiantesPorEscuela->pluck('total')->toArray()]]
                ],
                'actividadesTop' => [
                    'labels' => $actividadesTop->pluck('motivo')->toArray(),
                    'series' => $actividadesTop->pluck('total')->toArray()
                ],
                'estadoActividades' => [
                    'labels' => $estadoActividades->pluck('estado')->toArray(),
                    'series' => $estadoActividades->pluck('total')->toArray()
                ],
                'promedioHoras' => [
                    'labels' => $promedioHoras->pluck('nombre')->toArray(),
                    'series' => [['data' => $promedioHoras->pluck('promedio')->toArray()]]
                ]
            ]
        ];
    }
}
?>

<div>
    <div class="p-6 bg-white shadow-sm mb-6 rounded-lg">
        <h2 class="text-2xl font-bold text-gray-800">Panel de Control</h2>
        <p class="text-gray-600">Resumen de estadísticas</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6">
        <!-- Gráfico 1: Estudiantes por Escuela -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Estudiantes por Escuela</h3>
            <div id="estudiantesEscuelaChart" class="h-[400px]"></div>
        </div>

        <!-- Gráfico 2: Top 5 Actividades -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Top 5 Actividades</h3>
            <div id="actividadesTopChart" class="h-[400px]"></div>
        </div>

        <!-- Gráfico 3: Estado de Actividades -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Estado de Actividades</h3>
            <div id="estadoActividadesChart" class="h-[400px]"></div>
        </div>

        <!-- Gráfico 4: Promedio de Horas -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Promedio de Horas por Escuela</h3>
            <div id="promedioHorasChart" class="h-[400px]"></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('livewire:navigated', function () {
    // 1. Estudiantes por Escuela
    const estudiantesEscuelaChart = new ApexCharts(document.querySelector("#estudiantesEscuelaChart"), {
        series: @json($chartData['estudiantesPorEscuela']['series']),
        chart: {
            type: 'bar',
            height: 400
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
            }
        },
        dataLabels: {
            enabled: true
        },
        xaxis: {
            categories: @json($chartData['estudiantesPorEscuela']['labels']),
            labels: {
                rotate: -45,
                style: {
                    fontSize: '12px'
                }
            }
        },
        colors: ['#3B82F6']
    });
    estudiantesEscuelaChart.render();

    // 2. Top 5 Actividades
    const actividadesTopChart = new ApexCharts(document.querySelector("#actividadesTopChart"), {
        series: @json($chartData['actividadesTop']['series']),
        chart: {
            type: 'donut',
            height: 400
        },
        labels: @json($chartData['actividadesTop']['labels']),
        colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    });
    actividadesTopChart.render();

    // 3. Estado de Actividades
    const estadoActividadesChart = new ApexCharts(document.querySelector("#estadoActividadesChart"), {
        series: @json($chartData['estadoActividades']['series']),
        chart: {
            type: 'pie',
            height: 400
        },
        labels: @json($chartData['estadoActividades']['labels']),
        colors: ['#10B981', '#EF4444'],
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 200
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    });
    estadoActividadesChart.render();

    // 4. Promedio de Horas
    const promedioHorasChart = new ApexCharts(document.querySelector("#promedioHorasChart"), {
        series: @json($chartData['promedioHoras']['series']),
        chart: {
            type: 'bar',
            height: 400
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: true,
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val) {
                return val + ' horas';
            }
        },
        xaxis: {
            categories: @json($chartData['promedioHoras']['labels']),
        },
        colors: ['#8B5CF6']
    });
    promedioHorasChart.render();

    /* HOLA */
    estudiantesEscuelaChart.updateOptions({
        xaxis: {
            categories: @json($chartData['estudiantesPorEscuela']['labels'])
        }
    });
    estudiantesEscuelaChart.updateSeries(@json($chartData['estudiantesPorEscuela']['series']));
    
    actividadesTopChart.updateSeries(@json($chartData['actividadesTop']['series']));
    
    estadoActividadesChart.updateSeries(@json($chartData['estadoActividades']['series']));
    
    promedioHorasChart.updateOptions({
        xaxis: {
            categories: @json($chartData['promedioHoras']['labels'])
        }
    });
    promedioHorasChart.updateSeries(@json($chartData['promedioHoras']['series']));
});
</script>
