<?php
use yii\helpers\Html;
/* @var $this yii\web\View */
/* @var $model  */
?>
<div class="form-group">
<?php if(Yii::$app->controller->action->id != 'save-as-new'): ?>
	<?= Html::submitButton($model->isNewRecord ? Yii::t('app', 'Create') : Yii::t('app', 'Update'), ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
<?php endif; ?>
<?php if(Yii::$app->controller->action->id != 'create'): ?>
	<?= Html::submitButton(Yii::t('app', 'Save As New'), ['class' => 'btn btn-info', 'value' => '1', 'name' => '_asnew']) ?>
<?php endif; ?>
	<?= Html::a(Yii::t('app', 'Cancel'), Yii::$app->request->referrer , ['class'=> 'btn btn-danger']) ?>
</div>
<?php
