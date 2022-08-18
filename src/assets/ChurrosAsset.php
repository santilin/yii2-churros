<?php

namespace santilin\churros\assets;

use yii\web\AssetBundle;

/**
 * This asset bundle provides the javascript files for client validation.
 */
class ChurrosAsset extends AssetBundle
{
    public $sourcePath = '@churros/assets';
    public $js = [
		'churros.validation.js'
    ];
    public $css = [
		'churros.radioimages.css',
		'churros.recordview.css'
    ];
}

