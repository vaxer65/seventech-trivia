<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AdvancedQuestionCatalogTest extends TestCase
{
    public static function catalogs(): array
    {
        return [
            'analysis' => ['etapa3_dificiles.php', 1],
            'expert' => ['muerte_subita.php', 0],
        ];
    }

    #[DataProvider('catalogs')]
    public function test_catalog_has_balanced_complete_categories(string $file, int $funPerModule): void
    {
        $rows = require dirname(__DIR__, 2).'/database/seeders/data/'.$file;
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
        $this->assertCount(100, $rows);
        $counts = array_fill_keys($modules, 0);
        $fun = array_fill_keys($modules, 0);
        $letters = array_fill_keys(['a', 'b', 'c', 'd'], 0);
        foreach ($rows as $row) {
            $this->assertArrayHasKey($row['modulo'], $counts);
            $this->assertIsBool($row['es_amena']);
            $this->assertArrayHasKey($row['respuesta_correcta'], $letters);
            $counts[$row['modulo']]++;
            $fun[$row['modulo']] += (int) $row['es_amena'];
            $letters[$row['respuesta_correcta']]++;
        }
        $this->assertSame(array_fill_keys($modules, 10), $counts);
        $this->assertSame(array_fill_keys($modules, $funPerModule), $fun);
        $this->assertSame(['a' => 25, 'b' => 25, 'c' => 25, 'd' => 25], $letters);
    }

    public static function catalogFiles(): array
    {
        return array_map(static fn (array $catalog): array => [$catalog[0]], self::catalogs());
    }

    #[DataProvider('catalogFiles')]
    public function test_choices_fit_mobile_and_do_not_signal_the_answer(string $file): void
    {
        $rows = require dirname(__DIR__, 2).'/database/seeders/data/'.$file;
        $longestByModule = [];
        foreach ($rows as $row) {
            $choices = [];
            foreach (['a', 'b', 'c', 'd'] as $letter) {
                $choice = $row['opcion_'.$letter];
                $this->assertSame(trim($choice), $choice, $row['enunciado']);
                $this->assertNotSame('', $choice);
                $this->assertDoesNotMatchRegularExpression('/[\r\n]/u', $choice);
                $this->assertLessThanOrEqual(20, mb_strlen($choice), $choice);
                $choices[$letter] = mb_strlen($choice);
            }
            $this->assertCount(4, array_unique(array_map(fn ($letter) => mb_strtolower($row['opcion_'.$letter]), ['a', 'b', 'c', 'd'])));
            $this->assertLessThanOrEqual(6, max($choices) - min($choices), $row['enunciado']);
            $longestByModule[$row['modulo']] ??= 0;
            if ($choices[$row['respuesta_correcta']] === max($choices) && count(array_keys($choices, max($choices))) === 1) {
                $longestByModule[$row['modulo']]++;
            }
        }
        $this->assertLessThanOrEqual(35, array_sum($longestByModule));
        foreach ($longestByModule as $module => $count) {
            $this->assertLessThanOrEqual(5, $count, $module);
        }
    }

    public function test_advanced_stems_are_distinct(): void
    {
        $stems = [];
        foreach (['etapa3_dificiles.php', 'muerte_subita.php'] as $file) {
            foreach (require dirname(__DIR__, 2).'/database/seeders/data/'.$file as $row) {
                $this->assertNotSame('', trim($row['enunciado']));
                $stems[] = mb_strtolower(trim($row['enunciado']));
            }
        }
        $this->assertCount(200, array_unique($stems));
    }
}
