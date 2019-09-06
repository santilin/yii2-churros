<?php

/*
 * This file is part of the 2amigos/yii2-grid-view-library project.
 * (c) 2amigOS! <http://2amigos.us/>
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace app\components;

use yii\base\Behavior;
use yii\bootstrap\ButtonGroup;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use dosamigos\grid\contracts\RegistersClientScriptInterface;


class TitleBehavior extends Behavior implements RegistersClientScriptInterface
{
    /**
     * @var array $buttons the buttons that would be rendered within the toolbar. The buttons configuration are exactly the
     *            same as http://www.yiiframework.com/doc-2.0/yii-bootstrap-buttongroup.html to display them with one difference,
     *            multiple button groups can be displayed by providing a separator element:
     *
     * ```
     * 'buttons' => [
     *      ['label' => 'A'],
     *      ['label' => 'B', 'visible' => false],
     *      '-', // divider
     *      ['label' => 'C'], // this will belong to a different group
     * ]
     * ```
     * @see http://www.yiiframework.com/doc-2.0/yii-bootstrap-buttongroup.html#$buttons-detail
     */
    public $buttons = [];
    /**
     * @var boolean whether to HTML-encode the button labels of the button groups.
     */
    public $encodeLabels = true;
    /**
     * @var array $buttonGroupOptions the options to pass to the button groups. Currently are global. For example:
     *
     * ```
     * 'buttonGroupOptions' => ['class' => 'btn-group-lg']
     * ```
     */
    public $buttonGroupOptions = [];
    /**
     * @var array the options for the toolbar tag.
     */
    public $toolbarOptions = [];
    /**
     * @var array the options for the container.
     */
    public $containerOptions = [];

    /**
     * @var array the options for the toolbar tag.
     */
    public $titleOptions = [];
    /**
     * @var array the options for the toolbar tag.
     */
    public $pagerOptions = [];
    /**
     * @var array the title for the grid.
     */
    public $title = "";
    
    /**
     * @var array contains the grouped buttons
     */
    protected $groups = [];

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->initOptions();
        $this->initButtonGroups();
    }

    /**
     * Renders the toolbar.
     *
     * @return string
     */
    public function renderTitle()
    {
        if (empty($this->groups) && empty($this->title)) {
            return '';
        }
        $content = [];
        foreach ($this->groups as $buttons) {
            $content[] = ButtonGroup::widget(
                ['buttons' => $buttons, 'encodeLabels' => $this->encodeLabels, 'options' => $this->buttonGroupOptions]
            );
        }
        $toolbar = Html::tag('div', implode("\n", $content), $this->toolbarOptions);
        $summary = Html::tag("div", $this->owner->renderSummary(), $this->titleOptions);
        $summary = str_replace("elementos", lcfirst($this->title), $summary);
        $pager = Html::tag('div', $this->owner->renderPager(), $this->pagerOptions);
        $container = Html::tag('div', $toolbar.$summary.$pager, $this->containerOptions);

        $container .= '<div class="clearfix" style="margin-top:5px"></div>';

        return $container;
    }

    /**
     * Initializes toolbar options
     */
    protected function initOptions()
    {
		Html::addCssClass($this->containerOptions, 'titbeh titbeh-primary' );
		Html::addCssClass($this->titleOptions, 'titbeh-heading ' );
		Html::addCssClass($this->pagerOptions, 'titbeh-heading pull-right' );
// 		Html::addCssClass($this->containerOptions, 'titbeh-primary');
        $this->toolbarOptions = ArrayHelper::merge($this->toolbarOptions, ['class' => 'btn-toolbar', 'role' => 'toolbar']);
		Html::addCssClass($this->toolbarOptions, 'titbeh-heading' );
    }

    /**
     * Parses the buttons to check for possible button group separations.
     */
    protected function initButtonGroups()
    {
        $group = [];
        foreach ($this->buttons as $button) {
            if (is_string($button) && $button == '-') {
                $this->groups[] = $group;
                $group = [];
                continue;
            }
            $group[] = $button;
        }
        if (!empty($group)) {
            $this->groups[] = $group;
        }
    }
    
    public function registerClientScript()
    {
		$css = 
"
.titbeh div {
    float: left;
    clear: none; 
}

.titbeh {
   overflow: hidden;
   background-color: #337ab7;
   border: 1px solid transparent;
   border-radius: 4px;
   box-shadow(0 1px 1px rgba(0, 0, 0, .05));
}
.titbeh-primary {
    color: #fff;
    background-color: #337ab7;
    border-color: #337ab7;
}
.titbeh-primary > .titbeh-heading {
    color: #fff;
    background-color: #337ab7;
    border-color: #337ab7;
}
.titbeh-heading {
  padding: 10px;
  border-bottom: 1px solid transparent;
  border-top-radius: 3px;

  > .dropdown .dropdown-toggle {
    color: inherit;
  }
}

.titbeh-heading .pagination {
	margin: 0px;
}
.titbeh-heading .pagination li.active a {
	background-color: #f0ad4e;
}
";
		$this->owner->getView()->registerCss($css, [], 'grid-title-behavior');
    }
    
}

