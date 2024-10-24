<?php

namespace santilin\churros\widgets;

use yii\web\AssetBundle;

class JSZipAsset extends AssetBundle
{
	public $sourcePath = '@churros/assets';
	public $js = [
		'jszip.min.js',
		'FileSaver.min.js',
	];
}

