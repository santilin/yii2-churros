<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace santilin\churros\widgets;

use yii;
use yii\helpers\{ArrayHelper,Html};
use yii\widgets\ListView;
use santilin\churros\widgets\ChurrosAsset;


/**
 * This ListView acts like a table
 */
class CardListView extends ListView
{
	public $options = [ 'class' => 'cardlistview'];
	public $itemsOptions = [ 'class' => 'row mb-3' ];
	public $cardOptions = [ 'class' => 'col-12 col-sm-6 col-lg-4 col-xxl-3 px-1 mb-1' ];
	public $labelSingular = 'item';
	public $labelPlural = 'items';
	public $itemOptions = [ 'class' => 'card shadow h-100' ];

	public function __construct($config = [])
	{
		parent::__construct($config);
		if (empty($this->summary)) {
			if ($this->dataProvider->getPagination() !== false) {
                $this->summary = Yii::t('churros', 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.');
			} else {
                $this->summary = Yii::t('churros', 'Total <b>{count, number}</b> {count, plural, one{item} other{items}}.');
			}
			$this->summary = strtr($this->summary, ['{item}' => '{'.$this->labelSingular.'}',
				'{items}' => '{'.$this->labelPlural.'}' ]);
		}
		Html::addCssClass($this->itemOptions, 'card');
	}

	public function run()
	{
		$view = $this->getView();
        ChurrosAsset::register($view);

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

    /**
     * Renders a single data model.
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key value associated with the data model
     * @param int $index the zero-based index of the data model in the model array returned by [[dataProvider]].
     * @return string the rendering result
     */
    public function renderItem($model, $key, $index)
    {
		$content = parent::renderItem($model, $key, $index);
        return Html::tag('div', $content, $this->cardOptions);
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

