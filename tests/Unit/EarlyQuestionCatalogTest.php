<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class EarlyQuestionCatalogTest extends TestCase
{
    public function test_early_catalogs_have_balanced_compact_complete_records(): void
    {
        $root = dirname(__DIR__, 2);
        $modules = [
            'Fundamentos de Programación & Lógica',
            'Desarrollo Web Frontend (HTML, CSS, JS)',
            'Backend, APIs, PHP & Laravel',
            'Bases de Datos Relacionales & SQL',
            'Estructuras de Datos, Algoritmos & POO',
            'Sistemas Operativos, Linux & DevOps',
            'Redes de Datos, Protocolos & Cloud',
            'Ciberseguridad, Hacking Ético & Criptografía',
            'Inteligencia Artificial, Datos & Tendencias Tech',
            'Cultura Geek, Historia de la Computación & Gaming',
        ];
        $statements = [];
        foreach (['etapa1_faciles.php', 'etapa2_medias.php'] as $file) {
            $questions = require $root.'/database/seeders/data/'.$file;
            self::assertCount(100, $questions, $file);
            $letters = array_fill_keys(['a', 'b', 'c', 'd'], 0);
            $counts = $amena = $longest = array_fill_keys($modules, 0);
            foreach ($questions as $question) {
                self::assertSame(['modulo', 'enunciado', 'opcion_a', 'opcion_b', 'opcion_c', 'opcion_d', 'respuesta_correcta', 'es_amena'], array_keys($question));
                self::assertContains($question['modulo'], $modules);
                self::assertIsBool($question['es_amena']);
                self::assertNotContains($question['enunciado'], $statements);
                $statements[] = $question['enunciado'];
                $options = array_map(fn ($letter) => $question['opcion_'.$letter], array_keys($letters));
                self::assertCount(4, array_unique($options), $question['enunciado']);
                $lengths = [];
                foreach ($options as $option) {
                    self::assertSame(trim($option), $option);
                    self::assertDoesNotMatchRegularExpression('/[\r\n]/u', $option);
                    $lengths[] = mb_strlen($option);
                    self::assertGreaterThan(0, mb_strlen($option));
                    self::assertLessThanOrEqual(20, mb_strlen($option), $question['enunciado'].' / '.$option);
                }
                self::assertLessThanOrEqual(6, max($lengths) - min($lengths), $question['enunciado']);
                self::assertArrayHasKey($question['respuesta_correcta'], $letters);
                $letters[$question['respuesta_correcta']]++;
                $counts[$question['modulo']]++;
                $amena[$question['modulo']] += (int) $question['es_amena'];
                $correctLength = mb_strlen($question['opcion_'.$question['respuesta_correcta']]);
                if ($correctLength === max($lengths) && count(array_filter($lengths, fn ($length) => $length === $correctLength)) === 1) {
                    $longest[$question['modulo']]++;
                }
            }
            self::assertSame(['a' => 25, 'b' => 25, 'c' => 25, 'd' => 25], $letters);
            foreach ($modules as $module) {
                self::assertSame(10, $counts[$module], $module);
                self::assertSame(1, $amena[$module], $module);
                self::assertLessThanOrEqual(5, $longest[$module], $module);
            }
            self::assertLessThanOrEqual(35, array_sum($longest), $file);
        }
    }
}
