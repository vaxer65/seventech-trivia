<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class QuestionBankSyncTest extends TestCase
{
    use RefreshDatabase;

    private Pregunta $legacy;

    protected function setUp(): void
    {
        parent::setUp();
        $source = require database_path('seeders/data/etapa1_faciles.php');
        foreach (array_unique(array_column($source, 'modulo')) as $name) {
            Modulo::create(['nombre' => $name]);
        }
        for ($order = 1; $order <= 4; $order++) {
            Nivel::create([
                'nombre' => 'Stage '.$order, 'orden_dificultad' => $order,
                'cantidad_preguntas' => 5, 'puntos_por_acierto' => 100,
                'tiempo_limite_seg' => 25, 'es_desempate' => $order === 4,
            ]);
        }
        ConfiguracionEvento::create(['estado_juego' => 'finalizado', 'semilla_partida' => 'preserved-seed']);
        $this->legacy = Pregunta::create([
            'modulo_id' => Modulo::where('nombre', 'Fundamentos de Programación & Lógica')->value('id'),
            'nivel_id' => Nivel::where('orden_dificultad', 1)->value('id'),
            'enunciado' => '¿Cómo se llama el espacio en memoria donde un programa guarda un valor que puede cambiar durante su ejecución?',
            'opcion_a' => 'Variable', 'opcion_b' => 'Comentario',
            'opcion_c' => 'Compilador', 'opcion_d' => 'Librería',
            'respuesta_correcta' => 'a', 'es_amena' => false, 'activa' => true,
        ]);
    }

    public function test_default_dry_run_does_not_mutate_stored_questions(): void
    {
        $before = $this->snapshot();
        $this->artisan('trivia:sync-questions')->expectsOutputToContain('DRY RUN')->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_apply_replaces_bank_resets_progress_preserves_teams_and_is_idempotent(): void
    {
        $team = Equipo::create(['nombre_equipo' => 'Historical team', 'token_sesion' => str_repeat('x', 60), 'puntaje_total' => 400, 'pin_recuperacion' => '1234', 'fase_actual' => 3, 'aciertos_totales' => 8, 'preguntas_contestadas' => 10, 'tiempo_acumulado_ms' => 12345, 'en_desempate' => true, 'finalizo_carrera' => true, 'racha_actual' => 3, 'racha_maxima' => 7, 'salidas_pantalla' => 2]);
        DB::table('integrantes')->insert([['equipo_id' => $team->id, 'nombre_completo' => 'Test Player One'], ['equipo_id' => $team->id, 'nombre_completo' => 'Test Player Two']]);
        $membersBefore = DB::table('integrantes')->orderBy('id')->get()->toJson();
        DB::table('respuestas_equipos')->insert([
            'equipo_id' => $team->id, 'pregunta_id' => $this->legacy->id,
            'nivel_id' => $this->legacy->nivel_id, 'opcion_elegida' => 'a',
            'es_correcta' => true, 'puntos_ganados' => 400,
        ]);
        DB::table('entregas')->insert(['equipo_id' => $team->id, 'pregunta_id' => $this->legacy->id, 'entregada_at' => now()]);
        $teamBefore = $team->getRawOriginal();
        $this->artisan('trivia:sync-questions', ['--apply' => true])->assertSuccessful();
        $this->assertNull($this->legacy->fresh());
        $this->assertDatabaseCount('preguntas', 400);
        $this->assertSame(400, Pregunta::where('activa', true)->count());
        $this->assertDatabaseCount('respuestas_equipos', 0);
        $this->assertDatabaseCount('entregas', 0);
        foreach (['puntaje_total', 'aciertos_totales', 'preguntas_contestadas', 'tiempo_acumulado_ms', 'racha_actual', 'racha_maxima', 'salidas_pantalla'] as $field) {
            $this->assertSame(0, $team->fresh()->{$field});
        }
        $this->assertSame(1, $team->fresh()->fase_actual);
        $this->assertFalse($team->fresh()->en_desempate);
        $this->assertFalse($team->fresh()->finalizo_carrera);
        $this->assertSame('1234', $team->fresh()->pin_recuperacion);
        $this->assertSame($membersBefore, DB::table('integrantes')->orderBy('id')->get()->toJson());
        $this->assertSame($teamBefore['token_sesion'], $team->fresh()->getRawOriginal('token_sesion'));
        $this->assertSame($teamBefore['nombre_equipo'], $team->fresh()->nombre_equipo);
        $this->assertSame('registro', ConfiguracionEvento::first()->estado_juego);
        $this->assertNotSame('preserved-seed', ConfiguracionEvento::first()->semilla_partida);
        $before = $this->snapshot();
        $this->artisan('trivia:sync-questions', ['--apply' => true])->assertSuccessful();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_replacement_removes_the_entire_previous_bank(): void
    {
        $this->legacy->update(['opcion_a' => 'Customized answer']);
        $custom = Pregunta::create([
            ...$this->legacy->getAttributes(), 'id' => null,
            'enunciado' => 'A custom historical question?', 'activa' => false,
        ]);
        $this->artisan('trivia:sync-questions', ['--apply' => true])->assertSuccessful();
        $this->assertNull($this->legacy->fresh());
        $this->assertNull($custom->fresh());
        $this->assertDatabaseCount('preguntas', 400);
    }

    public function test_replacement_restores_a_complete_active_catalog(): void
    {
        $this->artisan('trivia:sync-questions', ['--apply' => true])->assertSuccessful();
        Pregunta::where('activa', true)->firstOrFail()->update(['activa' => false]);
        $before = $this->snapshot();
        $this->artisan('trivia:sync-questions', ['--apply' => true])->assertSuccessful();
        $this->assertSame(400, Pregunta::where('activa', true)->count());
        $this->assertDatabaseCount('preguntas', 400);
    }

    public function test_running_game_states_refuse_changes(): void
    {
        foreach (['en_curso', 'pausado', 'desempate', 'unknown-state'] as $state) {
            ConfiguracionEvento::first()->update(['estado_juego' => $state]);
            $before = $this->snapshot();
            $this->artisan('trivia:sync-questions', ['--apply' => true])->expectsOutputToContain('inactive game')->assertFailed();
            $this->assertSame($before, $this->snapshot());
        }
    }

    public function test_ambiguous_module_mapping_refuses_changes(): void
    {
        Modulo::create(['nombre' => Modulo::first()->nombre]);
        $before = $this->snapshot();
        $this->artisan('trivia:sync-questions', ['--apply' => true])->expectsOutputToContain('ambiguous module/stage')->assertFailed();
        $this->assertSame($before, $this->snapshot());
    }

    public function test_failed_insert_rolls_back_the_whole_sync(): void
    {
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        $isolatedDispatcher = clone $dispatcher;
        $isolatedDispatcher->listen(QueryExecuted::class, function (QueryExecuted $query): void {
            if (str_starts_with($query->sql, 'insert into `preguntas`')) {
                throw new RuntimeException('Simulated insert failure');
            }
        });
        $before = $this->snapshot();
        $connection->setEventDispatcher($isolatedDispatcher);
        try {
            $this->artisan('trivia:sync-questions', ['--apply' => true])
                ->expectsOutputToContain('Simulated insert failure')->assertFailed();
        } finally {
            $connection->setEventDispatcher($dispatcher);
        }
        $this->assertSame($before, $this->snapshot());
    }

    private function snapshot(): string
    {
        $snapshot = [];
        foreach (['preguntas', 'equipos', 'integrantes', 'respuestas_equipos', 'entregas', 'configuracion_eventos', 'historial_eventos'] as $table) {
            $snapshot[$table] = DB::table($table)->orderBy('id')->get()->all();
        }

        return json_encode($snapshot, JSON_THROW_ON_ERROR);
    }
}
