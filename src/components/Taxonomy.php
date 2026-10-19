<?php
namespace santilin\churros\components;

class Taxonomy
{
	static public function titlesToCode(array $titles, array $taxonomy): string
	{
		$items = $taxonomy['items'];
		$dot = $taxonomy['dot']??'.';
		$ret = [];
		foreach ($titles as $title) {
			if ($title == '' && count($items) == 0) {
				$ret[] = '0';
				$found = true;
			} else {
				$mb_title = mb_strtoupper($title);
				$found = false;
				foreach ($items as $k => $v) {
					if (mb_strtoupper($v['title']) == $mb_title
						|| mb_strtoupper($v['abbrev']??'') == $mb_title) {
						$found = true;
						$ret[] = $k;
						break;
					}
				}
				if ($found) {
					$items = $v['items']??[];
				}
			}
			if (!$found) {
				return '';
			}
		}
		return implode($dot,$ret);
	}

	static public function format(string $value, array $taxonomy, string $sep = '/'): string
	{
		$value_parts = explode($taxonomy['dot']??'.', $value);
		$ret_parts = [ $value ];
		$items = $taxonomy['items'];
		foreach ($value_parts as $k => $v) {
			if (count($items)) {
				$p = $items[$v]['abbrev']??$items[$v]['title']??'????';
				if ($p === '????' && is_numeric($v)) {
					$vt = ltrim($v, '0');
					if ($vt !== $v) {
						$p = $items[$vt]['abbrev']??$items[$vt]['title']??'????';
					}
				}
				$ret_parts[] = $p;
				$items = $items[$v]['items']??[];
			} else {
				break;
			}
		}
		return implode($sep, $ret_parts);
	}

	static public function formatTitles(string $value, array $taxonomy, string $sep = '/'): string
	{
		$value_parts = explode($taxonomy['dot']??'.', $value);
		$ret_parts = [];
		$items = $taxonomy['items'];
		foreach ($value_parts as $k => $v) {
			if (count($items)) {
				$p = $items[$v]['title']??$items[$v]['abbrev']??'????';
				if ($p === '????' && is_numeric($v) && ltrim($v, '0') !== $v) {
					$v = ltrim($v, '0');
					$p = $items[$v]['title']??$items[$v]['abbrev']??'????';
				}
				$ret_parts[] = $p;
				$items = $items[$v]['items']??[];
			} else {
				break;
			}
		}
		return implode($sep, $ret_parts);
	}

	static public function calcLevel(string $value, array $taxonomy): int
	{
		$parts = explode($taxonomy['dot']??'.', $value);
		return count($parts);
	}

	static public function upToLevel(int $level, string $value, array $taxonomy, $inc_dot = false): string
	{
		$parts = explode($taxonomy['dot'] ?? '.', $value);
		$parts = array_slice($parts, 0, $level);
		$ret = implode($taxonomy['dot'] ?? '.', $parts);
		if ($inc_dot) {
			$ret .= $taxonomy['dot'] ?? '.';
		}
		return $ret;
	}


	static public function findTitles(array $taxonomy): array
	{
		$ret = [];
		foreach ($taxonomy['items']??[] as $k => $item) {
			$ret[$k] = $item['title']??$k;
		}
		return $ret;
	}

	static public function fixCode(string $value, array $taxonomy): string
	{
		$ret_parts = [];
		$dot = $taxonomy['dot']??'.';
		$value_parts = explode($dot, $value);
		$mask_parts = explode($dot, $taxonomy['mask']);
		for ($i=0; $i<count($value_parts); ++$i) {
			if ($i >= $mask_parts) {
				$ret_parts[] = $value_parts[$i];
			} else {
				$ret_parts[] = self::applySegmentedMask($value_parts[$i], $mask_parts[$i]);
			}
		}
		return implode($dot, $ret_parts);
	}

	static private function applySegmentedMask($segmentInput, $segmentMask)
	{
		// Count number of mask chars
		$padLength = strlen($segmentMask);
		// Find leading zeros for pad calculation
		preg_match('/^(0+)/', $segmentMask, $matches);
		$leadingZeros = isset($matches[1]) ? strlen($matches[1]) : 0;
		// Only pad if there are zeros and input is shorter than mask
		if ($leadingZeros > 0) {
			$padded = str_pad($segmentInput, $padLength, '0', STR_PAD_LEFT); // PHP standard method[1][2][4][8]
		} else {
			$padded = str_pad($segmentInput, $padLength, ' ', STR_PAD_LEFT);
		}
		// Truncate in case input is too long
		return substr($padded, -$padLength, $padLength);
	}

	/**
	 * Código padre (sin el último segmento). '' si es de primer nivel.
	 */
	static public function parentCode(string $value, array $taxonomy): string
	{
		$dot = $taxonomy['dot']??'.';
		$pos = strrpos($value, $dot);
		return $pos === false ? '' : substr($value, 0, $pos);
	}

	/**
	 * Último segmento del código (número de bloque).
	 */
	static public function blockNumber(string $value, array $taxonomy): string
	{
		$dot = $taxonomy['dot']??'.';
		$pos = strrpos($value, $dot);
		return $pos === false ? $value : substr($value, $pos + strlen($dot));
	}

	/**
	 * True si $code está bajo $ancestor (comparando por segmentos).
	 * Sirve para impedir mover un nodo dentro de su propio subárbol.
	 */
	static public function isDescendantOf(string $code, string $ancestor, array $taxonomy): bool
	{
		if ($ancestor === '') {
			return true;
		}
		$dot = $taxonomy['dot']??'.';
		return $code !== $ancestor
			&& strpos($code . $dot, $ancestor . $dot) === 0;
	}

	/**
	 * Nuevo código al mover (reparentar) un nodo bajo otro padre,
	 * conservando su último segmento. Devuelve '' si es inválido
	 * (moverse a sí mismo o a un descendiente).
	 */
	static public function reparentCode(string $code, string $newParent, array $taxonomy): string
	{
		if ($code === '' || $newParent === $code
			|| ($newParent !== '' && self::isDescendantOf($newParent, $code, $taxonomy))) {
			return '';
		}
		$dot = $taxonomy['dot']??'.';
		$block = self::blockNumber($code, $taxonomy);
		$newParentLevel = $newParent === '' ? 0 : count(explode($dot, $newParent));
		if (!self::fitsMaskWidth($block, $newParentLevel, $taxonomy)) {
			return '';
		}
		$new = $newParent === '' ? $block
			: $newParent . $dot . $block;
		return self::fixCode($new, $taxonomy);
	}

	/**
	 * Nuevo código al renumerar un nodo dentro de su mismo padre.
	 * Devuelve '' si no cabe en el ancho de la máscara.
	 */
	static public function renumberCode(string $code, $newNumber, array $taxonomy): string
	{
		$dot = $taxonomy['dot']??'.';
		$parent = self::parentCode($code, $taxonomy);
		$level = $parent === '' ? 0 : count(explode($dot, $parent));
		if (!self::fitsMaskWidth((string)$newNumber, $level, $taxonomy)) {
			return '';
		}
		$new = $parent === '' ? (string)$newNumber : $parent . $dot . $newNumber;
		return self::fixCode($new, $taxonomy);
	}

	/**
	 * True si el segmento cabe en el ancho del grupo de la máscara indicado.
	 */
	static private function fitsMaskWidth(string $segment, int $level, array $taxonomy): bool
	{
		$dot = $taxonomy['dot']??'.';
		$mask_parts = explode($dot, $taxonomy['mask']??'');
		if (!isset($mask_parts[$level])) {
			return true;
		}
		return strlen($segment) <= strlen($mask_parts[$level]);
	}

	/**
	 * Siguiente número de bloque libre bajo un padre, según los códigos
	 * hermanos existentes y el ancho de la máscara. '' si no cabe.
	 */
	static public function nextChildCode(string $parent, array $existingCodes, array $taxonomy): string
	{
		$dot = $taxonomy['dot']??'.';
		$mask_parts = explode($dot, $taxonomy['mask']??'');
		$level = $parent === '' ? 0 : count(explode($dot, $parent));
		$width = isset($mask_parts[$level]) ? strlen($mask_parts[$level]) : 0;
		$max = 0;
		$prefix = $parent === '' ? '' : $parent . $dot;
		foreach ($existingCodes as $code) {
			if (strpos($code, $prefix) === 0) {
				$rest = substr($code, strlen($prefix));
				if (strpos($rest, $dot) === false && is_numeric($rest)) {
					$max = max($max, intval($rest));
				}
			}
		}
		$next = $max + 1;
		if ($width > 0 && strlen((string)$next) > $width) {
			return '';
		}
		$new = $prefix . ($width > 0 ? str_pad((string)$next, $width, '0', STR_PAD_LEFT) : (string)$next);
		return self::fixCode($new, $taxonomy);
	}

} // class
