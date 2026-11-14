<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class QuestionBank
{
    private const FILES = ['etapa1_faciles.php', 'etapa2_medias.php', 'etapa3_dificiles.php', 'muerte_subita.php'];

    public function records(): array
    {
        $records = [];
        $statements = [];
        $modules = [];
        foreach (self::FILES as $index => $file) {
            $questions = require database_path('seeders/data/'.$file);
            if (count($questions) !== 100) {
                throw new RuntimeException($file.' must contain 100 questions.');
            }
            $groups = [];
            $letters = array_fill_keys(['a', 'b', 'c', 'd'], 0);
            $longestCorrect = 0;
            foreach ($questions as $question) {
                $options = array_map(fn (string $key): string => $question['opcion_'.$key], ['a', 'b', 'c', 'd']);
                $lengths = array_map('mb_strlen', $options);
                $statement = mb_strtolower(trim($question['enunciado']));
                $answer = $question['respuesta_correcta'];
                if ($statement === '' || isset($statements[$statement]) || ! isset($letters[$answer])
                    || count(array_unique(array_map('mb_strtolower', $options))) !== 4
                    || min($lengths) < 1 || max($lengths) > 20 || max($lengths) - min($lengths) > 6
                    || array_filter($options, fn (string $value): bool => trim($value) !== $value || strpbrk($value, "\r\n") !== false)) {
                    throw new RuntimeException('Invalid or duplicated question: '.$question['enunciado']);
                }
                $statements[$statement] = true;
                $letters[$answer]++;
                $module = $question['modulo'];
                $groups[$module]['count'] = ($groups[$module]['count'] ?? 0) + 1;
                $groups[$module]['amena'] = ($groups[$module]['amena'] ?? 0) + (int) $question['es_amena'];
                $answerLength = mb_strlen($question['opcion_'.$answer]);
                if ($answerLength === max($lengths) && count(array_filter($lengths, fn (int $length): bool => $length === $answerLength)) === 1) {
                    $longestCorrect++;
                }
                $records[] = [...$question, 'level_order' => $index + 1];
            }
            $names = array_keys($groups);
            sort($names);
            if (count($groups) !== 10 || ($modules !== [] && $modules !== $names)
                || array_unique(array_values($letters)) !== [25] || $longestCorrect > 35) {
                throw new RuntimeException('Unbalanced catalog: '.$file);
            }
            foreach ($groups as $group) {
                if ($group['count'] !== 10 || $group['amena'] !== ($index === 3 ? 0 : 1)) {
                    throw new RuntimeException('Expected 10 questions and the configured amenity count per module: '.$file);
                }
            }
            $modules = $names;
        }

        return $records;
    }

    public function fingerprint(array $question, string $module, int $order): string
    {
        $tuple = [$module, $order, $question['enunciado'], $question['opcion_a'], $question['opcion_b'],
            $question['opcion_c'], $question['opcion_d'], $question['respuesta_correcta'], (bool) $question['es_amena']];

        return hash('sha256', json_encode($tuple, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }
}
