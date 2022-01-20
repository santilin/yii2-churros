<?php
/**
 * @copyright Copyright (c) 2015 Yiister
 * @license https://github.com/yiister/yii2-gentelella/blob/master/LICENSE
 * @link http://gentelella.yiister.ru
 */

namespace santilin\churros\components;

use yii\helpers\Html;
use rmrevin\yii\fontawesome\component\Icon;
use yiister\gentelella\widgets\Menu;

class AdminMenu extends Menu
{
    /**
     * @inheritdoc
     */
    public $linkTemplate = '<a href="{url}"{attr}>{icon}<span>{label}</span>{badge}</a>';

    /**
     * @inheritdoc
     */
    public $submenuTemplate = "\n<ul class=\"nav child_menu\">\n{items}\n</ul>\n";
    /**
     * @inheritdoc
     */
    public function init()
    {
        Html::addCssClass($this->options, 'nav side-menu');
        parent::init();
    }

    /**
     * @inheritdoc
     */
    protected function renderItem($item)
    {
        $renderedItem = parent::renderItem($item);
        if (isset($item['badge'])) {
            $badgeOptions = ArrayHelper::getValue($item, 'badgeOptions', []);
            Html::addCssClass($badgeOptions, 'label pull-right');
        } else {
            $badgeOptions = null;
        }
        return strtr(
            $renderedItem,
            [
                '{icon}' => isset($item['icon'])
                    ? new Icon($item['icon'], ArrayHelper::getValue($item, 'iconOptions', []))
                    : '',
                '{badge}' => (
                    isset($item['badge'])
                        ? Html::tag('small', $item['badge'], $badgeOptions)
                        : ''
                    ) . (
                    isset($item['items']) && count($item['items']) > 0
                        ? (new Icon('chevron-down'))->tag('span')
                        : ''
                    ),
                '{attr}' =>
                    isset($item['linkOptions'])
                        ? ' ' . Html::renderTagAttributes($item['linkOptions'])
                        : ''
            ]
        );
    }
}
