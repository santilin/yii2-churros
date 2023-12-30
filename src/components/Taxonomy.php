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
		foreach( $value_parts as $k => $v ) {
			if( count($items) ) {
				$ret_parts[] = $items[$v]['abbrev']??$items[$v]['title']??'????';
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

} // class
