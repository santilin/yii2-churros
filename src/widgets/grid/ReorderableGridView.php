<?php
namespace santilin\churros\widgets\grid;

use Yii;
use yii\helpers\{Html, Json, Url};
use yii\web\JsExpression;
use santilin\churros\widgets\ReorderableListAsset;

/**
 * A {@see GridView} whose data rows can be reordered by drag & drop.
 *
 * It extends the full-featured churros {@see GridView} (itself a {@see SimpleGridView})
 * so it is a drop-in replacement for the generated `_grid.php` grids: it prepends a
 * grab-handle column and makes the table body sortable (jQuery UI). When a row is
 * dropped in a new position, the ordered list of the rows' `data-key` values is sent
 * (AJAX) to the controller's `reorder` action under the {@see $reorderKeysParam} name.
 *
 * Usage:
 * ```php
 * echo ReorderableGridView::widget([
 *     'dataProvider' => $dataProvider,
 *     'columns' => [...],
 *     // 'reorderUrl' => ['reorder'],       // where the new order is posted
 *     // 'reorderKeysParam' => 'keys',      // keys[] = <data-key>
 * ]);
 * ```
 */
class ReorderableGridView extends GridView
{
	/**
	 * @var array|string|false URL of the reorder action. Defaults to `['reorder']`
	 * in the current controller. Set to false for a client-only reorder (no server call).
	 */
	public $reorderUrl = ['reorder'];
	/** @var string HTTP method used to post the new order */
	public $reorderMethod = 'post';
	/** @var string POST parameter carrying the ordered keys (posted as `<param>[]=key`) */
	public $reorderKeysParam = 'keys';
	/** @var array extra key/value pairs posted alongside the keys (e.g. ['relation_name' => 'jsonModuleMenuItem']) */
	public $reorderData = [];
	/** @var bool whether to prepend a drag-handle column */
	public $showHandleColumn = true;
	/** @var string the grab-handle icon markup */
	public $handleIcon = '<i class="fa-solid fa-grip-vertical"></i>';
	/** @var string css class of the handle element (also the sortable `handle` selector) */
	public $handleClass = 'reorder-handle';
	/** @var array extra options merged into the jQuery UI `sortable()` call */
	public $sortableOptions = [];

	public function init()
	{
		if ($this->showHandleColumn) {
			$icon = $this->handleIcon;
			$cls = $this->handleClass;
			$this->columns = array_merge([
				'__reorder_handle__' => [
					'class' => DataColumn::class,
					'format' => 'raw',
					'header' => '',
					'filter' => false,
					'contentOptions' => ['class' => $cls . '-cell'],
					'headerOptions' => ['class' => $cls . '-cell'],
					'filterOptions' => ['class' => $cls . '-cell'],
					'content' => function ($model, $key, $index) use ($icon, $cls) {
						return Html::tag('span', $icon, [
							'class' => $cls,
							'title' => Yii::t('churros', 'Drag to reorder'),
							'style' => 'cursor: grab;',
						]);
					},
				],
			], $this->columns);
		}
		Html::addCssClass($this->options, 'reorderable-grid');
		parent::init();
	}

	public function run()
	{
		$view = $this->getView();
		ReorderableListAsset::register($view);
		$this->registerReorderAssets();
		return parent::run();
	}

	protected function registerReorderAssets(): void
	{
		$view = $this->getView();
		$id = $this->options['id'];
		// the container id may contain dashes; sanitize it for a valid JS function name
		$fn = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $id);
		$handle = '.' . $this->handleClass;

		$view->registerCss(<<<css
#$id .{$this->handleClass}-cell { width: 1%; white-space: nowrap; text-align: center; }
#$id tr.ui-sortable-helper { display: table; }
#$id tr.ui-sortable-placeholder { visibility: visible; background: rgba(0,0,0,.05); }
css
		);

		// keep the cell widths while dragging a <tr>
		$helper = new JsExpression(
			'function (e, tr) { var $o = tr.children(); var $h = tr.clone();'
			. ' $h.children().each(function (i) { jQuery(this).width($o.eq(i).width()); });'
			. ' return $h; }'
		);
		$sortable = Json::encode(array_merge([
			'items' => '> tr[data-key]',
			'handle' => $handle,
			'axis' => 'y',
			'cursor' => 'grabbing',
			'tolerance' => 'pointer',
			'helper' => $helper,
			'update' => new JsExpression("function (event, ui) { churrosReorderGrid_$fn(); }"),
		], $this->sortableOptions));

		$postJs = '';
		if ($this->reorderUrl !== false && $this->reorderUrl !== null) {
			$url = Url::to($this->reorderUrl);
			$param = $this->reorderKeysParam;
			$method = strtoupper($this->reorderMethod);
			$extra = Json::encode(array_merge($this->reorderData, ['_csrf' => new JsExpression('yii.getCsrfToken()')]));
			$postJs = <<<js
	var data = $extra;
	data['$param'] = keys;
	function churrosReorderAlert$fn(level, messages) {
		if (!messages || !messages.length) { return; }
		var box = jQuery('<div class="alert alert-' + level + ' alert-dismissible fade show reorder-alert" role="alert">'
			+ '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
			+ '</div>');
		var items = [];
		jQuery.each(messages, function (i, m) {
			items.push(jQuery('<div>').text(m).html());
		});
		box.prepend(items.join('<br>'));
		box.find('.btn-close').on('click', function () { box.remove(); });
		jQuery('#$id').before(box);
	}
	jQuery.ajax({
		url: '$url',
		method: '$method',
		data: data,
		success: function (response) {
			if (response && response.result === 'error') {
				var msgs = (response.error || []).slice();
				if (response.warning && response.warning.length) {
					msgs = msgs.concat(response.warning);
				}
				churrosReorderAlert$fn(msgs.length ? 'danger' : 'warning', msgs);
			} else if (response && response.warning && response.warning.length) {
				churrosReorderAlert$fn('warning', response.warning);
			}
		},
		error: function (xhr) {
			console.error('reorder failed', xhr && xhr.responseText);
			churrosReorderAlert$fn('danger', [xhr && xhr.responseText ? xhr.responseText : 'reorder failed']);
		}
	});
js;
		}

		$js = <<<js
function churrosReorderGrid_$fn() {
	var keys = [];
	jQuery('#$id table > tbody').first().children('tr[data-key]').each(function () {
		keys.push(jQuery(this).attr('data-key'));
	});
$postJs
}
(function () {
	var \$tbody = jQuery('#$id table > tbody').first();
	if (!\$tbody.length || typeof \$tbody.sortable !== 'function') { return; }
	\$tbody.sortable($sortable);
	if (typeof \$tbody.disableSelection === 'function') { \$tbody.disableSelection(); }
})();
js;
		$view->registerJs($js);
	}

} // class
