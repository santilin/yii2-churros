<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace santilin\churros\widgets\grid;

use Yii;
use yii\helpers\{Html,Url};
use santilin\churros\helpers\{FormHelper,GridFiltersHelper};


class ActionColumn extends \yii\grid\ActionColumn
{
    public $template = '{view}{update}{delete}{duplicate}';
    public $iconClassPrefix = 'fas fa';
	public $icons = [
		'view' => '<i class="fas fa-eye"></i>',
		'update' => '<i class="fas fa-pencil-alt"></i>',
		'delete' => '<i class="fas fa-trash"></i>',
		'duplicate' => '<i class="fas fa-clone"></i>',
	];
    public array|bool $crudPerms = true; // by default, all perms are granted
	public array $extraButtonsOptions = [];
	/**
	 * Icono que se muestra en la fila de filtros mientras se envía el filtro
	 * (churros.grid.js le quita `d-none` en el evento `beforeFilter` de
	 * yii.gridView.js). `false` para no mostrarlo. Si se sustituye por un
	 * icono propio, tiene que conservar la clase `loading-filters`: es lo
	 * único que usa el JS para encontrarlo.
	 */
	public string|bool $loadingFilterIcon = '<i class="fas fa-spinner fa-spin loading-filters d-none" aria-hidden="true"></i>';

    /**
     * Initializes the default button rendering callbacks.
     */
    protected function initDefaultButtons()
    {
        if ($this->crudPerms === null) {
			$this->crudPerms = [ 'create', 'view', 'update', 'delete' ];
		}
		$options = $this->buttonOptions;
        if (FormHelper::hasPermission($this->crudPerms, 'view')) {
			if (!isset($this->buttons['view'])) {
				$this->initDefaultButton('view', 'view', array_merge(
					['title' => Yii::t('churros', 'View')],
					$this->extraButtonsOptions['view']??[]));
			}
		}
        if (FormHelper::hasPermission($this->crudPerms, 'update')) {
			if (!isset($this->buttons['update'])) {
				$this->initDefaultButton('update', 'update', array_merge(
					['title' => Yii::t('churros', 'Update')],
					$this->extraButtonsOptions['update']??[]));
			}
		}
        if (FormHelper::hasPermission($this->crudPerms, 'delete')) {
			if (!isset($this->buttons['delete'])) {
				$this->initDefaultButton('delete', 'delete', array_merge(
					[ 'title' => Yii::t('churros', 'Delete'),
					  'data-confirm' => Yii::t('yii', 'Are you sure you want to delete this item?'),
					  'data-method' => 'post' ],
					$this->extraButtonsOptions['delete']??[]));
			}
		}
        if (FormHelper::hasAllPermissions($this->crudPerms, ['duplicate'])) {
			if (!isset($this->buttons['duplicate'])) {
				$this->initDefaultButton('duplicate', 'duplicate', array_merge(
					[ 'title' => Yii::t('churros', 'Duplicate')],
					$this->extraButtonsOptions['duplicate']??[]));
			}
		}
		foreach( $this->buttons as $index => $button) {
			if (!str_contains($this->template, '{'.$index.'}')) {
				$this->template .= '{'.$index.'}';
			}
		}
    }

	/**
     * Initializes the default button rendering callback for single button.
     * @param string $name Button name as it's written in template
     * @param string $iconName The part of Bootstrap glyphicon class that makes it unique
     * @param array $additionalOptions Array of additional options
     * @since 2.0.11
     */
	protected function initDefaultButton($name, $iconName, $additionalOptions = [])
	{
		if (!isset($this->buttons[$name]) && strpos($this->template, '{' . $name . '}') !== false) {
			$this->buttons[$name] = function ($url, $model, $key) use ($name, $iconName, $additionalOptions) {
				if (!empty($additionalOptions['no-confirm'])) {
					unset($additionalOptions['data-confirm'], $additionalOptions['no-confirm']);
				}
				$options = array_merge($this->buttonOptions, $additionalOptions);
				Html::addCssClass($options, $this->buttonOptions['class']??[]);
                Html::addCssClass($options, $name);
				if (empty($options['aria-label'])) {
					$options['aria-label'] = $options['title'];
				}
				$icon = isset($this->icons[$iconName])
					? $this->icons[$iconName]
					: Html::tag('span', '', ['class' => "{$this->iconClassPrefix}-$iconName"]);
				return Html::a($icon, $url, $options);
			};
		}
	}

    /**
	 * Creates a URL for the given action and model.
	 * This method is called for each button and each row.
	 * @param string $action the button name (or action ID)
	 * @param \yii\db\ActiveRecordInterface $model the data model
	 * @param mixed $key the key associated with the data model
	 * @param int $index the current row index
	 * @return string the created URL
	 */
	public function createUrl($action, $model, $key, $index)
	{
		if (is_callable($this->urlCreator, true)) { // Added true so that an array can be passed
			return call_user_func($this->urlCreator, $action, $model, $key, $index, $this);
		}

		$params = is_array($key) ? $key : ['id' => (string) $key];
		$params[0] = $this->controller ? $this->controller . '/' . $action : $action;
		// Preserve grid filters and order
		if ($action == 'delete') {
			$params += [ 'returnTo' => Yii::$app->request->url ];
		}
		return Url::toRoute($params);
	}

	public function renderFilterCell()
	{
		$loadingIcon = $this->loadingFilterIcon !== false ? $this->loadingFilterIcon : '';
		return Html::tag('td', $this->renderSaveFiltersButton() . <<<html
<a class="remove-filters me-1 ms-1" role="button" data-pjax="1" href="javascript:void(0);"
onclick="ChurrosGrid.resetFilters('{$this->grid->id}'); return false;"><i class="fas fa-filter-circle-xmark text-secondary"></i></a><a class="run-filters me-1" role="button" href="javascript:void(0);"><i class="fas fa-filter text-secondary"></i></a>{$loadingIcon}
html
		, $this->filterOptions);
	}

	/**
	 * Botón "clip": mientras esté activo, los filtros de este grid se guardan
	 * en la sesión y se restauran al volver. Sólo se pinta si la app ha activado
	 * GridFiltersHelper::PARAM_SWITCH, así por defecto nada cambia.
	 */
	protected function renderSaveFiltersButton(): string
	{
		if (!GridFiltersHelper::isEnabled()) {
			return '';
		}
		// La persistencia la hace CrudReadOnlyController::beforeAction(): fuera
		// de un controlador CRUD el botón no tendría a quién pedirle nada
		if (!(Yii::$app->controller instanceof \santilin\churros\CrudReadOnlyController)) {
			return '';
		}
		$filterModel = $this->grid->filterModel;
		if (!is_object($filterModel)) {
			return '';
		}
		$on = GridFiltersHelper::isSaved($filterModel->formName());
		$title = $on
			? Yii::t('churros', 'The filters of this grid are saved. Click to stop saving them')
			: Yii::t('churros', 'Click to save the filters of this grid');
		return Html::a('<i class="fa-solid fa-thumbtack"></i>', 'javascript:void(0);', [
			'class' => 'save-filters me-1' . ($on ? ' on' : ''),
			'role' => 'button',
			'data-state' => $on ? 'on' : 'off',
			'title' => $title,
			'aria-label' => $title,
			'onclick' => "ChurrosGrid.saveFilters('{$this->grid->id}'); return false;",
		]);
	}


}
