<?php

namespace santilin\churros\db\anonymizers;

/*
 // Example usage:
 $narrativo = new FreeTextAnonymizer('your-secret-key-here');
 echo $narrativo->anonymize('Texto largo con datos personales...') . "\n";

 $etiqueta = new FreeTextAnonymizer('your-secret-key-here', etiqueta: true);
 echo $etiqueta->anonymize('Mercadona S.A.') . "\n";
*/

/**
 * Replaces free text with deterministic synthetic filler, without trying to
 * "clean" the original in search of personal data (names, health
 * situations...): a partial redaction that misses something is worse than one
 * that replaces it entirely.
 *
 * Two modes:
 * - narrative (default): lorem-ipsum style filler, trimmed/repeated to the
 *   approximate length of the original. For long texts (remarks,
 *   history, assessments...) where it matters to keep whether the field has
 *   content (or not) and its approximate length, so that things like
 *   truncation, "read more", or pagination can still be tested.
 * - label: deterministically picks one from a short list of generic
 *   labels, for fields that are more of a short reference (supplier,
 *   entity...) than a narrative.
 *
 * Same original -> always the same result (with the same secret), like
 * the rest of the anonymizers: re-running the process does not change the dump.
 */
class FreeTextAnonymizer
{
	private const LOREM_WORDS = [
		'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit',
		'sed', 'do', 'eiusmod', 'tempor', 'incididunt', 'ut', 'labore', 'et', 'dolore',
		'magna', 'aliqua', 'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
		'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip', 'ex', 'ea', 'commodo',
		'consequat', 'duis', 'aute', 'irure', 'in', 'reprehenderit', 'voluptate',
		'velit', 'esse', 'cillum', 'fugiat', 'nulla', 'pariatur',
	];

	private const ETIQUETAS = [
		'Proveedor genérico 1',
		'Proveedor genérico 2',
		'Proveedor genérico 3',
		'Proveedor genérico 4',
		'Proveedor genérico 5',
	];

	private string $secretKey;
	private bool $etiqueta;

	public function __construct(string $secretKey, bool $etiqueta = false)
	{
		$this->secretKey = $secretKey;
		$this->etiqueta = $etiqueta;
	}

	public function anonymize(?string $text): ?string
	{
		if ($text === null || $text === '') {
			return $text;
		}
		if ($this->etiqueta) {
			return self::ETIQUETAS[$this->hashIndex($text, count(self::ETIQUETAS))];
		}
		return $this->loremOfLength($text);
	}

	private function hashIndex(string $text, int $count): int
	{
		return hexdec(substr(hash('sha256', $this->secretKey . '|' . $text), 0, 8)) % $count;
	}

	private function loremOfLength(string $text): string
	{
		$length = mb_strlen($text);
		$seed = hexdec(substr(hash('sha256', $this->secretKey . '|' . $text), 0, 8));
		$words = self::LOREM_WORDS;
		$wordCount = count($words);
		$result = '';
		$i = 0;
		while (mb_strlen($result) < $length) {
			$result .= ($result === '' ? '' : ' ') . $words[($seed + $i) % $wordCount];
			$i++;
		}
		return mb_substr($result, 0, $length);
	}
}
