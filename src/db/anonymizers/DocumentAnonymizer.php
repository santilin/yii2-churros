<?php

namespace santilin\churros\db\anonymizers;

/*
 * Ejemplo de uso:
 * $anonymizer = new DocumentNumberAnonymizer('tu-clave-secreta');
 * $original = '12345678Z / X1234567A / 50.123.456-X';
 * echo "Original: $original\n";
 * echo "Anonimizado: " . $anonymizer->anonymize($original) . "\n";
 */

class DocumentAnonymizer
{
    private $secretKey;
    private $digits;
    private $letters;

    public function __construct(string $secretKey) {
        $this->secretKey = $secretKey;
        $this->digits = ['0','1','2','3','4','5','6','7','8','9'];
        // Letras españolas (mayúsculas y minúsculas, incluyendo Ñ y tildes)
        $baseLetters = [
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'ñ',
            'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z',
            'á', 'é', 'í', 'ó', 'ú', 'ü'
        ];
        $this->letters = array_merge($baseLetters, array_map('mb_strtoupper', $baseLetters));
    }

    public function anonymize(?string $documentNumber): ?string
    {
        if (empty($documentNumber)) {
            return $documentNumber;
        }
        $chars = $this->mb_str_split_unicode($documentNumber);
        $anonymized = [];
        foreach ($chars as $i => $char) {
            $anonymized[] = $this->anonymizeChar($char, $i, $documentNumber);
        }
        return implode('', $anonymized);
    }

    private function anonymizeChar(string $char, int $index, string $original): string {
        if ($this->isDigit($char)) {
            $hash = crc32($this->secretKey . $char . $index . $original);
            return $this->digits[$hash % 10];
        }
        if ($this->isUnicodeLetter($char)) {
            $hash = crc32($this->secretKey . $char . $index . $original);
            return $this->letters[$hash % count($this->letters)];
        }
        // Mantener cualquier otro carácter (guiones, puntos, espacios, etc.)
        return $char;
    }

    private function isDigit(string $char): bool {
        return preg_match('/^\d$/u', $char) === 1;
    }

    private function isUnicodeLetter(string $char): bool {
        return preg_match('/^\p{L}$/u', $char) === 1;
    }

    private function mb_str_split_unicode(string $str): array {
        if (function_exists('mb_str_split')) {
            return mb_str_split($str, 1, 'UTF-8');
        }
        return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}
