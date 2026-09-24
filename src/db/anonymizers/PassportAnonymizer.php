<?php

namespace santilin\churros\db\anonymizers;

/*
 * Ejemplo de uso:
 * $anonymizer = new PassportAnonymizer('tu-clave-secreta');
 * echo $anonymizer->anonymize('PA1234567') . "\n";
 */

/**
 * Anonimización de números de pasaporte con valores únicos.
 *
 * DocumentAnonymizer cifra carácter a carácter de forma determinista, pero no
 * garantiza inyectividad: dos originales distintos podrían, en teoría,
 * producir el mismo anonimizado y chocar con una clave UNIQUE. Esta clase
 * añade esa garantía, igual que DNIAnonymizer: mismo original -> siempre el
 * mismo anonimizado (estable entre tablas si se usa el mismo secret), y ante
 * una colisión se reintenta con un salt distinto (#1, #2...) hasta obtener un
 * valor libre.
 */
class PassportAnonymizer
{
	/**
	 * Por secretKey: mapeo original -> anonimizado y el inverso, para
	 * detectar y resolver colisiones.
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
