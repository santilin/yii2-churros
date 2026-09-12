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
 * Sustituye texto libre por relleno sintético determinista, sin intentar
 * "limpiar" el original en busca de datos personales (nombres, situaciones de
 * salud...): un borrado parcial al que se le escape algo es peor que uno que
 * lo sustituye del todo.
 *
 * Dos modos:
 * - narrativo (por defecto): relleno tipo lorem-ipsum, recortado/repetido a la
 *   longitud aproximada del original. Para textos largos (observaciones,
 *   historia, valoraciones...) donde interesa conservar que el campo tenga
 *   contenido (o no) y su longitud aproximada, para poder seguir probando
 *   cosas como truncados, "leer más", o paginación.
 * - etiqueta: elige determinísticamente una de una lista corta de etiquetas
 *   genéricas, para campos que son más una referencia corta (proveedor,
 *   entidad...) que una narración.
 *
 * Mismo original -> siempre el mismo resultado (con el mismo secret), igual
 * que el resto de anonimizadores: relanzar el proceso no cambia el dump.
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
