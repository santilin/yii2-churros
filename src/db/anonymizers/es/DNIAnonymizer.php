<?php

namespace santilin\churros\db\anonymizers\es;

/*
 // Example usage:
 $anonymizer = new DNIAnonymizer('your-secret-key-here');

 $dni = '12345678Z';
 $nie = 'X1234567L';
 $cif = 'A1234567B';

 echo $anonymizer->anonymize($dni) . "\n";  // Example: 87654321T
 echo $anonymizer->anonymize($nie) . "\n";  // Example: Z7654321R
 echo $anonymizer->anonymize($cif) . "\n";  // Example: A7654321S
*/

/**
 * Anonimización de DNI/NIE/CIF con valores únicos.
 *
 * El shuffle de dígitos es determinista pero no inyectivo: el espacio de
 * salida (ocho dígitos con letra de control) es menor que el de las entradas
 * reales, y con miles de DNIs distintos aparecen colisiones (dos DNIs
 * distintos producirían el mismo anonimizado). Cuando ese valor participa en
 * una clave UNIQUE de la tabla (p.ej. (ano, semana, nif)) el UPDATE de la
 * segunda fila chocaría con la clave y abortaría la anonimización.
 *
 * Esta clase garantiza que dos originales distintos nunca obtienen el mismo
 * anonimizado, conservando las demás propiedades: mismo original -> siempre
 * el mismo anonimizado (también entre tablas, por lo que las claves ajenas
 * que referencian el DNI se mantienen coherentes), porque el mapeo se
 * comparte en todo el proceso de anonimización (una sola pasada por base y
 * tabla). Ante una colisión (incluida una colisión con uno de los valores de
 * EXCEPTIONS) el original que llega después se anonimiza con un salt
 * distinto (#1, #2, ...) hasta obtener un valor libre, siempre en formato
 * DNI/NIE/CIF válido. El resultado depende del orden de aparición, que en
 * encrypt-dbs es estable entre ejecuciones.
 */
class DNIAnonymizer
{
	const CHECKSUM_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';
	const NIE_PREFIXES = ['X' => 0, 'Y' => 1, 'Z' => 2];
	const CIF_LETTERS = 'ABCDEFGHJKLMNPQRSUVW';

	/**
	 * Valores que se devuelven sin anonimizar (no son DNIs reales, o son
	 * DNIs de prueba/placeholder ya conocidos) y que nunca se generan como
	 * salida para otro original distinto.
	 */
	const EXCEPTIONS = ['ADMIN', '00000000A', '59152066E'];

	/**
	 * Por secretKey: mapeo original -> anonimizado y el inverso (anonimizado
	 * -> original), para detectar y resolver colisiones.
	 * @var array<string,array{forward:array<string,string>,reverse:array<string,string>}>
	 */
	private static array $known = [];

	private string $secretKey;

	public function __construct(string $secretKey)
	{
		$this->secretKey = $secretKey;
	}

	public function anonymize(?string $originalID): ?string
	{
		if ($originalID === null || $originalID === '') {
			return $originalID;
		}
		if (in_array(strtoupper($originalID), self::EXCEPTIONS, true)) {
			return $originalID; // tal cual, sin normalizar mayúsculas/minúsculas
		}
		$originalID = strtoupper($originalID);

		if (!isset(self::$known[$this->secretKey])) {
			self::$known[$this->secretKey] = ['forward' => [], 'reverse' => []];
		}
		$map = &self::$known[$this->secretKey];
		if (isset($map['forward'][$originalID])) {
			return $map['forward'][$originalID];
		}

		$suffix = 0;
		$prevCandidate = null;
		do {
			$candidate = $this->generateCandidate($originalID, $suffix);
			// Si el shuffle es insensible al salt para esta entrada (p.ej.
			// '00000000A', cuyos dígitos son todos iguales: el shuffle no
			// cambia nada y la salida es siempre la misma), se genera un
			// candidato a partir de un hash del original.
			if ($candidate === $prevCandidate) {
				$candidate = $this->hashFallbackDNI($originalID, $suffix);
			}
			$prevCandidate = $candidate;
			$suffix++;
		} while (
			(isset($map['reverse'][$candidate]) || in_array($candidate, self::EXCEPTIONS, true))
			&& $suffix < 1000
		);
		if (isset($map['reverse'][$candidate])) {
			throw new \LogicException("No se pudo generar un DNI anonimizado único para '$originalID'");
		}

		$map['forward'][$originalID] = $candidate;
		$map['reverse'][$candidate] = $originalID;
		return $candidate;
	}

	/**
	 * Genera un candidato completo (con letra de control) para $originalID;
	 * $suffix distingue reintentos ante una colisión, variando el shuffle.
	 */
	private function generateCandidate(string $originalID, int $suffix): string
	{
		$firstChar = $originalID[0];
		$type = $this->determineIdType($firstChar);
		$components = $this->extractComponents($originalID, $type);
		$shuffledNumber = $this->shuffleDigits($components['numeric'], $originalID, $suffix);
		$rebuiltID = $this->rebuildId($shuffledNumber, $components, $type);
		return $this->addChecksum($rebuiltID, $type);
	}

	/**
	 * Genera un DNI/NIE/CIF válido a partir de un hash, para entradas donde
	 * el shuffle no puede producir salidas distintas variando el suffix
	 * (todos los dígitos son iguales).
	 */
	private function hashFallbackDNI(string $originalID, int $suffix): string
	{
		$firstChar = $originalID[0];
		if (array_key_exists($firstChar, self::NIE_PREFIXES)) {
			$numeric = hexdec(substr(hash('sha256', $this->secretKey . '|' . $originalID . '|' . $suffix), 0, 7)) % 10000000;
			$body = $firstChar . str_pad((string) $numeric, 7, '0', STR_PAD_LEFT);
			$remainder = (int) (self::NIE_PREFIXES[$firstChar] . substr($body, 1)) % 23;
			return $body . self::CHECKSUM_LETTERS[$remainder];
		}
		if (strpos(self::CIF_LETTERS, $firstChar) !== false) {
			$numeric = hexdec(substr(hash('sha256', $this->secretKey . '|' . $originalID . '|' . $suffix), 0, 7)) % 10000000;
			return $firstChar . str_pad((string) $numeric, 7, '0', STR_PAD_LEFT);
		}
		// DNI estándar
		$numeric = hexdec(substr(hash('sha256', $this->secretKey . '|' . $originalID . '|' . $suffix), 0, 8)) % 100000000;
		$body = str_pad((string) $numeric, 8, '0', STR_PAD_LEFT);
		$remainder = (int) $body % 23;
		return $body . self::CHECKSUM_LETTERS[$remainder];
	}

	private function determineIdType(string $firstChar): string
	{
		if (array_key_exists($firstChar, self::NIE_PREFIXES)) {
			return 'NIE';
		}
		if (strpos(self::CIF_LETTERS, $firstChar) !== false) {
			return 'CIF';
		}
		return 'DNI';
	}

	private function extractComponents(string $id, string $type): array
	{
		$components = ['prefix' => '', 'numeric' => ''];

		switch ($type) {
			case 'NIE':
				$components['prefix'] = $id[0];
				$components['numeric'] = self::NIE_PREFIXES[$components['prefix']] . substr($id, 1, 7);
				break;
			case 'CIF':
				$components['prefix'] = $id[0];
				$components['numeric'] = substr($id, 1, 7);
				break;
			default: // DNI
				$components['numeric'] = substr($id, 0, 8);
				break;
		}

		return $components;
	}

	private function shuffleDigits(string $number, string $originalID, int $suffix): string
	{
		$digits = str_split($number);
		$salt = $suffix === 0 ? $originalID : $originalID . '#' . $suffix;
		$seed = crc32($this->secretKey . $salt);
		mt_srand($seed);

		$shuffled = [];
		while (!empty($digits)) {
			$index = mt_rand(0, count($digits) - 1);
			$shuffled[] = array_splice($digits, $index, 1)[0];
		}

		return str_pad(implode('', $shuffled), 8, '0', STR_PAD_LEFT);
	}

	private function rebuildId(string $shuffled, array $components, string $type): string
	{
		switch ($type) {
			case 'NIE':
				$niePrefix = array_search(substr($shuffled, 0, 1), self::NIE_PREFIXES) ?: 'X';
				return $niePrefix . substr($shuffled, 1, 7);
			case 'CIF':
				return $components['prefix'] . substr($shuffled, 0, 7);
			default: // DNI
				return substr($shuffled, 0, 8);
		}
	}

	private function addChecksum(string $idBody, string $type): string
	{
		$numericValue = match ($type) {
			'NIE' => self::NIE_PREFIXES[$idBody[0]] . substr($idBody, 1),
			'CIF' => substr($idBody, 1),
			default => $idBody
		};

		$remainder = (int) $numericValue % 23;
		return $idBody . self::CHECKSUM_LETTERS[$remainder];
	}
}
