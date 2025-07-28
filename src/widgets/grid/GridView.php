<?php
/**
 * Yet Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\widgets\grid;

use yii;
use yii\helpers\{ArrayHelper,Html};
use santilin\churros\helpers\FormHelper;
use santilin\churros\widgets\grid\SimpleGridView;
use santilin\churros\widgets\JSZipAsset;
use yii\bootstrap5\ActiveForm;

class GridView extends SimpleGridView
{
	public bool $embedded = false;
	public ?string $title = null;
	public array $selectViews = [];
	public array $selectViewsOptions = [];
	public array $toolbarButtons = [];
	public array $toolbarButtonsOptions = [];
	public bool $condensed = false;
	public bool $hover = false;
	public $layout = "{summary}\n{selectViews}\n{items}\n{pager}{filterCount}";


	public function init()
	{
		if ($this->emptyText === null) {
			$configItems = [
				'item' => $this->itemLabelSingle,
				'items' => $this->itemLabelPlural,
				'items-few' => $this->itemLabelFew,
				'items-many' => $this->itemLabelMany,
				'items-acc' => $this->itemLabelAccusative,
			];
			$this->emptyText = Yii::t('churros', 'No {items} found.', [ 'items' => mb_lcfirst($configItems['items']??'items') ]);
		}
		parent::init();
	}

    /**
     * Renders a section of the specified name.
     * If the named section is not supported, false will be returned.
     * @param string $name the section name, e.g., `{summary}`, `{items}`.
     * @return string|bool the rendering result of the section, or false if the named section is not supported.
     */
    public function renderSection($name)
    {
        switch ($name) {
            case '{toolbar}':
            case '{toolbarContainer}':
                return $this->renderToolbar();
			case '{title}':
				return $this->renderTitle();
			case '{summaryWithTitle}':
				return $this->renderSummaryWithTitle();
			case '{filterCount}':
				return $this->renderFilterCount();
			case '{selectViews}':
				return $this->renderSelectViews();
			case '{summaryWithSelectViews}':
				return $this->renderSummaryWithSelectViews();
            default:
                return parent::renderSection($name);
        }
    }

    public function renderFilterCount()
    {
		$pagination = $this->dataProvider->getPagination();
        if ($pagination === false || $pagination->totalCount <= 6) {
            return '';
        }
        $urls = [];
		$html = "<nav><ul class=\"pagination grid-items-per-page\"><li class=\"d-flex align-items-center\">" . Yii::t('churros', 'Rows per page') . '&nbsp;</li>';
        foreach ([6 => '6', 12 => '12',24 => '24', 60 => '60', -1 => Yii::t('churros', 'All')] as $page_size => $label) {
			if ($pagination->pageSize == $page_size) {
				$li_class = ' class="page-item active"';
			} else {
				$li_class = ' class="page-item"';
			}
			if ($page_size == -1) {
				$pagination_url = $pagination->createUrl(0, 999999999);
			} else {
				$pagination_url = $pagination->createUrl($pagination->page, $page_size);
			}
			$html.= "<li$li_class>" . Html::a($label, $pagination_url, ['class' => 'page-link', 'data' => ['pjax' => '1']]) . '</li>';
		}
		$html .= '</ul></nav>';
		return $html;
    }

    // Dont show emptyText here, emptyText is managed in the summary section.
    public function renderItems()
    {
		if ($this->filterModel && $this->dataProvider->getCount() == 0) {
			// No mostrar la fila de filtro si no hay valores filtrados
 			$filter_attrs = $this->filterModel->activeAttributes();
			$has_filters = false;
			foreach ($filter_attrs as $attribute) {
				$v = $this->filterModel->$attribute;
				if (!empty($v)) {
					$has_filters = true;
					break;
				}
			}
 			if (!$has_filters) {
				$this->showHeader = false;
			}
		}
		$empty_text_save = $this->emptyText;
		$this->emptyText = false;
		$ret = parent::renderItems();
		$this->emptyText = $empty_text_save;
		return $ret;
	}

	protected function renderSelectViews()
	{
		if (count($this->selectViews)) {
			$selectViewsOptions = array_merge(['class' => 'mb-2'], $this->selectViewsOptions);
			$tag = ArrayHelper::remove($selectViewsOptions, 'tag', 'div');
			return Html::tag($tag,
				FormHelper::displayButtons([$this->selectViews]),
				$selectViewsOptions);
		} else {
			return '';
		}
	}

    public function renderToolbar()
    {
		if (count($this->toolbarButtons)) {
			if (isset($this->toolbarButtons['export'])) {
				JSZipAsset::register($this->getView());
			}
			$toolbarButtonsOptions = $this->toolbarButtonsOptions;
			Html::addCssClass($toolbarButtonsOptions, 'toolbar');
			$tag = ArrayHelper::remove($toolbarButtonsOptions, 'tag', 'div');
			$toolbarButtonsContent = FormHelper::displayButtons($this->toolbarButtons,'');
			return Html::tag($tag, $toolbarButtonsContent, $toolbarButtonsOptions);
		} else {
			return '';
		}
    }

    public function renderSummaryWithSelectViews()
    {
		$embedded = $this->embedded;
		$title = $this->title;
		$selectViewsContent = $this->renderSelectViews();
        $count = $this->dataProvider->getCount(); // records in current page
		$summaryOptions = $this->summaryOptions;
		$configItems = [
			'item' => $this->itemLabelSingle,
			'items' => $this->itemLabelPlural,
			'items-few' => $this->itemLabelFew,
			'items-many' => $this->itemLabelMany,
			'items-acc' => $this->itemLabelAccusative,
		];
        if ($count == 0) {
			$ret = $this->renderSelectViews();
			if( $this->emptyText !== false ) {
				$ret = Html::tag('div',
					Yii::t('churros', $this->emptyText, $configItems) . ' ' . $ret, $summaryOptions);
			}
			return $ret;
        } else {
			$tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');
			$pagination = $this->dataProvider->getPagination();
			if ($pagination !== false) {
				$totalCount = $this->dataProvider->getTotalCount();
				$begin = $pagination->getPage() * $pagination->pageSize + 1;
				$end = $begin + $count - 1;
				if ($begin > $end) {
					$begin = $end;
				}
				$page = $pagination->getPage() + 1;
				$pageCount = $pagination->pageCount;
				$configSummary = [
					'begin' => $begin,
					'end' => $end,
					'count' => $count,
					'totalCount' => $totalCount,
					'page' => $page,
					'pageCount' => $pageCount,
				];
				if (($summaryContent = $this->summary) === null) {
					if ($pageCount <= 1) {
						$counts = Yii::t('churros', 'Showing <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.', $configSummary + $configItems);
					} else {
						$counts = Yii::t('churros', 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.', $configSummary + $configItems);
					}
					if( $selectViewsContent ) {
						$counts .= ' ' . $selectViewsContent;
					}
					if ($embedded && $title) {
						$counts = Html::tag('div', $counts, ['class' => 'supertitle']) . $title;
					}
					return Html::tag($tag, $counts, $summaryOptions);
				}
			} else {
				$begin = $page = $pageCount = 1;
				$end = $totalCount = $count;
				$configSummary = [
					'begin' => $begin,
					'end' => $end,
					'count' => $count,
					'totalCount' => $totalCount,
					'page' => $page,
					'pageCount' => $pageCount,
				];
				if (($summaryContent = $this->summary) === null) {
					return $selectViewsContent . ' ' . Html::tag($tag,
						Yii::t('churros', 'Total <b>{count, number}</b> {count, plural, one{{item}} other{{items}}}.',
							$configSummary + $configItems
						), $summaryOptions);
				}
			}
			return $selectViewsContent . ' ' .  Yii::$app->getI18n()->format($summaryContent, $configSummary, Yii::$app->language);
		}
    }

    public function renderTitle()
	{
		if ($this->title) {
			if (!$this->embedded) {
				return Html::tag('h1', $this->title, ['class' => 'title']);
			} else {
				return Html::tag('div', $this->title, ['class' => 'title']);
			}
		} else {
			return '';
		}
	}

	public function renderSummaryWithTitle()
	{
		$summaryOptions = $this->summaryOptions;
		$tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');
		Html::addCssClass($summaryOptions, 'supertitle');
		$configItems = [
			'item' => $this->itemLabelSingle,
			'items' => $this->itemLabelPlural,
			'items-few' => $this->itemLabelFew,
			'items-many' => $this->itemLabelMany,
			'items-acc' => $this->itemLabelAccusative,
		];
		$summary = '';
		$count = $this->dataProvider->getCount(); // records in current page
		if ($count == 0) {
			if ($this->emptyText !== false) {
				$summary = $this->title;
				$this->title = $this->emptyText;
			}
		} else {
			$pagination = $this->dataProvider->getPagination();
			if ($pagination !== false) {
				$totalCount = $this->dataProvider->getTotalCount();
				$begin = $pagination->getPage() * $pagination->pageSize + 1;
				$end = $begin + $count - 1;
				if ($begin > $end) {
					$begin = $end;
				}
				$page = $pagination->getPage() + 1;
				$pageCount = $pagination->pageCount;
				$configSummary = [
					'begin' => $begin,
					'end' => $end,
					'count' => $count,
					'totalCount' => $totalCount,
					'page' => $page,
					'pageCount' => $pageCount,
				];
				if (($summaryContent = $this->summary) === null) {
					if ($pageCount <= 1) {
						if ($this->title !== false && $this->title == '') {
							$summary = Yii::t('churros', "Showing <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.", $configSummary + $configItems);
						} else {
							$summary = Yii::t('churros', 'Showing <b>{totalCount, number}</b>', $configSummary + $configItems);
						}
					} else {
						if ($this->title !== false && $this->title == '') {
							$summary = Yii::t('churros', "Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.", $configSummary + $configItems);
						} else {
							$summary = Yii::t('churros', 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b>', $configSummary + $configItems);
						}
					}
				} else if ($summaryContent !== false) {
					$summary = Yii::$app->getI18n()->format($summaryContent, $configSummary, Yii::$app->language);
				}
			} else {
				$begin = $page = $pageCount = 1;
				$end = $totalCount = $count;
				$configSummary = [
					'begin' => $begin,
					'end' => $end,
					'count' => $count,
					'totalCount' => $totalCount,
					'page' => $page,
					'pageCount' => $pageCount,
				];
				if (($summaryContent = $this->summary) === null) {
					$summary = Yii::t('churros', 'Total <b>{count, number}</b>', $configSummary + $configItems);;
				} else if ($summaryContent !== false) {
					$summary = Yii::$app->getI18n()->format($summaryContent, $configSummary, Yii::$app->language);
				}
			}
		}
		$title = $this->renderTitle();
		return Html::tag('div', Html::tag($tag, $summary, $summaryOptions)
			. Html::tag('div', $title), [ 'class' => 'title-container']);
	}

    /**
     * @inheritdoc
     */
    public function renderSummary()
    {
        $count = $this->dataProvider->getCount();
		$summaryOptions = $this->summaryOptions;
		$configItems = [
			'item' => $this->itemLabelSingle,
			'items' => $this->itemLabelPlural,
			'items-few' => $this->itemLabelFew,
			'items-many' => $this->itemLabelMany,
			'items-acc' => $this->itemLabelAccusative,
		];
        if ($count == 0) {
			if ($this->emptyText !== false) {
				return Html::tag('div',
					Yii::t('churros', $this->emptyText, $configItems),
					$summaryOptions);
			}
			return '';
        } else {
			$tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');
			$pagination = $this->dataProvider->getPagination();
			if ($pagination !== false) {
				$totalCount = $this->dataProvider->getTotalCount();
				$begin = $pagination->getPage() * $pagination->pageSize + 1;
				$end = $begin + $count - 1;
				if ($begin > $end) {
					$begin = $end;
				}
				$page = $pagination->getPage() + 1;
				$pageCount = $pagination->pageCount;
				$configSummary = [
					'begin' => $begin,
					'end' => $end,
					'count' => $count,
					'totalCount' => $totalCount,
					'page' => $page,
					'pageCount' => $pageCount,
				];
				if (($summaryContent = $this->summary) === null) {
					return Html::tag($tag, Yii::t('churros',
						'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.',
						$configSummary + $configItems ), $summaryOptions);
				}
			} else {
				$begin = $page = $pageCount = 1;
				$end = $totalCount = $count;
				$configSummary = [
					'begin' => $begin,
					'end' => $end,
					'count' => $count,
					'totalCount' => $totalCount,
					'page' => $page,
					'pageCount' => $pageCount,
				];
				if (($summaryContent = $this->summary) === null) {
					return Html::tag($tag,
						Yii::t('churros', 'Total <b>{count, number}</b> {count, plural, one{{item}} other{{items}}}.', $configSummary + $configItems ),
						$summaryOptions);
				}
			}
			return Yii::$app->getI18n()->format($summaryContent, $configSummary, Yii::$app->language);
		}
    }

    const EXPORT_BUTTONS = [
		'export' => [
			'type' => 'button',
			'title' => 'Exportar',
			'url' => "javascript:ChurrosGrid.createODSFile('grid-evaluacion-_grid_matriculas', 'table_export', ['filters']);",
			'htmlOptions' => [
				'class' => 'btn btn-secondary btn-outline'
			]
		]
	];

}
