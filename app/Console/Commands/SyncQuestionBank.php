<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\QuestionBank;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SyncQuestionBank extends Command
{
    protected $signature = 'trivia:sync-questions {--apply : Replace the bank and reset current progress; default is a read-only dry run}';

    protected $description = 'Replace questions and reset tournament answers/scores, preserving teams, members and archived events';

    private const TEAM_RESET = [
        'puntaje_total' => 0, 'aciertos_totales' => 0, 'preguntas_contestadas' => 0,
        'tiempo_acumulado_ms' => 0, 'fase_actual' => 1,
        'en_desempate' => false, 'finalizo_carrera' => false,
        'racha_actual' => 0, 'racha_maxima' => 0, 'salidas_pantalla' => 0,
    ];

    public function handle(QuestionBank $bank): int
    {
        try {
            $records = $bank->records();
            $summary = DB::transaction(fn (): array => $this->synchronize($bank, $records, (bool) $this->option('apply')));
            $this->line(($this->option('apply') ? 'APPLIED' : 'DRY RUN').': '.collect($summary)
                ->map(fn (int $count, string $name): string => $name.'='.$count)->implode(' '));

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function synchronize(QuestionBank $bank, array $records, bool $apply): array
    {
        $configurationQuery = DB::table('configuracion_eventos');
        if ($apply) {
            $configurationQuery->lockForUpdate();
        }
        $configurations = $configurationQuery->get();
        if ($configurations->count() !== 1 || ! in_array($configurations->first()->estado_juego, ['registro', 'finalizado'], true)) {
            throw new RuntimeException('Question-bank changes require one configuration and an inactive game.');
        }
        $configuration = $configurations->first();
        $allModules = DB::table('modulos')->get();
        $allLevels = DB::table('niveles')->get();
        $modules = $allModules->groupBy('nombre');
        $levels = $allLevels->groupBy('orden_dificultad');
        $expected = [];
        foreach ($records as $record) {
            if (($modules[$record['modulo']] ?? collect())->count() !== 1
                || ($levels[$record['level_order']] ?? collect())->count() !== 1) {
                throw new RuntimeException('Missing or ambiguous module/stage: '.$record['modulo'].' / '.$record['level_order']);
            }
            $expected[$bank->fingerprint($record, $record['modulo'], $record['level_order'])] = [
                'modulo_id' => $modules[$record['modulo']]->first()->id,
                'nivel_id' => $levels[$record['level_order']]->first()->id,
                'enunciado' => $record['enunciado'],
                'opcion_a' => $record['opcion_a'], 'opcion_b' => $record['opcion_b'],
                'opcion_c' => $record['opcion_c'], 'opcion_d' => $record['opcion_d'],
                'respuesta_correcta' => $record['respuesta_correcta'],
                'es_amena' => $record['es_amena'], 'activa' => true,
            ];
        }
        $questionsQuery = DB::table('preguntas');
        $teamsQuery = DB::table('equipos');
        if ($apply) {
            $questionsQuery->lockForUpdate();
            $teamsQuery->lockForUpdate();
        }
        $questions = $questionsQuery->get();
        $teams = $teamsQuery->get();
        $modulesById = $allModules->keyBy('id');
        $levelsById = $allLevels->keyBy('id');
        $present = [];
        $bankCurrent = $questions->count() === count($expected);
        foreach ($questions as $question) {
            $module = $modulesById[$question->modulo_id] ?? null;
            $level = $levelsById[$question->nivel_id] ?? null;
            if (! $module || ! $level) {
                throw new RuntimeException('Unresolved question mapping: '.$question->id);
            }
            $fingerprint = $bank->fingerprint((array) $question, $module->nombre, (int) $level->orden_dificultad);
            $bankCurrent = $bankCurrent && (bool) $question->activa && isset($expected[$fingerprint]) && ! isset($present[$fingerprint]);
            $present[$fingerprint] = true;
        }
        $answers = DB::table('respuestas_equipos')->count();
        $deliveries = DB::table('entregas')->count();
        $needsReset = ! $bankCurrent || $answers > 0 || $deliveries > 0
            || $configuration->estado_juego !== 'registro'
            || $configuration->fin_evento_at !== null || $configuration->pausado_at !== null;
        foreach ($teams as $team) {
            foreach (self::TEAM_RESET as $column => $value) {
                $needsReset = $needsReset || (int) $team->{$column} !== (int) $value;
            }
        }
        if ($apply && $needsReset) {
            // DELETE stays inside the transaction; TRUNCATE would implicitly commit.
            DB::table('respuestas_equipos')->delete();
            DB::table('entregas')->delete();
            $now = now();
            if (! $bankCurrent) {
                DB::table('preguntas')->delete();
                foreach (array_chunk(array_values($expected), 50) as $chunk) {
                    DB::table('preguntas')->insert(array_map(fn (array $row): array => [...$row, 'created_at' => $now, 'updated_at' => $now], $chunk));
                }
            }
            if ($teams->isNotEmpty()) {
                DB::table('equipos')->update([...self::TEAM_RESET, 'updated_at' => $now]);
            }
            DB::table('configuracion_eventos')->where('id', $configuration->id)->update([
                'estado_juego' => 'registro', 'semilla_partida' => Str::random(16),
                'fin_evento_at' => null, 'pausado_at' => null, 'updated_at' => $now,
            ]);
        }

        return [
            'replace' => $bankCurrent ? 0 : count($expected),
            'remove' => $bankCurrent ? 0 : $questions->count(),
            'reset_teams' => $needsReset ? $teams->count() : 0,
            'clear_answers' => $answers, 'clear_deliveries' => $deliveries,
        ];
    }
}
