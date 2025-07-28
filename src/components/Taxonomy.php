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
			if( $title == '' && count($items) == 0 ) {
				$ret[] = '0';
				$found = true;
			} else {
				$mb_title = mb_strtoupper($title);
				$found = false;
				foreach ($items as $k => $v) {
					if (mb_strtoupper($v['title']) == $mb_title
						|| mb_strtoupper($v['abbrev']??'') == $mb_title ) {
						$found = true;
						$ret[] = $k;
						break;
					}
				}
				if ($found) {
					$items = $v['items']??[];
				}
			}
			if( !$found ) {
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
			if( count($items) ) {
				$p = $items[$v]['abbrev']??$items[$v]['title']??'????';
				if ($p === '????' && is_numeric($v)) {
					$vt = ltrim($v, '0');
					if ($vt != $v) {
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

} // class
