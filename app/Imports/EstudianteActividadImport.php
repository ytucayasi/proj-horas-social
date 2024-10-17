<?php
namespace App\Imports;

use App\Models\Estudiante;
use App\Models\EstudianteActividad;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EstudianteActividadImport implements ToModel, WithHeadingRow
{
    protected $actividadId;
    protected $horasPorEstudiante = [];

    public function __construct($actividadId)
    {
        $this->actividadId = $actividadId;
    }

    public function model(array $row)
    {
        Log::info("Procesando fila: " . json_encode($row));
        try {
            $estudiante = Estudiante::where('codigo', $row['codigo_estudiante'])->first();
            if (!$estudiante) {
                Log::warning("Estudiante no encontrado: " . $row['codigo_estudiante']);
                return null;
            }

            // Acumular horas para este estudiante
            if (!isset($this->horasPorEstudiante[$estudiante->id])) {
                $this->horasPorEstudiante[$estudiante->id] = 0;
            }
            $this->horasPorEstudiante[$estudiante->id] += $row['horas'];

            // Actualizar horas_base del estudiante
            $estudiante->increment('horas_base', $row['horas']);

            // Buscar o crear el registro de EstudianteActividad
            $estudianteActividad = EstudianteActividad::updateOrCreate(
                [
                    'estudiante_id' => $estudiante->id,
                    'actividad_id' => $this->actividadId
                ],
                [
                    'horas' => DB::raw('horas + ' . $row['horas']),
                    'estado' => '1'
                ]
            );

            Log::info("EstudianteActividad actualizado: " . $estudianteActividad->id . ", Horas totales: " . $this->horasPorEstudiante[$estudiante->id]);
            Log::info("Horas base del estudiante actualizadas: " . $estudiante->horas_base);

            return $estudianteActividad;
        } catch (\Exception $e) {
            Log::error("Error procesando fila: " . $e->getMessage());
            return null;
        }
    }

    public function __destruct()
    {
        Log::info("Resumen de horas por estudiante: " . json_encode($this->horasPorEstudiante));
    }
}