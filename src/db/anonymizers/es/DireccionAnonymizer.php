<?php

namespace santilin\churros\db\anonymizers\es;

/*
 * Ejemplo de uso:
 * $anonymizer = new DireccionAnonymizer('tu-clave-secreta');
 * $original = 'Calle de la Constitución, 23 2ºB';
 * echo "Original: $original\n";
 * echo "Anonimizada: " . $anonymizer->anonymize($original) . "\n";
 */

class DireccionAnonymizer
{
    private $secretKey;
    private $vowels;
    private $consonants;

    public function __construct(string $secretKey) {
        $this->secretKey = $secretKey;
        // Todas las vocales relevantes en español (mayúsculas, minúsculas, acentuadas y ü)
        $this->vowels = [
            'a', 'e', 'i', 'o', 'u', 'á', 'é', 'í', 'ó', 'ú', 'ü',
            'A', 'E', 'I', 'O', 'U', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü'
        ];
        // Consonantes españolas (mayúsculas y minúsculas)
        $baseConsonants = [
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n',
            'ñ', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
        ];
        $this->consonants = array_merge($baseConsonants, array_map('mb_strtoupper', $baseConsonants));
    }

    public function anonymize(?string $direccion): ?string
    {
        if (empty($direccion)) {
            return $direccion;
        }        // Separa por espacios, pero mantiene números y signos juntos a palabras
        $words = preg_split('/(\s+)/u', $direccion, -1, PREG_SPLIT_DELIM_CAPTURE);
        $anonymized = array_map([$this, 'anonymizeWord'], $words);
        return implode('', $anonymized);
    }

    private function anonymizeWord(string $word): string {
        // Si la palabra es solo espacios, retorna igual
        if (preg_match('/^\s+$/u', $word)) {
            return $word;
        }

        $letters = $this->mb_str_split_unicode($word);
        $anonymizedLetters = [];

        foreach ($letters as $i => $letter) {
            $anonymizedLetters[] = $this->anonymizeLetter($letter, $i, $word);
        }

        // Asegura al menos una vocal si hay alguna letra
        if ($this->hasLetters($letters) && !$this->containsVowel($anonymizedLetters)) {
            $position = $this->getDeterministicPosition($word);
            $anonymizedLetters[$position] = $this->getDeterministicVowel($word, $position);
        }

        return implode('', $anonymizedLetters);
    }

    private function anonymizeLetter(string $letter, int $index, string $word): string {
        // Solo anonimiza letras Unicode
        if (!$this->isUnicodeLetter($letter)) {
            return $letter;
        }

        $isVowel = $this->isVowel($letter);
        $alphabet = $isVowel ? $this->vowels : $this->consonants;

        $hash = crc32($this->secretKey . $letter . $index . $word);
        return $alphabet[$hash % count($alphabet)];
    }

    private function containsVowel(array $letters): bool {
        foreach ($letters as $letter) {
            if ($this->isVowel($letter)) {
                return true;
            }
        }
        return false;
    }

    private function hasLetters(array $letters): bool {
        foreach ($letters as $letter) {
            if ($this->isUnicodeLetter($letter)) {
                return true;
            }
        }
        return false;
    }

    private function getDeterministicPosition(string $word): int {
        $letters = $this->mb_str_split_unicode($word);
        $positions = [];
        foreach ($letters as $i => $letter) {
            if ($this->isUnicodeLetter($letter)) {
                $positions[] = $i;
            }
        }
        if (count($positions) === 0) return 0;
        $hash = crc32($this->secretKey . $word);
        return $positions[$hash % count($positions)];
    }

    private function getDeterministicVowel(string $word, int $position): string {
        $hash = crc32($this->secretKey . $word . $position);
        return $this->vowels[$hash % count($this->vowels)];
    }

    private function isVowel(string $char): bool {
        return in_array($char, $this->vowels, true);
    }

    private function isUnicodeLetter(string $char): bool {
        return (bool) preg_match('/^\p{L}$/u', $char);
    }

    private function mb_str_split_unicode(string $str): array {
        if (function_exists('mb_str_split')) {
            return mb_str_split($str, 1, 'UTF-8');
        }
        return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}

