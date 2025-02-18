<?php

namespace santilin\churros\widgets;

use Yii;

/**
 * Alert widget renders a message from session flash. All flash messages are displayed
 * in the sequence they were assigned using setFlash. You can set message as following:
 *
 * ```php
 * Yii::$app->session->setFlash('error', 'This is the message');
 * Yii::$app->session->setFlash('success', 'This is the message');
 * Yii::$app->session->setFlash('info', 'This is the message');
 * ```
 *
 * Multiple messages could be set as follows:
 *
 * ```php
 * Yii::$app->session->setFlash('error', ['Error 1', 'Error 2']);
 * ```
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @author Alexander Makarov <sam@rmcreative.ru>
 */

class NotifAlert extends \yii\base\Widget
{
    public $htmlOptions= [];
    /**
     * @var array the options for rendering the close button tag.
     * Array will be passed to [[yii\bootstrap\Alert::closeButton]].
     */
    public $closeButton = [];

    /**
     * @var array the alert types configuration for the flash messages.
     * This array is setup as $key => $value, where:
     * - key: the name of the session flash variable
     * - value: the bootstrap alert type (i.e. danger, success, info, warning)
     */
    public $alertTypes = [
        'error'   => 'alert-danger',
        'danger'  => 'alert-danger',
        'success' => 'alert-success',
        'info'    => 'alert-info',
        'warning' => 'alert-warning'
    ];

    public array $notifications = [];

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $appendClass = isset($this->options['options']['class']) ? ' ' . $this->options['options']['class'] : '';

        foreach ($this->notifications as $type => $notification) {
            if (!isset($this->alertTypes[$type])) {
                continue;
            }

            foreach ((array) $notification as $i => $message) {
                if (!empty($message)) {
                    echo \yii\bootstrap5\Alert::widget([
                        'body' => $message,
                        'closeButton' => $this->closeButton,
                        'options' => array_merge($this->htmlOptions, [
                            'id' => $this->getId() . '-' . $type . '-' . $i,
                            'class' => $this->alertTypes[$type] . $appendClass,
                        ]),
                    ]);
                }
            }
        }
    }
}

