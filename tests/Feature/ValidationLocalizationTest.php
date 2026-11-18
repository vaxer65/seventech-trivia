<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ValidationLocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('es');
        ConfiguracionEvento::create([
            'nombre_evento' => 'Validation test',
            'estado_juego' => 'registro',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos' => 15,
            'semilla_partida' => 'validation-test',
        ]);
    }

    #[DataProvider('invalidRequests')]
    public function test_http_validation_returns_readable_spanish_messages(string $endpoint, array $payload, array $expectedErrors): void
    {
        $response = $this->withSession(['es_admin_seventech' => true])
            ->postJson($endpoint, $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(array_keys($expectedErrors));

        foreach ($expectedErrors as $attribute => $message) {
            $this->assertContains($message, $response->json('errors')[$attribute]);
        }

        $this->assertStringNotContainsString('validation.', $response->getContent());
        $this->assertStringNotContainsString('(and', $response->json('message'));
        $this->assertDatabaseCount('equipos', 0);
        $this->assertDatabaseHas('configuracion_eventos', ['estado_juego' => 'registro']);
    }

    public static function invalidRequests(): array
    {
        $configuration = ['estado_juego' => 'registro', 'max_integrantes_equipo' => 5, 'max_equipos_permitidos' => 30];

        return [
            'empty registration' => ['/api/registrar-equipo', [], [
                'nombre_equipo' => 'El campo nombre del equipo es obligatorio.',
                'integrantes' => 'El campo integrantes es obligatorio.',
            ]],
            'short team name' => ['/api/registrar-equipo', ['nombre_equipo' => 'A', 'integrantes' => ['Ana Pérez', 'Luis López']], [
                'nombre_equipo' => 'El campo nombre del equipo debe tener al menos 2 caracteres.',
            ]],
            'long team and member names' => ['/api/registrar-equipo', ['nombre_equipo' => str_repeat('A', 41), 'integrantes' => ['Ana '.str_repeat('P', 61), 'Luis López']], [
                'nombre_equipo' => 'El campo nombre del equipo no debe superar los 40 caracteres.',
                'integrantes.0' => 'El campo nombre y apellido del integrante no debe superar los 60 caracteres.',
            ]],
            'existing member minimum message' => ['/api/registrar-equipo', ['nombre_equipo' => 'Team', 'integrantes' => ['Ana Pérez']], [
                'integrantes' => 'El equipo debe tener como mínimo 2 integrantes.',
            ]],
            'recovery required fields' => ['/api/recuperar-sesion', [], [
                'nombre_equipo' => 'El campo nombre del equipo es obligatorio.',
                'pin_recuperacion' => 'El campo PIN de recuperación es obligatorio.',
            ]],
            'recovery PIN length' => ['/api/recuperar-sesion', ['nombre_equipo' => 'Team', 'pin_recuperacion' => '123'], [
                'pin_recuperacion' => 'El campo PIN de recuperación debe tener 4 caracteres.',
            ]],
            'recovery PIN type' => ['/api/recuperar-sesion', ['nombre_equipo' => 'Team', 'pin_recuperacion' => 1234], [
                'pin_recuperacion' => 'El campo PIN de recuperación debe ser una cadena de texto.',
            ]],
            'configuration invalid values' => ['/admin/configuracion', ['estado_juego' => 'invalid', 'max_integrantes_equipo' => 11, 'max_equipos_permitidos' => 'many', 'modulo_activo_id' => 999], [
                'estado_juego' => 'El valor seleccionado para estado del juego no es válido.',
                'max_integrantes_equipo' => 'El campo máximo de integrantes por equipo debe estar entre 2 y 10.',
                'max_equipos_permitidos' => 'El campo máximo de equipos permitidos debe ser un número entero.',
                'modulo_activo_id' => 'El valor seleccionado para módulo activo no es válido.',
            ]],
            'duration minimum' => ['/admin/configuracion', $configuration + ['tiempo_total_minutos' => 0], [
                'tiempo_total_minutos' => 'El campo duración total en minutos debe ser al menos 1.',
            ]],
            'duration maximum' => ['/admin/configuracion', $configuration + ['tiempo_total_minutos' => 601], [
                'tiempo_total_minutos' => 'El campo duración total en minutos no debe superar 600.',
            ]],
            'question fields' => ['/admin/pregunta', ['modulo_id' => 999, 'nivel_id' => 999, 'enunciado' => ['invalid'], 'respuesta_correcta' => 'z'], [
                'modulo_id' => 'El valor seleccionado para módulo no es válido.',
                'nivel_id' => 'El valor seleccionado para etapa no es válido.',
                'enunciado' => 'El campo enunciado debe ser una cadena de texto.',
                'opcion_a' => 'El campo opción A es obligatorio.',
                'respuesta_correcta' => 'El valor seleccionado para respuesta correcta no es válido.',
            ]],
            'import requires file' => ['/admin/preguntas/importar', ['archivo' => 'invalid'], [
                'archivo' => 'El campo archivo de importación debe ser un archivo.',
            ]],
            'tiebreak requires array' => ['/admin/desempate', ['equipos_desempate' => 'invalid'], [
                'equipos_desempate' => 'El campo equipos de desempate debe ser una lista.',
            ]],
            'tiebreak team identifiers' => ['/admin/desempate', ['equipos_desempate' => ['invalid']], [
                'equipos_desempate.0' => 'El campo equipo de desempate debe ser un número entero.',
            ]],
        ];
    }

    public function test_import_file_limit_uses_spanish_file_size_message(): void
    {
        $response = $this->withSession(['es_admin_seventech' => true])
            ->postJson('/admin/preguntas/importar', ['archivo' => UploadedFile::fake()->create('questions.json', 5121)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('archivo');

        $this->assertContains('El campo archivo de importación no debe superar los 5120 kilobytes.', $response->json('errors.archivo'));
        $this->assertStringNotContainsString('validation.', $response->getContent());
        $this->assertDatabaseCount('preguntas', 0);
    }

    public function test_duplicate_team_name_preserves_existing_custom_message(): void
    {
        Equipo::create(['nombre_equipo' => 'Existing team', 'token_sesion' => str_repeat('x', 60), 'pin_recuperacion' => '1234']);

        $this->postJson('/api/registrar-equipo', ['nombre_equipo' => 'Existing team', 'integrantes' => ['Ana Pérez', 'Luis López']])
            ->assertUnprocessable()
            ->assertJsonPath('errors.nombre_equipo.0', '¡Ese nombre de equipo ya está en uso! Elige otro.');

        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseCount('integrantes', 0);
    }

    public function test_multiple_error_summaries_use_spanish_singular_and_plural(): void
    {
        $this->postJson('/api/recuperar-sesion', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El campo nombre del equipo es obligatorio. (y 1 error más)');

        $this->withSession(['es_admin_seventech' => true])
            ->postJson('/admin/configuracion', [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El campo estado del juego es obligatorio. (y 2 errores más)');
    }
}
