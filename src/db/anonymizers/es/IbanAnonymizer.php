<?php

namespace santilin\churros\db\anonymizers\es;

/*
 * Usage example:
 * $anonymizer = new IbanAnonymizer('your-secret-key');
 * $original = 'ES91 2100 0418 4502 0005 1332';
 * echo "Original: $original\n";
 * echo "Anonymized: " . $anonymizer->anonymize($original) . "\n";
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
        // Remove spaces and convert to uppercase
        $iban = strtoupper(str_replace(' ', '', $iban));
        if (mb_substr($iban, 0, 2, 'UTF-8') !== 'ES' || mb_strlen($iban, 'UTF-8') !== 24) {
            // IBAN from another country (or a value that is not a valid IBAN): we
            // cannot calculate its check digits, so it is encrypted character by
            // character without trying to keep it valid, instead of aborting the
            // whole anonymization process for a single non-Spanish value.
            return trim(chunk_split($this->anonymizeGeneric($iban), 4, ' '));
        }

        // Extract fields
        // ESkk BBBB SSSS CC CCCCCCCCCC
        $bankCode   = mb_substr($iban, 4, 4, 'UTF-8');
        $branchCode = mb_substr($iban, 8, 4, 'UTF-8');
        $nationalCd = mb_substr($iban, 12, 2, 'UTF-8');
        $accountNum = mb_substr($iban, 14, 10, 'UTF-8');

        // Anonymize each numeric field deterministically
        $bankCode   = $this->anonymizeDigits($bankCode, 'bank');
        $branchCode = $this->anonymizeDigits($branchCode, 'branch');
        $nationalCd = $this->anonymizeDigits($nationalCd, 'national');
        $accountNum = $this->anonymizeDigits($accountNum, 'account');

        // Rebuild the BBAN
        $bban = $bankCode . $branchCode . $nationalCd . $accountNum;

        // Calculate the valid IBAN check digits
        $checkDigits = $this->calculateIbanCheckDigits('ES', $bban);

        // Assemble the final IBAN
        $ibanFinal = 'ES' . $checkDigits . $bban;

        // Format with spaces every 4 characters
        return trim(chunk_split($ibanFinal, 4, ' '));
    }

    private function anonymizeGeneric(string $iban): string {
        $chars = $this->mb_str_split_unicode($iban);
        $anonymized = [];
        foreach ($chars as $i => $char) {
            if (preg_match('/^\d$/u', $char)) {
                $hash = crc32($this->secretKey . 'generic' . $char . $i . $iban);
                $anonymized[] = $this->digits[$hash % 10];
            } elseif (preg_match('/^[A-Za-z]$/u', $char)) {
                $hash = crc32($this->secretKey . 'genericletter' . $char . $i . $iban);
                $anonymized[] = chr(65 + ($hash % 26));
            } else {
                $anonymized[] = $char;
            }
        }
        return implode('', $anonymized);
    }

    private function anonymizeDigits(string $digits, string $context): string {
        $chars = $this->mb_str_split_unicode($digits);
        $anonymized = [];
        foreach ($chars as $i => $char) {
            if (preg_match('/^\d$/', $char)) {
                $hash = crc32($this->secretKey . $context . $char . $i . $digits);
                $anonymized[] = $this->digits[$hash % 10];
            } else {
                // Just in case, but a Spanish IBAN should only contain digits
                $anonymized[] = $char;
            }
        }
        return implode('', $anonymized);
    }

    // Calculates IBAN check digits according to the international standard
    private function calculateIbanCheckDigits($countryCode, $bban) {
        // Move the country code and the zeros to the end
        $rearranged = $bban . $countryCode . '00';
        // Convert letters to numbers (A=10, ..., Z=35)
        $converted = '';
        foreach (str_split($rearranged) as $c) {
            if (ctype_alpha($c)) {
                $converted .= (string)(ord($c) - 55);
            } else {
                $converted .= $c;
            }
        }
        // Calculate the remainder
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

