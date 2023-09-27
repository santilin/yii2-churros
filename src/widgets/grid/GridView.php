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


    public function init()
    {
        if ($this->emptyText === null) {
            $this->emptyText = Yii::t('churros', 'No {items} found.');
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
            default:
                return parent::renderSection($name);
        }
    }

    public function renderItems()
    {
		$empty_text_save = $this->emptyText;
		$this->emptyText = false;
		$ret = parent::renderItems();
		$this->emptyText = $empty_text_save;
		return $ret;
	}

	protected function renderSelectViews()
	{
		if( count( $this->selectViews ) ) {
			return FormHelper::displayButtons( [ $this->selectViews ] );
		} else {
			return '';
		}
	}

    public function renderToolbar()
    {
		if( count( $this->toolbarButtons) ) {
			$toolbarButtonsOptions = $this->toolbarButtonsOptions;
			$tag = ArrayHelper::remove($toolbarButtonsOptions, 'tag', 'div');
			$toolbarButtonsContent = FormHelper::displayButtons( $this->toolbarButtons, '' );
			return Html::tag($tag, $toolbarButtonsContent, $toolbarButtonsOptions);
		} else {
			return '';
		}
    }

    /**
     * @inheritdoc
     */
    public function renderSummary()
    {
        if( count($this->selectViews) ) {
			$selectViewsOptions = $this->selectViewsOptions;
			$tag = ArrayHelper::remove($selectViewsOptions, 'tag', 'span');
			$selectViewsContent = ' ' . Html::tag($tag, $this->renderSelectViews(), $selectViewsOptions);
		} else {
			$selectViewsContent = null;
		}
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
			$ret = $selectViewsContent;
			if( $this->emptyText !== false ) {
				$ret .= Html::tag('div',
					Yii::t('churros', $this->emptyText, $configItems),
					$summaryOptions);
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
					if( $selectViewsContent ) {
						return Html::tag($tag, Yii::t('churros',
							'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b>',
							$configSummary + $configItems
						) . $selectViewsContent, $summaryOptions);
					} else {
						return Html::tag($tag, Yii::t('churros',
							'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{{item}} other{{items}}}.',
							$configSummary + $configItems
						) , $summaryOptions);

					}
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
					return $selectViewsContent . Html::tag($tag,
						Yii::t('churros', 'Total <b>{count, number}</b> {count, plural, one{{item}} other{{items}}}.',
							$configSummary + $configItems
						), $summaryOptions);
				}
			}
			return $selectViewsContent . Yii::$app->getI18n()->format($summaryContent, $configSummary, Yii::$app->language);
		}
    }

}
