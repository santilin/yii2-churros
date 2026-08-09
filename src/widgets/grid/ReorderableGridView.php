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
 * so it is a drop-in replacement for the generated `_grid.php` grids.
 *
 * The grid is displayed as usual until the reorder button of the toolbar is pressed.
 * Then it enters the reorder mode: the action column is hidden, the grab handles are
 * shown, the rows become sortable and a bar with the `save`/`cancel` buttons is
 * displayed between the grid header and the table. `save` posts the ordered list of
 * the rows' `data-key` values to {@see $reorderUrl} under the {@see $reorderKeysParam}
 * name; `cancel` restores the original order and leaves the reorder mode.
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
	/**
	 * @var string selector of the buttons that enter the reorder mode. It is relative
	 * to the grid container, so the `reorder` toolbar button starts it by default.
	 */
	public $reorderToggleSelector = '.reorder';
	/**
	 * @var bool whether each row gets, while reordering, a button that removes it from
	 * the table. The keys of the removed rows are not sent when the order is saved.
	 */
	public $removable = true;
	/** @var string the remove-button icon markup */
	public $removeIcon = '<i class="fa-solid fa-xmark"></i>';
	/** @var string css class of the remove button */
	public $removeClass = 'reorder-remove';
	/** @var bool whether to reload the page after the new order has been saved */
	public $reloadOnSave = true;
	/** @var array extra options merged into the jQuery UI `sortable()` call */
	public $sortableOptions = [];

	public function init()
	{
		if ($this->showHandleColumn || $this->removable) {
			$handle_icon = $this->showHandleColumn ? $this->handleIcon : '';
			$handle_cls = $this->handleClass;
			$remove_icon = $this->removable ? $this->removeIcon : '';
			$remove_cls = $this->removeClass;
			$this->columns = array_merge([
				'__reorder_handle__' => [
					'class' => DataColumn::class,
					'format' => 'raw',
					'header' => '',
					'filter' => false,
					'contentOptions' => ['class' => $handle_cls . '-cell'],
					'headerOptions' => ['class' => $handle_cls . '-cell'],
					'filterOptions' => ['class' => $handle_cls . '-cell'],
					'content' => function ($model, $key, $index)
						use ($handle_icon, $handle_cls, $remove_icon, $remove_cls) {
						$ret = '';
						if ($handle_icon !== '') {
							$ret .= Html::tag('span', $handle_icon, [
								'class' => $handle_cls,
								'title' => Yii::t('churros', 'Drag to reorder'),
								'style' => 'cursor: grab;',
							]);
						}
						if ($remove_icon !== '') {
							// same column and same look as the handle, so both are aligned
							$ret .= Html::tag('span', $remove_icon, [
								'class' => $remove_cls,
								'title' => Yii::t('churros', 'Remove this row'),
								'style' => 'cursor: pointer;',
							]);
						}
						return $ret;
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

	/**
	 * The 1-based positions of the action columns, to hide them while reordering
	 * @return int[]
	 */
	protected function actionColumnPositions(): array
	{
		$positions = [];
		$pos = 0;
		foreach ($this->columns as $column) {
			if (!$column->visible) {
				continue;
			}
			++$pos;
			if ($column instanceof \yii\grid\ActionColumn) {
				$positions[] = $pos;
			}
		}
		return $positions;
	}

	protected function registerReorderAssets(): void
	{
		$view = $this->getView();
		$id = $this->options['id'];
		// the container id may contain dashes; sanitize it for a valid JS identifier
		$fn = preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $id);
		$handle = '.' . $this->handleClass;
		$handle_cell = $this->handleClass . '-cell';
		$remove = '.' . $this->removeClass;

		$hide_actions = [];
		foreach ($this->actionColumnPositions() as $pos) {
			$hide_actions[] = "#$id.reordering table > thead > tr:not(.reorder-toolbar-row) > *:nth-child($pos)";
			$hide_actions[] = "#$id.reordering table > tbody > tr > *:nth-child($pos)";
		}
		$hide_actions = count($hide_actions)
			? implode(",\n", $hide_actions) . ' { display: none; }'
			: '';

		$view->registerCss(<<<css
#$id .$handle_cell { display: none; }
#$id.reordering .$handle_cell { display: table-cell; width: 1%; white-space: nowrap; text-align: center; }
#$id .$handle_cell > * + * { margin-left: .5rem; }
#$id $remove { color: var(--bs-danger, #dc3545); }
#$id .reorder-toolbar-row { display: none; }
#$id.reordering .reorder-toolbar-row { display: table-row; }
#$id .reorder-toolbar { display: flex; gap: .5rem; align-items: center; }
#$id tr.ui-sortable-helper { display: table; }
#$id tr.ui-sortable-placeholder { visibility: visible; background: rgba(0,0,0,.05); }
$hide_actions
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
			'disabled' => true,
		], $this->sortableOptions));

		$labels = Json::encode([
			'save' => Yii::t('churros', 'Save order'),
			'cancel' => Yii::t('churros', 'Cancel'),
			'hint' => Yii::t('churros', 'Drag the rows to their new position and save the order'),
		]);
		$toggle_selector = Json::encode($this->reorderToggleSelector);
		$reload_on_save = $this->reloadOnSave ? 'true' : 'false';

		if ($this->reorderUrl !== false && $this->reorderUrl !== null) {
			$url = Json::encode(Url::to($this->reorderUrl));
			$param = Json::encode($this->reorderKeysParam);
			$method = Json::encode(strtoupper($this->reorderMethod));
			$extra = Json::encode($this->reorderData);
		} else {
			$url = 'null';
			$param = Json::encode($this->reorderKeysParam);
			$method = '"POST"';
			$extra = '{}';
		}

		$js = <<<js
(function () {
	var \$grid = jQuery('#$id');
	var \$tbody = \$grid.find('table > tbody').first();
	if (!\$grid.length || !\$tbody.length || typeof \$tbody.sortable !== 'function') { return; }
	var labels = $labels, url = $url, order = [];

	function alertBox(level, messages) {
		if (!messages || !messages.length) { return; }
		var box = jQuery('<div class="alert alert-' + level + ' alert-dismissible fade show reorder-alert" role="alert">'
			+ '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>');
		var items = [];
		jQuery.each(messages, function (i, m) { items.push(jQuery('<div>').text(m).html()); });
		box.prepend(items.join('<br>'));
		box.find('.btn-close').on('click', function () { box.remove(); });
		\$grid.prepend(box);
	}

	var \$thead = \$grid.find('table > thead').first();
	var \$bar = \$thead.find('.reorder-toolbar').first();
	if (!\$bar.length) {
		// the buttons go in a new header row, below the header of the columns
		var cols = \$thead.children('tr').first().children().length || 1;
		var \$row = jQuery('<tr class="reorder-toolbar-row"><th></th></tr>');
		\$bar = jQuery('<div class="reorder-toolbar">'
			+ '<button type="button" class="btn btn-primary btn-sm reorder-save"></button>'
			+ '<button type="button" class="btn btn-secondary btn-sm reorder-cancel"></button>'
			+ '<span class="reorder-hint text-muted small"></span></div>');
		\$bar.find('.reorder-save').text(labels.save);
		\$bar.find('.reorder-cancel').text(labels.cancel);
		\$bar.find('.reorder-hint').text(labels.hint);
		\$row.children('th').attr('colspan', cols).append(\$bar);
		\$thead.append(\$row);
	}

	\$tbody.sortable($sortable);
	if (typeof \$tbody.disableSelection === 'function') { \$tbody.disableSelection(); }

	function start() {
		order = \$tbody.children('tr').toArray();
		\$grid.addClass('reordering');
		\$tbody.sortable('enable');
	}

	function stop() {
		\$grid.removeClass('reordering');
		\$tbody.sortable('disable');
		\$grid.find('.reorder-alert').remove();
	}

	function keys() {
		var ret = [];
		\$tbody.children('tr[data-key]').each(function () { ret.push(jQuery(this).attr('data-key')); });
		return ret;
	}

	function save() {
		if (!url) { stop(); return; }
		var data = jQuery.extend({}, $extra, { _csrf: yii.getCsrfToken() });
		data[$param] = keys();
		var \$button = \$bar.find('.reorder-save').prop('disabled', true);
		jQuery.ajax({
			url: url,
			method: $method,
			data: data,
			success: function (response) {
				\$button.prop('disabled', false);
				if (response && response.result === 'error') {
					var msgs = (response.error || []).slice();
					if (response.warning && response.warning.length) { msgs = msgs.concat(response.warning); }
					alertBox(msgs.length ? 'danger' : 'warning', msgs);
					return;
				}
				stop();
				if (response && response.warning && response.warning.length) {
					alertBox('warning', response.warning);
				}
				if ($reload_on_save) { window.location.reload(); }
			},
			error: function (xhr) {
				\$button.prop('disabled', false);
				alertBox('danger', [xhr && xhr.responseText ? xhr.responseText : 'reorder failed']);
			}
		});
	}

	\$grid.off('.churrosReorder$fn');
	\$grid.on('click.churrosReorder$fn', $toggle_selector, function (e) {
		e.preventDefault();
		start();
	});
	\$grid.on('click.churrosReorder$fn', '.reorder-save', function (e) { e.preventDefault(); save(); });
	\$grid.on('click.churrosReorder$fn', '.reorder-cancel', function (e) {
		e.preventDefault();
		// restores the original order, and the rows removed while reordering
		\$tbody.append(order);
		stop();
	});
	\$grid.on('click.churrosReorder$fn', '.{$this->removeClass}', function (e) {
		e.preventDefault();
		// the keys of the removed rows are not sent when the order is saved
		jQuery(this).closest('tr[data-key]').detach();
	});
})();
js;
		$view->registerJs($js);
	}

} // class
