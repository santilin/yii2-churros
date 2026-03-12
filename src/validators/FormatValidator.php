<?php

namespace santilin\churros\validators;

use Yii;
use yii\validators\Validator;

class FormatValidator extends Validator
{
    /**
     * Format string; may contain <, >, A, a for case formatting rules.
     * Example: '<' means uppercase whole string.
     */
    public $format;

    public function init()
    {
        parent::init();
        if ($this->message === null) {
            $this->message = Yii::t('yii', '{attribute} has an invalid format.');
        }
    }

    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if (($message = $this->validateValue($value)) === null) {
            $model->$attribute = $this->formatValue($this->format, $value);
        } else {
            $this->addError($model, $attribute, $message);
        }
    }

    protected function validateValue($value)
    {
        if (!is_string($value)) {
            return Yii::t('yii', 'The value must be a string.');
        }

        // Simple case validation based on format
        for ($i = 0; $i < strlen($this->format); ++$i) {
            switch ($this->format[$i]) {
                case '<': // Must already be uppercase
                    if ($value !== mb_strtoupper($value)) {
                        return Yii::t('yii', 'The value must be entirely uppercase.');
                    }
                    break;
                case '>': // Must already be lowercase
                    if ($value !== mb_strtolower($value)) {
                        return Yii::t('yii', 'The value must be entirely lowercase.');
                    }
                    break;
                case 'A': // Each character uppercase
                    if ($value !== mb_strtoupper($value)) {
                        return Yii::t('yii', 'All letters must be uppercase.');
                    }
                    break;
                case 'a': // Each character lowercase
                    if ($value !== mb_strtolower($value)) {
                        return Yii::t('yii', 'All letters must be lowercase.');
                    }
                    break;
                default:
                    break;
            }
        }

        return null; // No error
    }

    protected function formatValue($format, $value)
    {
        for ($i = 0; $i < strlen($format); ++$i) {
            switch ($format[$i]) {
                case '<':
                    $value = mb_strtoupper($value);
                    break;
                case '>':
                    $value = mb_strtolower($value);
                    break;
                case 'A':
                    $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($chars as &$ch) {
                        $ch = mb_strtoupper($ch);
                    }
                    $value = implode('', $chars);
                    break;
                case 'a':
                    $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
                    foreach ($chars as &$ch) {
                        $ch = mb_strtolower($ch);
                    }
                    $value = implode('', $chars);
                    break;
                default:
                    break;
            }
        }
        return $value;
    }

    /**
     * Adds client-side validation instructions for Yii2 ActiveForm.
     */
	public function clientValidateAttribute($model, $attribute, $view)
	{
        \yii\validators\ValidationAsset::register($view);
		$format = $this->format;
		$message = json_encode($this->message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		return <<<JS
function isUpperCase(str) {
	return str === str.normalize('NFC').replace(/[\u00C0-\u02AF\u1E00-\u1EFF]/g, c =>
		String.fromCharCode(c.charCodeAt(0) - 32)
	).normalize('NFC');
}

function isLowerCase(str) {
	return str === str.normalize('NFC').replace(/[\u00E0-\u02FF\u1E00-\u1EFF]/g, c =>
		String.fromCharCode(c.charCodeAt(0) + 32)
	).normalize('NFC');
}

var \$input = \$form.find(attribute.input);value = \$input.val();
for (let i = 0; i < "$format".length; i++) {
	switch("$format"[i]) {
		case '<':
			if (value !== value.toLocaleUpperCase()) {
				messages.push($message);
			}
			break;
		case '>':
			if (val !== val.toLocaleLowerCase()) {
				messages.push($message);
			}
			break;
		case 'A':
			if (val !== val.toLocaleUpperCase()) {
				messages.push($message);
			}
			break;
		case 'a':
			if (val !== val.toLocaleLowerCase()) {
				messages.push($message);
			}
			break;
	}
}
\$input.val(value);
return value;
JS;
/*
// Helper for proper case checking (works with international chars)
function isUpperCase(str) {
	return str === str.normalize('NFC').replace(/[\u00C0-\u02AF\u1E00-\u1EFF]/g, c =>
		String.fromCharCode(c.charCodeAt(0) - 32)
	).normalize('NFC');
}

function isLowerCase(str) {
	return str === str.normalize('NFC').replace(/[\u00E0-\u02FF\u1E00-\u1EFF]/g, c =>
		String.fromCharCode(c.charCodeAt(0) + 32)
	).normalize('NFC');
}

for (let i = 0; i < "$format".length; i++) {
	switch("$format"[i]) {
		case '<':
			if (val !== val.toLocaleUpperCase()) {
				messages.push($message);
			}
			break;
		case '>':
			if (val !== val.toLocaleLowerCase()) {
				messages.push($message);
			}
			break;
		case 'A':
			if (val !== val.toLocaleUpperCase()) {
				messages.push($message);
			}
			break;
		case 'a':
			if (val !== val.toLocaleLowerCase()) {
				messages.push($message);
			}
			break;
	}
}
return value;
JS;
*/
	}

}
