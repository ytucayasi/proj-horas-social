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

new #[Layout('layouts.app')] class extends Component {
    public $chartData;

    public function mount()
    {
        $this->loadChartData();
        $this->dispatch('$refresh');
    }
    public function loadChartData()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // First Dashboard Queries
        $actividadesMes = Actividad::selectRaw('DATE(fecha_inicio) as fecha, COUNT(*) as total')
            ->whereMonth('fecha_inicio', $currentMonth)
            ->whereYear('fecha_inicio', $currentYear)
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $inscripcionesPorActividad = DB::table('estudiantes_actividades')
            ->join('actividades', 'actividades.id', '=', 'estudiantes_actividades.actividad_id')
            ->select('actividades.motivo', DB::raw('COUNT(estudiantes_actividades.id) as total_inscritos'))
            ->where('estudiantes_actividades.estado', '1')
            ->groupBy('actividades.id', 'actividades.motivo')
            ->orderByDesc('total_inscritos')
            ->limit(10)
            ->get();

        $estudiantesPorMes = Estudiante::selectRaw('MONTH(created_at) as mes, COUNT(*) as total')
            ->whereYear('created_at', $currentYear)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
        // Continuing Admin queries...
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

        // Second Dashboard Queries
        $estudiantesPorEscuela = DB::table('estudiantes')
            ->join('escuelas', 'estudiantes.escuela_id', '=', 'escuelas.id')
            ->select('escuelas.nombre', DB::raw('count(*) as total'))
            ->where('estudiantes.estado', '1')
            ->groupBy('escuelas.id', 'escuelas.nombre')
            ->get();

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
        $estadoActividades = DB::table('actividades')
            ->select(
                DB::raw('CASE WHEN publicado = 2 THEN "Publicadas" ELSE "No Publicadas" END as estado'),
                DB::raw('count(*) as total')
            )
            ->groupBy('publicado')
            ->get();

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

        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $this->chartData = [
            'actividadesMes' => [
                'labels' => $actividadesMes->pluck('fecha')->map(function ($fecha) {
                    return Carbon::parse($fecha)->format('d M');
                }),
                'series' => [$actividadesMes->pluck('total')]
            ],
            'inscripciones' => [
                'labels' => $inscripcionesPorActividad->pluck('motivo'),
                'series' => [$inscripcionesPorActividad->pluck('total_inscritos')]
            ],
            'estudiantesMes' => [
                'labels' => $estudiantesPorMes->pluck('mes')->map(function ($mes) use ($meses) {
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
            ],
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
        ];
    }
}
?>
<div>
    <div class="p-6 bg-white shadow-sm mb-6 rounded-lg">
        <h2 class="text-2xl font-bold text-gray-800">Panel de Control</h2>
        <p class="text-gray-600">Resumen de estadísticas</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-6 bg-gray-100">
        <!-- Sección 1: Gráficos del primer dashboard -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Actividades Realizadas (Mes Actual)</h3>
            <div id="actividadesMesChart" class="h-[400px]"></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Top 10 Actividades por Inscripciones</h3>
            <div id="inscripcionesChart" class="h-[400px]"></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Estudiantes Registrados por Mes</h3>
            <div id="estudiantesMesChart" class="h-[400px]"></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Distribución de Duración de Actividades</h3>
            <div id="distribucionHorasChart" class="h-[400px]"></div>
        </div>

        <!-- Sección 2: Gráficos del segundo dashboard -->
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Estudiantes por Escuela</h3>
            <div id="estudiantesEscuelaChart" class="h-[400px]"></div>
        </div>
        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Top 5 Actividades</h3>
            <div id="actividadesTopChart" class="h-[400px]"></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Estado de Actividades</h3>
            <div id="estadoActividadesChart" class="h-[400px]"></div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-4">
            <h3 class="text-lg font-semibold mb-4 text-gray-700">Promedio de Horas por Escuela</h3>
            <div id="promedioHorasChart" class="h-[400px]"></div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {
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
                    toolbar: { show: false }
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
                    labels: { rotate: -45 }
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
                    toolbar: { show: false }
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
                    toolbar: { show: false }
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
                        chart: { width: 200 },
                        legend: { position: 'bottom' }
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

            // Gráfico 5: Estudiantes por Escuela
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
                        style: { fontSize: '12px' }
                    }
                },
                colors: ['#3B82F6']
            });
            estudiantesEscuelaChart.render();

            // Gráfico 6: Top 5 Actividades (Dona)
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
                        chart: { width: 200 },
                        legend: { position: 'bottom' }
                    }
                }]
            });
            actividadesTopChart.render();

            // Gráfico 7: Estado de Actividades (Pie)
            const estadoActividadesChart = new ApexCharts(document.querySelector("#estadoActividadesChart"), {
                series: @json($chartData['estadoActividades']['series']),
                chart: {
                    type: 'pie',
                    height: 400
                },
                labels: @json($chartData['estadoActividades']['labels']),
                colors: ['#EF4444', '#10B981'],
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: { width: 200 },
                        legend: { position: 'bottom' }
                    }
                }]
            });
            estadoActividadesChart.render();

            // Gráfico 8: Promedio de Horas (Barras horizontales)
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

            // Update charts data when needed
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
    });
</script>