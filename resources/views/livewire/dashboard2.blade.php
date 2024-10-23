<?php
// dashboard2.blade.php

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

new #[Layout('layouts.app')]
class extends Component
{
    public function with(): array
    {
        $user = Auth::user();
        $roles = $user->roles;
        $adminRoles = ['Super Admin', 'Admin'];
        $isAdmin = $roles->pluck('name')->intersect($adminRoles)->isNotEmpty();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        if ($isAdmin) {
            // 1. Actividades realizadas en el mes actual
            $actividadesMes = Actividad::selectRaw('DATE(fecha_inicio) as fecha, COUNT(*) as total')
                ->whereMonth('fecha_inicio', $currentMonth)
                ->whereYear('fecha_inicio', $currentYear)
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get();

            // 2. Estudiantes inscritos por actividad
            $inscripcionesPorActividad = DB::table('estudiantes_actividades')
                ->join('actividades', 'actividades.id', '=', 'estudiantes_actividades.actividad_id')
                ->select('actividades.motivo', DB::raw('COUNT(estudiantes_actividades.id) as total_inscritos'))
                ->where('estudiantes_actividades.estado', '1')
                ->groupBy('actividades.id', 'actividades.motivo')
                ->orderByDesc('total_inscritos')
                ->limit(10)
                ->get();

            // 3. Estudiantes creados por mes
            $estudiantesPorMes = Estudiante::selectRaw('MONTH(created_at) as mes, COUNT(*) as total')
                ->whereYear('created_at', $currentYear)
                ->groupBy('mes')
                ->orderBy('mes')
                ->get();

            // 4. Distribución de duración de actividades
            $distribucionHoras = Actividad::selectRaw('
                CASE 
                    WHEN HOUR(horas) <= 2 THEN "1-2 horas"
                    WHEN HOUR(horas) <= 4 THEN "3-4 horas"
                    WHEN HOUR(horas) <= 6 THEN "5-6 horas"
                    ELSE "Más de 6 horas"
                END as rango,
                COUNT(*) as total
            ')
            ->groupBy('rango')
            ->get();

            // 5. Estudiantes con más de 100 horas en el mes actual
            $estudiantesHoras = DB::table('estudiantes')
                ->join('estudiantes_actividades', 'estudiantes.id', '=', 'estudiantes_actividades.estudiante_id')
                ->join('actividades', 'actividades.id', '=', 'estudiantes_actividades.actividad_id')
                ->select(
                    'estudiantes.codigo',
                    DB::raw('SUM(estudiantes_actividades.horas) as total_horas')
                )
                ->whereMonth('estudiantes_actividades.created_at', $currentMonth)
                ->whereYear('estudiantes_actividades.created_at', $currentYear)
                ->where('estudiantes_actividades.estado', '1')
                ->groupBy('estudiantes.id', 'estudiantes.codigo')
                ->having('total_horas', '>=', 100)
                ->orderByDesc('total_horas')
                ->get();

        } else {
            // Implementar las mismas consultas pero filtradas por escuela
            $escuelaIds = $roles->pluck('escuela_id')->unique()->filter();
            
            // Aplicar los mismos filtros pero añadiendo whereIn('escuela_id', $escuelaIds)
            // ... [código similar al anterior pero con filtros de escuela]
        }

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        return [
            'chartData' => [
                'actividadesMes' => [
                    'labels' => $actividadesMes->pluck('fecha')->map(function($fecha) {
                        return Carbon::parse($fecha)->format('d M');
                    }),
                    'series' => [$actividadesMes->pluck('total')]
                ],
                'inscripciones' => [
                    'labels' => $inscripcionesPorActividad->pluck('motivo'),
                    'series' => [$inscripcionesPorActividad->pluck('total_inscritos')]
                ],
                'estudiantesMes' => [
                    'labels' => $estudiantesPorMes->pluck('mes')->map(function($mes) use ($meses) {
                        return $meses[$mes - 1];
                    }),
                    'series' => [$estudiantesPorMes->pluck('total')]
                ],
                'distribucionHoras' => [
                    'labels' => $distribucionHoras->pluck('rango'),
                    'series' => $distribucionHoras->pluck('total')
                ],
                'estudiantesHoras' => [
                    'labels' => $estudiantesHoras->pluck('codigo'),
                    'series' => [$estudiantesHoras->pluck('total_horas')]
                ]
            ]
        ];
    }
}
?>

<div x-data class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-gray-100">
    <!-- Gráfico 1: Actividades del Mes -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Actividades Realizadas (Mes Actual)</h3>
        <div id="actividadesMesChart" class="h-[400px]"></div>
    </div>

    <!-- Gráfico 2: Estudiantes por Actividad -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Top 10 Actividades por Inscripciones</h3>
        <div id="inscripcionesChart" class="h-[400px]"></div>
    </div>

    <!-- Gráfico 3: Estudiantes Nuevos por Mes -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Estudiantes Registrados por Mes</h3>
        <div id="estudiantesMesChart" class="h-[400px]"></div>
    </div>

    <!-- Gráfico 4: Distribución de Horas -->
    <div class="bg-white rounded-lg shadow-md p-4">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Distribución de Duración de Actividades</h3>
        <div id="distribucionHorasChart" class="h-[400px]"></div>
    </div>
</div>

<script>
document.addEventListener('livewire:navigated', function () {
    // Gráfico 1: Actividades del Mes (Línea)
    const actividadesMesChart = new ApexCharts(document.querySelector("#actividadesMesChart"), {
        series: [{
            name: 'Actividades',
            data: @json($chartData['actividadesMes']['series'][0])
        }],
        chart: {
            type: 'line',
            height: 400,
            toolbar: {
                show: false
            }
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        markers: {
            size: 4
        },
        xaxis: {
            categories: @json($chartData['actividadesMes']['labels']),
            labels: {
                rotate: -45
            }
        },
        colors: ['#3B82F6']
    });
    actividadesMesChart.render();

    // Gráfico 2: Inscripciones por Actividad (Barras horizontales)
    const inscripcionesChart = new ApexCharts(document.querySelector("#inscripcionesChart"), {
        series: [{
            name: 'Estudiantes Inscritos',
            data: @json($chartData['inscripciones']['series'][0])
        }],
        chart: {
            type: 'bar',
            height: 400,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4
            }
        },
        xaxis: {
            categories: @json($chartData['inscripciones']['labels'])
        },
        colors: ['#10B981']
    });
    inscripcionesChart.render();

    // Gráfico 3: Estudiantes por Mes (Columnas)
    const estudiantesMesChart = new ApexCharts(document.querySelector("#estudiantesMesChart"), {
        series: [{
            name: 'Estudiantes Nuevos',
            data: @json($chartData['estudiantesMes']['series'][0])
        }],
        chart: {
            type: 'bar',
            height: 400,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                dataLabels: {
                    position: 'top'
                }
            }
        },
        dataLabels: {
            enabled: true,
            offsetY: -20,
            style: {
                fontSize: '12px',
                colors: ["#304758"]
            }
        },
        xaxis: {
            categories: @json($chartData['estudiantesMes']['labels'])
        },
        colors: ['#8B5CF6']
    });
    estudiantesMesChart.render();

    // Gráfico 4: Distribución de Horas (Dona)
    const distribucionHorasChart = new ApexCharts(document.querySelector("#distribucionHorasChart"), {
        series: @json($chartData['distribucionHoras']['series']),
        chart: {
            type: 'donut',
            height: 400
        },
        labels: @json($chartData['distribucionHoras']['labels']),
        colors: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444'],
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
        }],
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Total Actividades'
                        }
                    }
                }
            }
        }
    });
    distribucionHorasChart.render();


    /* HOLA */
    actividadesMesChart.updateSeries([{
        data: @json($chartData['actividadesMes']['series'][0])
    }]);
    
    inscripcionesChart.updateSeries([{
        data: @json($chartData['inscripciones']['series'][0])
    }]);
    
    estudiantesMesChart.updateSeries([{
        data: @json($chartData['estudiantesMes']['series'][0])
    }]);
    
    distribucionHorasChart.updateSeries(@json($chartData['distribucionHoras']['series']));
});
</script>