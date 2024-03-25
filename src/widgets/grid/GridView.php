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

class GridView extends SimpleGridView
{
	public $selectViews = [];
	public $selectViewsOptions = [];
	public $toolbarButtons = [];
	public $toolbarButtonsOptions = [];
	public $condensed = false;
	public $hover = false;
	public $layout = "{summary}\n{selectViews}\n{items}\n{pager}";


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
			$this->emptyText = Yii::t('churros', 'No {items} found.', $configItems);
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
			case '{selectViews}':
				return $this->renderSelectViews();
			case '{summaryWithSelectViews}':
				return $this->renderSummaryWithSelectViews();
            default:
                return parent::renderSection($name);
        }
    }

    // Dont show emptyText here, emptyText is managed in the summary section.
    public function renderItems()
    {
		if ($this->filterModel && $this->dataProvider->getCount() == 0) {
			$has_filters = false;
			$filter_attrs = $this->filterModel->activeAttributes();
			foreach ($filter_attrs as $attr) {
				if (property_exists($this->filterModel, $attr) || $this->filterModel->hasAttribute($attr)) {
					$has_filters = true;
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
		if( count($this->selectViews) ) {
			$selectViewsOptions = $this->selectViewsOptions;
			$tag = ArrayHelper::remove($selectViewsOptions, 'tag', 'span');
			return Html::tag($tag,
				FormHelper::displayButtons([$this->selectViews]), $selectViewsOptions);
		} else {
			return '';
		}
	}

    public function renderToolbar()
    {
		if( count( $this->toolbarButtons) ) {
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

}
