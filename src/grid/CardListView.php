<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace santilin\churros\grid;
use yii;
use yii\helpers\ArrayHelper;
use yii\widgets\ListView;
use yii\helpers\Html;
use santilin\churros\yii\ViewsAsset;


/**
 * This ListView acts like a table
 */
class CardListView extends ListView
{
	public $options = [ 'class' => 'cardlistview'];
	public $itemsOptions = [ 'class' => 'card-deck' ];
	public $itemOptions = [ 'class' => 'card' ];
	public $layout = "{summarypager}\n{items}";
	public $cardsPerRow = 4;
	public $labelSingular = 'item';
	public $labelPlural = 'items';

	public function __construct($config = [])
	{
		if (empty($config['pager']) ) {
			$config['pager'] = [
				'firstPageLabel' => '<<',
				'lastPageLabel' => '>>',
				'nextPageLabel' => '>',
				'prevPageLabel' => '<',
			];
		}
		parent::__construct($config);
		switch( $this->cardsPerRow ) {
		case 0:
		case 1:
		case 2:
			Html::addCssClass($this->itemOptions, 'clv-w-50');
			break;
		case 3:
			Html::addCssStyle($this->itemOptions, 'width:33%');
			break;
		case 4:
			Html::addCssClass($this->itemOptions, 'clv-w-25');
			break;
		}
		if( empty($this->summary) ) {
			if ($this->dataProvider->getPagination() !== false) {
                $this->summary = Yii::t('churros', 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.');
			} else {
                $this->summary = Yii::t('churros', 'Total <b>{count, number}</b> {count, plural, one{item} other{items}}.');
			}
			$this->summary = strtr($this->summary, ['{item}' => '{'.$this->labelSingular.'}',
				'{items}' => '{'.$this->labelPlural.'}' ]);
		}
	}

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
			case '{pagersummary}':
				return $this->renderPagerAndSummary();
			case '{summarypager}':
				return $this->renderSummaryAndPager();
            case '{summary}':
                return $this->renderSummary();
            case '{items}':
                return Html::tag('div', $this->renderItems(), $this->itemsOptions);
            case '{pager}':
                return $this->renderPager();
            case '{sorter}':
                return $this->renderSorter();
            default:
                return false;
        }
    }

    public function renderSummaryAndPager()
    {
		$sv_options = $this->summaryOptions;
		$this->summaryOptions = [];
		$summary = '<div style="display:flex;flex-grow:1">';
		$summary .= $this->renderSummary();
		$summary .= $this->renderPager();
		$this->summaryOptions = $sv_options;
		$tag = ArrayHelper::remove($sv_options, 'tag', 'div');
		return Html::tag($tag, $summary, $this->summaryOptions)
			. '</div>';
    }

    public function renderPagerAndSummary()
    {
		$sv_options = $this->summaryOptions;
		$this->summaryOptions = [];
		$summary = '<div style="display:flex;flex-grow:1">';
		$summary .= $this->renderPager();
		$summary .= $this->renderSummary();
		$this->summaryOptions = $sv_options;
		$tag = ArrayHelper::remove($sv_options, 'tag', 'div');
		return Html::tag($tag, $summary, $this->summaryOptions)
			. '</div>';
    }


} // class

