<?php
namespace santilin\churros;

use yii\web\AssetBundle;

class DateFormatterAsset extends AssetBundle
{
    public $sourcePath = '@vendor/kartik-v/php-date-formatter';
    public $js = [
        YII_ENV_DEV ? 'js/php-date-formatter.js' : 'js/php-date-formatter.min.js',
    ];
}

