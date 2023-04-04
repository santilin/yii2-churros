<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
namespace santilin\churros\widgets\grid;

use yii\helpers\ArrayHelper;
use yii\widgets\ListView;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use santilin\churros\ChurrosAsset;

/**
 * This ListView acts like a table
 */
class TableListView extends ListView
{
	public $options = [ 'class' => 'table-list-view'];
	public $itemOptions = [ 'class' => 'tlv-row' ];
	public $layout = "{summarypager}\n{header}\n{items}";
   /**
     * @var array the HTML attributes for the header of the list view.
     * The "tag" element specifies the tag name of the header element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
	*/
	public $headerOptions = [ 'class' => 'tlv-header' ];
	public $columns;

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
	}

	public function run()
	{
		if( !isset($this->columns) ) {
			throw new InvalidConfigException("TableListView: the property \$columns must be set");
		}
		$view = $this->getView();
        ChurrosAsset::register($view);

 		$this->layout = "{init}{$this->layout}{end}";
		return parent::run();
	}

	private function extractHeader()
	{
		$header_columns[] = '<div class="tlv-th tlv-date">Fecha</div>';
		$header_columns[] = '<div class="tlv-th tlv-string">Concepto</div>';
		$header_columns[] = '<div class="tlv-th tlv-string">Referencia</div>';
		$header_columns[] = '<div class="tlv-th tlv-number">Importe</div>';
		return implode('', $header_columns);
	}

    public function renderHeader()
    {
        $headerOptions = $this->headerOptions;
        $tag = ArrayHelper::remove($headerOptions, 'tag', 'div');
        return Html::tag($tag, $this->extractHeader(), $headerOptions);
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
			case '{init}':
				return '<div class="tlv-table">';
			case '{end}':
				return '</div>';
            case '{header}':
                return $this->renderHeader();
            default:
                return parent::renderSection($name);
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

