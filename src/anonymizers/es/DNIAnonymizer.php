<?php

namespace santilin\churros\anonymizers\es;

/*
 // Example usage:
 $anonyimizer = new DNIAnonymizer('your-secret-key-here');

 $dni = '12345678Z';
 $nie = 'X1234567L';
 $cif = 'A1234567B';

 echo $anonyimizer->anonymize($dni) . "\n";  // Example: 87654321T
 echo $anonyimizer->anonymize($nie) . "\n";  // Example: Z7654321R
 echo $anonyimizer->anonymize($cif) . "\n";  // Example: A7654321S
*/

class DNIAnonymizer
{
	const CHECKSUM_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';
	const NIE_PREFIXES = ['X' => 0, 'Y' => 1, 'Z' => 2];
	const CIF_LETTERS = 'ABCDEFGHJKLMNPQRSUVW';

	private $secretKey;

	public function __construct(string $secretKey) {
		$this->secretKey = $secretKey;
	}

	public function anonymize(string $originalID): string {
		$originalID = strtoupper($originalID);
		$firstChar = $originalID[0];

		// Determine ID type
		$type = $this->determineIdType($firstChar);

		// Extract numeric components based on type
		$components = $this->extractComponents($originalID, $type);

		// Shuffle numeric part
		$shuffledNumber = $this->shuffleDigits($components['numeric'], $originalID);

		// Rebuild ID with correct format
		$rebuiltID = $this->rebuildId($shuffledNumber, $components, $type);

		// Calculate and append checksum
		return $this->addChecksum($rebuiltID, $type);
	}

	private function determineIdType(string $firstChar): string {
		if (array_key_exists($firstChar, self::NIE_PREFIXES)) {
			return 'NIE';
		}
		if (strpos(self::CIF_LETTERS, $firstChar) !== false) {
			return 'CIF';
		}
		return 'DNI';
	}

	private function extractComponents(string $id, string $type): array {
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

	private function shuffleDigits(string $number, string $salt): string {
		$digits = str_split($number);
		$seed = crc32($this->secretKey . $salt);
		mt_srand($seed);

		$shuffled = [];
		while (!empty($digits)) {
			$index = mt_rand(0, count($digits) - 1);
			$shuffled[] = array_splice($digits, $index, 1)[0];
		}

		return str_pad(implode('', $shuffled), 8, '0', STR_PAD_LEFT);
	}

	private function rebuildId(string $shuffled, array $components, string $type): string {
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

	private function addChecksum(string $idBody, string $type): string {
		$numericValue = match($type) {
			'NIE' => self::NIE_PREFIXES[$idBody[0]] . substr($idBody, 1),
			'CIF' => substr($idBody, 1),
			default => $idBody
		};

		$remainder = (int)$numericValue % 23;
		return $idBody . self::CHECKSUM_LETTERS[$remainder];
	}
}


