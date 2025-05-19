<?php

namespace santilin\churros\db\anonymizers;

/*
 * Ejemplo de uso:
 * $anonymizer = new TelephoneNumberAnonymizer('tu-clave-secreta');
 * $original = '+34 600 123 456 / (91) 123-45-67 ext. 123';
 * echo "Original: $original\n";
 * echo "Anonimizado: " . $anonymizer->anonymize($original) . "\n";
 */

class PhoneNumberAnonymizer
{
    private $secretKey;
    private $digits;

    public function __construct(string $secretKey) {
        $this->secretKey = $secretKey;
        $this->digits = ['0','1','2','3','4','5','6','7','8','9'];
    }

    public function anonymize(?string $phone): ?string
	{
		if (empty($phone)) {
			return $phone;
		}
        $chars = $this->mb_str_split_unicode($phone);
        $anonymized = [];
        foreach ($chars as $i => $char) {
            $anonymized[] = $this->anonymizeChar($char, $i, $phone);
        }
        return implode('', $anonymized);
    }

    private function anonymizeChar(string $char, int $index, string $original): string {
        if ($this->isDigit($char)) {
            $hash = crc32($this->secretKey . $char . $index . $original);
            return $this->digits[$hash % 10];
        }
        // Mantener cualquier otro carácter (espacios, signos, letras, etc.)
        return $char;
    }

    private function isDigit(string $char): bool {
        return preg_match('/^\d$/u', $char) === 1;
    }

    private function mb_str_split_unicode(string $str): array {
        if (function_exists('mb_str_split')) {
            return mb_str_split($str, 1, 'UTF-8');
        }
        return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}
