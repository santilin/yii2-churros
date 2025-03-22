<?php

namespace santilin\churros\db\anonymizers;

/*
 * Example usage
	$anonymizer = new EmailAnonymizer('your-secret-key-here');

	$originalEmail1 = 'john.doe@example.com';

	echo "Original: $originalEmail1\n";
	echo "Anonymized: " . $anonymizer->anonymize($originalEmail1) . "\n";
 */

class EmailAnonymizer
{
	private $secretKey;

	public function __construct(string $secretKey) {
		$this->secretKey = $secretKey;
	}

	public function anonymize(?string $email): ?string
	{
		if ($email === null) {
			return $email;
		}
		if (trim($email) == '') {
			return $email;
		}

		if (strpos($email, '@') === FALSE) {
			return $email;
		}
		// Split email into local part and domain
		[$localPart, $domain] = explode('@', $email);

		// Anonymize the local part
		$anonymizedLocalPart = $this->anonymizeLocalPart($localPart);

		// Reassemble the email
		return $anonymizedLocalPart . '@' . $domain;
	}

	private function anonymizeLocalPart(string $localPart): string {
		// Split into characters while preserving dots
		$characters = str_split($localPart);
		$anonymizedCharacters = array_map([$this, 'anonymizeCharacter'], $characters);

		return implode('', $anonymizedCharacters);
	}

	private function anonymizeCharacter(string $char): string {
		// Preserve dots and other non-alphabetic characters
		if (!ctype_alnum($char)) {
			return $char;
		}

		// Generate a deterministic hash for the character using secret key
		$hash = crc32($this->secretKey . $char);

		// Map hash to a new alphanumeric character
		if (ctype_digit($char)) {
			// If it's a digit, map to another digit
			return strval($hash % 10);
		} else {
			// If it's a letter, map to another letter (preserve case)
			$alphabet = ctype_upper($char) ? range('A', 'Z') : range('a', 'z');
			return $alphabet[$hash % count($alphabet)];
		}
	}
}


