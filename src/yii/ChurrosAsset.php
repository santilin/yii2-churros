<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\yii;

use yii\web\AssetBundle;

/**
 * The asset bundle for the [[RecordView]] widget.
 *
 * @author Santilin <software@noviolento.es>
 * @since 2.0
 */
class ChurrosAsset extends AssetBundle
{
    public $sourcePath = '@churros/assets';
    public $css = [
        'churros.views.css',
    ];
    public $depends = [
        'yii\web\YiiAsset',
    ];
}
