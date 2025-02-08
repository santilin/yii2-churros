<?php

namespace santilin\churros\anonymizers\es;

/*
 * Example usage
 * $Anonymizer = new NameAnonymizer('your-secret-key-here');
 *
 * $originalName1 = 'Juan Carlos García López';
 * $originalName2 = 'María de los Ángeles Fernández';
 *
 * echo "Original: $originalName1\n";
 * echo "anonymized: " . $Anonymizer->anonymize($originalName1) . "\n";
 *
 * echo "Original: $originalName2\n";
 * echo "anonymized: " . $Anonymizer->anonymize($originalName2) . "\n";
 */

class NombrePersonaAnonymizer
{
	private $secretKey;

	public function __construct(string $secretKey) {
		$this->secretKey = $secretKey;
	}

	public function anonymize(string $name): string {
		// Split the name into words
		$words = explode(' ', $name);

		// anonymize each word
		$anonymizedWords = array_map([$this, 'anonymizeWord'], $words);

		// Reassemble the name
		return implode(' ', $anonymizedWords);
	}

	private function anonymizeWord(string $word): string {
		$letters = mb_str_split($word); // Split word into characters (supports multibyte)
		$anonymizedLetters = array_map([$this, 'anonymizeLetter'], $letters);
		return implode('', $anonymizedLetters);
	}

	private function anonymizeLetter(string $letter): string {
		// Only anonymize alphabetic characters
		if (!ctype_alpha($letter)) {
			return $letter;
		}

		// Generate a deterministic hash for the letter using secret key
		$hash = crc32($this->secretKey . $letter);

		// Map hash to a new letter (preserve case)
		$alphabet = ctype_upper($letter) ? range('A', 'Z') : range('a', 'z');
		return $alphabet[$hash % count($alphabet)];
	}
}


