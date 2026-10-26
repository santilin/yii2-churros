<?php

namespace santilin\churros\db\anonymizers;

/*
 * Example usage:
 * $anonymizer = new PassportAnonymizer('your-secret-key');
 * echo $anonymizer->anonymize('PA1234567') . "\n";
 */

/**
 * Anonymization of passport numbers with unique values.
 *
 * DocumentAnonymizer encrypts character by character in a deterministic way, but
 * it does not guarantee injectivity: two different originals could, in theory,
 * produce the same anonymized value and clash with a UNIQUE key. This class adds
 * that guarantee, just like DNIAnonymizer: same original -> always the same
 * anonymized value (stable across tables if the same secret is used), and on a
 * collision it retries with a different salt (#1, #2...) until a free value is
 * obtained.
 */
class PassportAnonymizer
{
	/**
	 * Per secretKey: original -> anonymized mapping and the reverse one, to
	 * detect and resolve collisions.
	 * @var array<string,array{forward:array<string,string>,reverse:array<string,string>}>
	 */
	private static array $known = [];

	private string $secretKey;

	public function __construct(string $secretKey)
	{
		$this->secretKey = $secretKey;
	}

	public function anonymize(?string $original): ?string
	{
		if ($original === null || $original === '') {
			return $original;
		}
		if (!isset(self::$known[$this->secretKey])) {
			self::$known[$this->secretKey] = ['forward' => [], 'reverse' => []];
		}
		$map = &self::$known[$this->secretKey];
		if (isset($map['forward'][$original])) {
			return $map['forward'][$original];
		}

		$suffix = 0;
		do {
			$salt = $suffix === 0 ? '' : '#' . $suffix;
			$candidate = (new DocumentAnonymizer($this->secretKey . $salt))->anonymize($original);
			$suffix++;
		} while (isset($map['reverse'][$candidate]) && $suffix < 1000);
		if (isset($map['reverse'][$candidate])) {
			throw new \LogicException("No se pudo generar un pasaporte anonimizado único para '$original'");
		}

		$map['forward'][$original] = $candidate;
		$map['reverse'][$candidate] = $original;
		return $candidate;
	}
}
