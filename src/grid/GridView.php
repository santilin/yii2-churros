<?php
/**
 * Yet Another Grid Widget
 * https://dev.mysql.com/doc/refman/8.0/en/group-by-functions.html
 */
namespace santilin\churros\grid;
use yii;
use yii\helpers\{ArrayHelper,Html};
use santilin\churros\helpers\FormHelper;
use santilin\churros\yii\ViewsAsset;

class GridView extends SimpleGridView
{
	public $selectViews = [];
	public $selectViewsOptions = [];
	public $toolbarButtons = [];
	public $toolbarButtonsOptions = [];

	public function run()
	{
		$view = $this->getView();
        ViewsAsset::register($view);
        return parent::run();
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
            case '{summary}':
                return $this->renderSummary();
            case '{items}':
                return $this->renderItems();
            case '{pager}':
                return $this->renderPager();
            case '{sorter}':
                return $this->renderSorter();
            case '{toolbar}':
            case '{toolbarContainer}':
                return $this->renderToolbar();
            default:
                return false;
        }
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
			$selectViewsContent = Html::tag($tag, $this->renderSelectViews(), $selectViewsOptions);
		} else {
			$selectViewsContent = null;
		}
        $count = $this->dataProvider->getCount();
        if ($count <= 0) {
            return $selectViewsContent;
        }
		$summaryOptions = $this->summaryOptions;
        $tag = ArrayHelper::remove($summaryOptions, 'tag', 'div');
        $configItems = [
            'item' => $this->itemLabelSingle,
            'items' => $this->itemLabelPlural,
            'items-few' => $this->itemLabelFew,
            'items-many' => $this->itemLabelMany,
            'items-acc' => $this->itemLabelAccusative,
        ];
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
