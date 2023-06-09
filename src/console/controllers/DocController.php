<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\console\controllers;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Console;
use yii\console\Controller;

/**
 * Churros code documentator
 *
 * @author Santilín <santi@noviolento.es>
 * @since 1.0
 */

class DocController extends Controller
{
	/** The version of this command */
	const VERSION = '0.1';
	private $verbose = true;
	private $suite_params = [
		'capel' => [
			'doc_pattern' => '//'.'/@',
			'find_patterns' => ["*.cpp","*.h","TODO","*.json" ],
			'find_exclude' => [],
			'source_path' => '/home/santilin/devel/capel',
			'dest_path' => '/home/santilin/devel/capel/doc/docs',
		],
		'apps' => [
			'doc_pattern' => '//'.'/@',
			'find_patterns' => ["*.php","*.js","*.css","*.tex","*.txt" ],
			'find_exclude' => [],
			'source_path' => '/home/santilin/devel/yii2base/app',
			'dest_path' => '/home/santilin/devel/yii2base/app/_doc',
		]
	];
/*
	public function options($action_id)
	{
		switch($action_id) {
			case 'Build':
				return [ "verbose" ];
				break;
			default:
				break;
		}
	}*/

	const KANBOARD_CARD_LINK = '[kb$1](https://intranet.cepaim.org.es/kanboard/?controller=TaskViewController&action=show&task_id=$1)';

	const HEADERS = [
		'intro' =>    '010.Introducción',
		'nomenclatura' => '020.Nomenclatura',
		'acceso' =>   '040.Acceso/Permisos',
		'permisos' => '040.Acceso/Permisos',
		'permiso' =>  '040.Acceso/Permisos',
		'modelo' =>   '100.Modelo',
		'db' =>       '110.Base de datos',
		'bd' =>       '110.Base de datos',
		'form' =>     '200.Formularios',


		'informes' => '300.Informes y estadísticas',
		'stats' =>    '300.Informes y estadísticas',
		'estadist' => '300.Informes y estadísticas',
		'estadis' =>  '300.Informes y estadísticas',
		'estadísticas' => '300.Informes y estadísticas',
		'estadisticas' => '300.Informes y estadísticas',
	];




	///@DOC:2 La sintaxis de la línea de comentario es: <br> `///@PATH.titulo:[orden] comentario`
	//https://engineering.empathy.co/building-a-future-proof-developer-documentation-site/
	public function actionBuild(string $suite)
	{
		$this->stdout("Generando documentación de $suite\n", Console::FG_GREEN, Console::BOLD);
		$params = $this->suite_params[$suite];
  		$code_comments = $this->getCodeComments($params['source_path'],
			$params['find_patterns'], $params['doc_pattern']);
		if ($this->verbose) {
			$this->stdout("Encontradas " . count($code_comments) . " líneas de comentarios\n");
		}
		$this->genDocFiles($code_comments, $params['doc_pattern'], $params['dest_path']);
 		exec( "cd {$params['dest_path']}/..; mkdocs build");
	}

	private function genDocFiles(array $code_comments, string $doc_pattern, string $dest_path)
	{
		$pat = "\.\/([^:]*):([0-9^:]+):\s*$doc_pattern\s*(.*?):([0-9:\-\s]+)?(.*)$";
		$comments = [];
		foreach( $code_comments as $line ) {
			$m = [];
 			if( preg_match("=$pat=", $line, $m) ) {
				$file_parts = explode('.', $m[3]);
				$file = array_shift($file_parts);
				if (empty($file_parts)) {
					$header = $this->sortHeader('intro');
				} else {
					$header = $this->sortHeader(implode('.',$file_parts));
				}
				if( preg_match('/^202[0-9][0-9][0-9][0-9][0-9]$/', $m[4], $orderm) ) {
					// Changelog
					$order = '**' . substr($order,7,2) . '/' . substr($order,5,2) . '/' . substr($order,1,4) . '** ';
				} else if( preg_match('/^([0-9]{1,4})-([0-9][0-9])-([0-9][0-9]).*$/', $m[4], $orderm ) ) {
					$order = $orderm[3] . '/' . $orderm[2] . '/' . $orderm[1] . ' ';
				} else {
					$order = str_pad(trim($m[4]), 3, '0', STR_PAD_LEFT);
				}
				$text = ":$order:" . trim($m[5]);
				$file_line = "[{$m[1]}:{$m[2]}]";
				if( !isset($comments[$file]) ) {
					$comments[$file] = [];
				}
				if( !isset($comments[$file][$header]) ) {
					$comments[$file][$header] = [];
				}
				if( !isset($comments[$file][$header][$order]) ) {
					$comments[$file][$header][$order] = [];
				}
				$comments[$file][$header][$order][] = [ $text, $file_line ];
			} else {
				$this->stderr("Línea de comentario no contiene el patrón de línea: $line\n");
			}
		}
 		foreach( $comments as $filename => $headers ) {
			$md_contents = '';
			ksort($headers);
			foreach( $headers as $header => $orders ) {
				$head_levels = explode('.', $header);
				$head_level = count($head_levels)-1;
				$header = array_pop($head_levels);
				$md_contents .= str_repeat('#', $head_level) . ' ' . $header . "\n";
				ksort($orders);
				foreach( $orders as $order ) {
					foreach( $order as $comment_info ) {
						$comment_info[0] = preg_replace(
							[ '/kb\#([0-9]+)/'        , '/^:[0-9]*:/' ],
							[ self::KANBOARD_CARD_LINK, '' ],
							$comment_info[0]);
						$md_contents .= $comment_info[0] . '<span class="doc-location hidden"> ' . $comment_info[1] . "</span><br>\n";
					}
				}
			}
			$fullfname = $dest_path . '/' . $filename . '.md';
			file_put_contents( $fullfname, $md_contents);
			if ($this->verbose) {
				echo $md_contents;
				$this->stdout("Generado $fullfname\n");
			}
 		}
	}

	protected function getCodeComments(string $source_path, array $find_patterns, string $doc_pattern): array
	{
		$result = null;
		$comments = [];
		$fnames = '';
		foreach ($find_patterns as $fname) {
			if ($fnames != '') {
				$fnames .= ' -o ';
			}
			$fnames .= "-name \"$fname\"";
		}
		if ($fnames) {
			$fnames = "-\( $fnames -\)";
		}
		if ($this->verbose) {
			$this->stdout("find . $fnames -print0 | xargs -0 grep -on \"$doc_pattern.*$\"\n");
		}
		// find . para que todos los ficheros commiencen por ./
		exec("cd $source_path; find . $fnames -print0 | xargs -0 grep -on \"$doc_pattern.*$\"", $comments, $result);
		return $comments;
	}

	private function sortHeader($header)
	{
		if( preg_match('/^([0-9]+)([^ ].*)$/', $header, $m) ) {
			return str_pad($m[1], 3, '0', STR_PAD_LEFT) . '.'. $m[2];
		} else if (isset(self::HEADERS[$header])) {
			return self::HEADERS[$header];
		} else {
			return '500.' . $header;
		}
	}

} // class
