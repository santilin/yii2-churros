<?php
/**
 * @link
 * @copyright
 * @license
 */

namespace santilin\churros\helpers;

use Yii;
use yii\helpers\StringHelper;
use League\HTMLToMarkdown\HtmlConverter;

class AppHelper
{

	static public function findKeyInArray($array, $key)
	{
		foreach ($array as $k => $value) {
			if ($k === $key) {
				return true;
			}
			if (is_array($value)) {
				if (findKeyInNestedArray($value, $key)) {
					return true;
				}
			}
		}
		return false;
	}

	static public function findKeyAndValueInArray($array, $key, $value)
	{
		foreach ($array as $k => $v) {
			if ($k === $key && $v === $value) {
				return true;
			}
			if (is_array($v)) {
				if (self::findKeyAndValueInArray($v, $key, $value)) {
					return true;
				}
			}
		}
		return false;

	}

	static public function removeDirRec(string $path): bool
	{
		return exec('rm -rf ' . escapeshellarg($path));
	}

	static public function checkWritableDir(string $path, int $perm = 0775, ?string $eol = "\n"): bool
	{
		if( $eol ) echo "Checking if $path is writable...$eol";
		if (!is_dir($path) ) {
			if( @mkdir($path, $perm) ) {
				if( $eol ) echo "$path: created$eol";
			} else {
				$error = error_get_last();
				if( $eol ) echo "$path: " . $error['message'] . $eol;
				return false;
			}
		} else {
			if (!is_writable($path) ) {
				@chmod($dir, $perm);
			}
        }
		if (!is_writable($path) ) {
			$whoami = exec('whoami');
			if( $eol ) echo $path . ": not writable by $whoami user$eol";
			return false;
		} else {
			if( $eol ) echo $path . ": Ok$eol";
		}
		return true;

	}

	static public function checkIsLink(string $target, string $link, string $eol = "\n"): bool
	{
		echo "Checking if $link is a link to $target...$eol";

		if (!file_exists($target)) {
			echo "$target: target of $link symlink not found$eol";
			return false;
		}

		if (is_link($link) && !file_exists($link)) {
			@unlink($link); // Eliminar enlace roto antes de recrear
			@symlink($target, $link);
			if (!is_link($link)) {
				$error = error_get_last();
				echo $link . ": " . $error['message'] . " creating symlink to $target$eol";
				return false;
			}
		}

		if (!is_link($link)) {
			echo $link . ": is not a symlink$eol";
			return false;
		}

		// Verificación añadida: Comparar destino real
		$actualTarget = readlink($link);
		if ($actualTarget !== $target) {
			echo "$link: points to incorrect target '$actualTarget' (should be '$target')$eol";
			return false;
		}

		echo $link . ": Ok$eol";
		return true;
	}


	// For defaultValidator
    static public function empty($value)
    {
		return empty($value);
	}

	static public function removeFirstWord(string $stack, string $sep = ' '): string
	{
		$pos_sep = strpos($stack, $sep);
		if ($pos_sep === FALSE) {
			return $stack;
		} else {
			return substr($stack, $pos_sep);
		}
	}

	static public function removeLastWord(string $stack, string $sep = ' '): string
	{
		$pos_sep = strrpos($stack, $sep);
		if ($pos_sep === FALSE) {
			return $stack;
		} else {
			return substr($stack, 0, $pos_sep);
		}
	}

	static public function removePrefix(string $string, string $prefix): string
	{
		if (strncasecmp($string, $prefix, strlen($prefix)) === 0) {
			$string = substr($string, strlen($prefix));
		}
		return $string;
	}

	static public function utf8StartsWithUpper(string $str): bool
	{
		$chr = mb_substr ($str, 0, 1, "UTF-8");
		return mb_strtolower($chr, "UTF-8") != $chr;
	}


	static public function getAppLocaleLanguage(): string
	{
		return str_replace("-", "_", Yii::$app->language);
	}

	static public function incrStr(string $str, int $inc = 1): string
	{
		if( preg_match('/([0-9]+)[^0-9]*$/', $str, $matches) ) {
			$value = $matches[1];
			$vlen = strlen($value);
			$newvalue = intval($value) + $inc;
			$newvlen = strlen(strval($newvalue));
			if( $newvlen < $vlen ) {
				$newvalue = substr($value,0,$vlen-$newvlen) . $newvalue;
			}
			return preg_replace('/([0-9]+)([^0-9]*)$/', "$newvalue$2", $str);
		} else {
			return $inc;
		}
	}

	/**
	* Strip the namespace from the class to get the actual class name
	*
	* @param string $obj Class name with full namespace
	*
	* @return string
	* @access public
	*/
	static public function stripNamespaceFromClassName($obj)
	{
		$classname = get_class($obj);
		if (preg_match('@\\\\([\w]+)$@', $classname, $matches)) {
			$classname = $matches[1];
		}
		return $classname;
	}


	static public function lastWord($sentence, $sep = ' ')
	{
		$last_word_start = strrpos($sentence, $sep);
		if ($last_word_start === false) {
			return $sentence;
		}
		// +1 so we don't include the space in our result
		return substr($sentence, $last_word_start + 1);
	}

	public static function camelCase($str, array $noStrip = [])
	{
		// non-alpha and non-numeric characters become spaces
		$str = preg_replace('/[^a-z0-9' . implode("", $noStrip) . ']+/i', ' ', trim($str));
		// uppercase the first character of each word
		$str = ucwords($str);
		$str = str_replace(" ", '', $str);
		return $str;
	}

	/**
	 * Concatenates the values of each element of an array
	 */
	static public function concatArrayValues($array, $keyname, $valuesname, $delimiter = ", ")
	{
		$ret = [];
		foreach( $array as $element ) {
			$key = $value = '';
			foreach ( $element as $element_key => $element_value ) {
				if( $element_key == $keyname ) {
					$key = $element_value;
				} else {
					if( $value != '') {
						$value .= $delimiter;
					}
					$value .= $element_value;
				}
			}
			$ret[$key] = $value;
		}
		return $ret;
	}

	// for mb_ucfirst use StringHelper::mb_ucfirst
    static public function mb_lcfirst(string $str, $encoding = 'UTF-8')
    {
        return mb_strtolower( mb_substr($str, 0, 1, $encoding), $encoding)
        . mb_substr($str, 1, mb_strlen($str), $encoding);
    }

	static public function mb_strcasecmp(string $str1, string $str2, string $encoding = null)
	{
		if (null === $encoding) { $encoding = mb_internal_encoding(); }
		return strcmp(mb_strtoupper($str1, $encoding), mb_strtoupper($str2, $encoding));
	}

	static public function yiiparam(string $name, $default = null)
	{
		if ( isset(Yii::$app->params[$name]) ) {
			return Yii::$app->params[$name];
		} else {
			return $default;
		}
	}


	/**
	* @param array      $array
	* @param int|string $position
	* @param mixed      $insert
	*/
	static public function array_insert(&$array, $position, $insert)
	{
		if (is_int($position)) {
			array_splice($array, $position, 0, $insert);
		} else {
			$pos   = array_search($position, array_keys($array));
			$array = array_merge(
				array_slice($array, 0, $pos),
				$insert,
				array_slice($array, $pos)
			);
		}
	}

    public static function mergeAndConcat(array $keys_to_concat, ...$args)
    {
		$res = [];
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if ($v instanceof UnsetArrayValue) {
                    unset($res[$k]);
                } elseif ($v instanceof ReplaceArrayValue) {
                    $res[$k] = $v->value;
                } elseif (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = static::merge($res[$k], $v);
                } else {
					if( in_array($k, $keys_to_concat) && isset($res[$k])) {
						$res[$k] .= " $v";
					} else {
						$res[$k] = $v;
					}
                }
            }
        }
        return $res;
    }

    static public function fileExtension($url)
    {
		$url_parts = parse_url($url);
		if( count($url_parts) != 1 ) {
			if( isset($url_parts['path']) ) {
				return pathinfo($url_parts['path'], PATHINFO_EXTENSION);
			}
		}
		return null;
	}


	static public function dumpHtml($var, $title = null)
	{
		if( $title ) {
			echo "<h1>$title</h1>";
		}
        echo "\n<pre>";
        print_r($var);
        echo "</pre><br/>";
    }

    static public function dump($var)
	{
        print_r($var);
        echo "\n";
    }

	/**
	 * @warning Use only for tablename.field_name. For more nested fldnames, use splitString
	 */
	static public function splitFieldName($fieldname, $reverse = true): array
	{
		if( $reverse ) {
			$dotpos = strrpos($fieldname, '.');
		} else {
			$dotpos = strpos($fieldname, '.');
		}
		if( $dotpos !== FALSE ) {
			$fldname = substr($fieldname, $dotpos + 1);
			$tablename = substr($fieldname, 0, $dotpos);
			return [ $tablename, $fldname ];
		} else {
			return [ "", $fieldname ];
		}
	}

	static public function splitString($s, $sep): array
	{
		$pos = strpos($s, $sep);
		if( $pos !== FALSE ) {
			$last = substr($s, $pos + 1);
			$first = substr($s, 0, $pos);
			return [ $first, $last];
		} else {
			return [ "", $s ];
		}
	}


	static public function rmdirRecursive(string $dir)
	{
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir") {
						static::rmdirRecursive($dir . "/" . $object);
					} else {
						unlink($dir . "/" . $object);
					}
				}
			}
			rmdir($dir);
		}
	}

	static public function implodeWithLastSep(array $values, string $sep, string $last_sep)
	{
		$values_display = '';
		$count_values = count($values);
		for ($nv = 0; $nv < $count_values; $nv++) {
			if ($values_display != '') {
				if ($nv == $count_values-1 ) {
					$values_display .= ' y ';
				} else {
					$values_display .= ', ';
				}
			}
			$values_display .= $values[$nv];
		}
		return $values_display;
	}


	static public function modelize(string $identifier, bool $nochangefirst = false): string
	{
		$parts = explode('_', str_replace(['-',' '],['_','_'],$identifier));
		$ret = '';
		if ($nochangefirst) {
			$ret = array_shift($parts);
		}
		foreach ($parts as $part) {
			$ret .= StringHelper::mb_ucfirst($part);
		}
		return $ret;
	}

	static public function getFirstLines(string $string, int $nlines): string
	{
		$lines = [];
		$count = 0;
		$offset = 0;

		while ($count < $nlines && ($pos = strpos($string, "\n", $offset)) !== false) {
			$lines[] = substr($string, $offset, $pos - $offset);
			$offset = $pos + 1;
			$count++;
		}

		// Add the last line if there are fewer than $nlines lines
		if ($count < $nlines && $offset < strlen($string)) {
			$lines[] = substr($string, $offset);
		}

		return implode("\n", $lines);
	}

	static public function arrayToShellArgs($array) {
		$args = [];
		foreach ($array as $key => $value) {
			if (is_bool($value)) {
				if ($value) {
					$args[] = escapeshellarg("--$key");
				}
			} else {
				$args[] = escapeshellarg("--$key=" . $value);
			}
		}
		return implode(' ', $args);
	}

	static public function htmlToMarkDown(string $html): string
	{
		$converter = new HtmlConverter();
		return $converter->convert($html);
	}

	// copied from Symfony\Component\DomCrawler/Crawler
	static public function normalizeWhitespace(string $string): string
	{
		return trim(preg_replace("/(?:[ \n\r\t\x0C]{2,}+|[\n\r\t\x0C])/", ' ', $string), " \n\r\t\x0C");
	}

}
