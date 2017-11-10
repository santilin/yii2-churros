<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\FieldSearch */
/* @var $model_title string */

$this->params['breadcrumbs'][] = $model_title;
$search = "$('.search-button').click(function(){
	$('.search-form').toggle(1000);
	return false;
});";
$this->registerJs($search);
?>
<div class="<?= strtolower($model_title) ?>-index">

    <h1><?= Html::encode($model_title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a(Yii::t('app', 'Create {model}', ['model' => $model_title]), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('app', 'Advanced Search'), '#', ['class' => 'btn btn-info search-button']) ?>
    </p>
    <div class="search-form" style="display:none">
        <?=  $this->render( '../' . basename(Yii::$app->controller->getViewPath()) . '/_search', ['model' => $searchModel]); ?>
    </div>
<?php
