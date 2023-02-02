<?php
namespace santilin\churros\widgets\wizard;

use yii;
use buttflattery\formwizard\FormWizard;
use yii\helpers\{ArrayHelper,Html,Json};
use yii\bootstrap\ActiveForm as BS3ActiveForm;
use yii\bootstrap4\ActiveForm as BS4ActiveForm;

class WizardForm extends FormWizard
{
	private $_hiddenFields = [];
	private $_enctype_multipart = false;
	public $formInfoOptions = [
		'class' => 'border-bottom border-gray pb-2'
	];

	public function run()
    {
        $this->labelNext = Yii::t('churros', $this->labelNext);
        $this->labelPrev = Yii::t('churros', $this->labelPrev);
        $this->labelFinish = Yii::t('churros', $this->labelFinish);
        parent::run();
	}

	/**
	 * @inheritdoc
	 */
    public function createForm()
    {
        //get the container id
        $wizardContainerId = $this->wizardContainerId;

        //load respective bootstrap assets
        if ($this->isBs3()) {
            $activeForm = BS3ActiveForm::class;
        } else {
            $activeForm = BS4ActiveForm::class;
        }

        //start ActiveForm tag
		$form = $activeForm::begin();
		$form->options = $this->formOptions;

        //start container tag
        echo Html::beginTag('div', ['id' => $wizardContainerId]);

        //draw form steps
        echo $this->createFormWizard();

        //sct output all the collected hidden_fields
        if( $this->_enctype_multipart ) {
			$form->options['enctype'] = 'multipart/form-data';
		}
        foreach( $this->_hiddenFields as $hf ) {
			echo $hf;
		}

        //end container div tag
        echo Html::endTag('div');

        //end form tag
        $form->end();
    }

	/**
	 * @inheritdoc
	 */
    public function createBody($index, $formInfoText, array $step)
    {
        $html = '';

        //get the step type
        $stepType = ArrayHelper::getValue($step, 'type', self::STEP_TYPE_DEFAULT);

        $isSkipable = ArrayHelper::getValue($step, 'isSkipable', false);

        //check if tabular step
        $isTabularStep = $this->isTabularStep($stepType);

        //tabular rows limit
        $limitRows = ArrayHelper::getValue($step, 'limitRows', self::ROWS_UNLIMITED);

        //check if tabular step
        $isTabularStep && $this->_checkTabularConstraints($step['model']);

        //step data
        $dataStep = [
            'number' => $index,
            'type' => $stepType,
            'skipable' => $isSkipable,
        ];

        //start step wrapper div
        $html .= Html::beginTag(
            'div',
            ['id' => 'step-' . $index, 'data' => ['step' => Json::encode($dataStep)]]
        );

        $formInfoOptions = $this->formInfoOptions;
		Html::addCssClass($formInfoTextOptions, "alert alert-info");
        $html .= Html::tag('div', $formInfoText, $formInfoTextOptions);

        //Add Row Buton to add fields dynamically
        if ($isTabularStep) {

            $html .= Html::button(
                $this->iconAdd . '&nbsp;' . $this->labelAddRow,
                [
                    'class' => $this->classAdd . (($this->_bsVersion == self::BS_3) ? ' pull-right add_row' : ' float-right add_row'),
                ]
            );
        }

        //check if not preview step and add fields container
        if (!$this->isPreviewStep($step)) {

            //start field container tag <div class="fields_container">
            $html .= Html::beginTag('div', ["class" => "fields_container", 'data' => ['rows-limit' => $limitRows]]);
            //create step fields
			$html .= $this->createStepFields($index, $step, $isTabularStep, $limitRows);
        }

        //close the field container tag </div>
        $html .= Html::endTag('div');

        //close the step div </div>
        $html .= Html::endTag('div');
        return $html;
    }

	/**
	 * @inheritdoc
	 */
    public function createStepFields($stepIndex, array $stepConfig, $isTabularStep, $limitRows)
    {

		list($form, $hidden_fields, $form_fields) = $stepConfig['form_parts'];
		if( !empty($form->options['enctype'])) {
			$this->_enctype_multipart = true;
		}
		$this->_hiddenFields = array_merge($this->_hiddenFields, $hidden_fields);
		$html = '';
		$html .= $form->layoutForm($form_fields);

//         //parse response
//         $this->_dependentInputScript .= $response->dependentInputJs;
//         $this->_persistenceEvents .= $response->persistenceJs;
//         $this->_tabularEventJs .= $response->tabularEventsJs;
//         $this->_allFields[$stepIndex] = $response->jsFields;

        //return the html
        return $html;
    }



}

