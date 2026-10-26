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
 * DNI/NIE/CIF anonymization with unique values.
 *
 * The digit shuffle is deterministic but not injective: the output
 * space (eight digits with a control letter) is smaller than that of the real
 * inputs, and with thousands of different DNIs collisions appear (two different
 * DNIs would produce the same anonymized value). When that value takes part in
 * a UNIQUE key of the table (e.g. (ano, semana, nif)) the UPDATE of the
 * second row would clash with the key and abort the anonymization.
 *
 * This class guarantees that two different originals never obtain the same
 * anonymized value, keeping the other properties: same original -> always
 * the same anonymized value (also across tables, so the foreign keys
 * referencing the DNI stay consistent), because the mapping is
 * shared across the whole anonymization process (a single pass per database and
 * table). On a collision (including a collision with one of the EXCEPTIONS
 * values) the original that arrives later is anonymized with a different
 * salt (#1, #2, ...) until a free value is obtained, always in a valid
 * DNI/NIE/CIF format. The result depends on the order of appearance, which in
 * encrypt-dbs is stable across runs.
 */
class DNIAnonymizer
{
	const CHECKSUM_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';
	const NIE_PREFIXES = ['X' => 0, 'Y' => 1, 'Z' => 2];
	const CIF_LETTERS = 'ABCDEFGHJKLMNPQRSUVW';

	/**
	 * Values returned without anonymization (they are not real DNIs, or they are
	 * known test/placeholder DNIs) and that are never generated as
	 * output for another different original.
	 */
	const EXCEPTIONS = ['ADMIN', '00000000A', '59152066E'];

	/**
	 * Per secretKey: original -> anonymized mapping and the reverse (anonymized
	 * -> original), to detect and resolve collisions.
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
			return $originalID; // as is, without normalizing case
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
			// If the shuffle is insensitive to the salt for this input (e.g.
			// '00000000A', whose digits are all equal: the shuffle changes
			// nothing and the output is always the same), a candidate is
			// generated from a hash of the original.
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
	 * Generates a full candidate (with control letter) for $originalID;
	 * $suffix distinguishes retries on a collision, varying the shuffle.
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
	 * Generates a valid DNI/NIE/CIF from a hash, for inputs where
	 * the shuffle cannot produce different outputs by varying the suffix
	 * (all digits are equal).
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
		// Standard DNI
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
