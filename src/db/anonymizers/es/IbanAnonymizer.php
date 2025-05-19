<?php

namespace santilin\churros\db\anonymizers\es;

/*
 * Ejemplo de uso:
 * $anonymizer = new IbanAnonymizer('tu-clave-secreta');
 * $original = 'ES91 2100 0418 4502 0005 1332';
 * echo "Original: $original\n";
 * echo "Anonimizado: " . $anonymizer->anonymize($original) . "\n";
 */

class IbanAnonymizer
{
    private $secretKey;
    private $digits;

    public function __construct(string $secretKey)
	{
        $this->secretKey = $secretKey;
        $this->digits = ['0','1','2','3','4','5','6','7','8','9'];
    }

    public function anonymize(?string $iban): ?string
	{
		if (empty($iban)) {
			return $iban;
		}
        // Eliminar espacios y mayúsculas
        $iban = strtoupper(str_replace(' ', '', $iban));
        if (mb_substr($iban, 0, 2, 'UTF-8') !== 'ES' || mb_strlen($iban, 'UTF-8') !== 24) {
            throw new \InvalidArgumentException('Solo se admite IBAN español de 24 caracteres.');
        }

        // Extraer campos
        // ESkk BBBB SSSS CC CCCCCCCCCC
        $bankCode   = mb_substr($iban, 4, 4, 'UTF-8');
        $branchCode = mb_substr($iban, 8, 4, 'UTF-8');
        $nationalCd = mb_substr($iban, 12, 2, 'UTF-8');
        $accountNum = mb_substr($iban, 14, 10, 'UTF-8');

        // Anonimizar cada campo numérico de forma determinista
        $bankCode   = $this->anonymizeDigits($bankCode, 'bank');
        $branchCode = $this->anonymizeDigits($branchCode, 'branch');
        $nationalCd = $this->anonymizeDigits($nationalCd, 'national');
        $accountNum = $this->anonymizeDigits($accountNum, 'account');

        // Reconstruir el BBAN
        $bban = $bankCode . $branchCode . $nationalCd . $accountNum;

        // Calcular los dígitos de control IBAN válidos
        $checkDigits = $this->calculateIbanCheckDigits('ES', $bban);

        // Montar el IBAN final
        $ibanFinal = 'ES' . $checkDigits . $bban;

        // Formato con espacios cada 4 caracteres
        return trim(chunk_split($ibanFinal, 4, ' '));
    }

    private function anonymizeDigits(string $digits, string $context): string {
        $chars = $this->mb_str_split_unicode($digits);
        $anonymized = [];
        foreach ($chars as $i => $char) {
            if (preg_match('/^\d$/', $char)) {
                $hash = crc32($this->secretKey . $context . $char . $i . $digits);
                $anonymized[] = $this->digits[$hash % 10];
            } else {
                // Por si acaso, pero en IBAN español solo deberían ser dígitos
                $anonymized[] = $char;
            }
        }
        return implode('', $anonymized);
    }

    // Calcula los dígitos de control IBAN según el estándar internacional
    private function calculateIbanCheckDigits($countryCode, $bban) {
        // Mover el país y los ceros al final
        $rearranged = $bban . $countryCode . '00';
        // Convertir letras a números (A=10, ..., Z=35)
        $converted = '';
        foreach (str_split($rearranged) as $c) {
            if (ctype_alpha($c)) {
                $converted .= (string)(ord($c) - 55);
            } else {
                $converted .= $c;
            }
        }
        // Calcular el resto
        $mod = intval(substr($converted, 0, 1));
        for ($i = 1; $i < strlen($converted); $i++) {
            $mod = ($mod * 10 + intval($converted[$i])) % 97;
        }
        $checkDigits = str_pad(98 - $mod, 2, '0', STR_PAD_LEFT);
        return $checkDigits;
    }

    private function mb_str_split_unicode(string $str): array {
        if (function_exists('mb_str_split')) {
            return mb_str_split($str, 1, 'UTF-8');
        }
        return preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    }
}

