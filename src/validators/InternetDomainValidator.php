<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace santilin\churros\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\validators\{Validator,ValidationAsset,PunycodeAsset};

/**
 * UrlValidator validates that the attribute value is a valid http or https URL.
 *
 * Note that this validator only checks if the URL scheme and host part are correct.
 * It does not check the remaining parts of a URL.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InternetDomainValidator extends \yii\validators\Validator
{
    /**
     * @var string the regular expression used to validate the attribute value.
     * The pattern may contain a `{schemes}` token that will be replaced
     * by a regular expression which represents the [[validSchemes]].
     */
    public $pattern = <<<regex
/^(((?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))|(([-a-z0-9]{1,63}\.)+(ac\.nz|co\.nz|geek\.nz|gen\.nz|kiwi\.nz|maori\.nz|net\.nz|org\.nz|school\.nz|ae|ae\.org|com\.af|asia|asn\.au|auz\.info|auz\.net|com\.au|id\.au|net\.au|org\.au|auz\.biz|az|com\.az|int\.az|net\.az|org\.az|pp\.az|biz\.fj|com\.fj|info\.fj|name\.fj|net\.fj|org\.fj|pro\.fj|or\.id|biz\.id|co\.id|my\.id|web\.id|biz\.ki|com\.ki|info\.ki|ki|mobi\.ki|net\.ki|org\.ki|phone\.ki|biz\.pk|com\.pk|net\.pk|org\.pk|pk|web\.pk|cc|cn|com\.cn|net\.cn|org\.cn|co\.in|firm\.in|gen\.in|in|in\.net|ind\.in|net\.in|org\.in|co\.ir|ir|co\.jp|jp|jp\.net|ne\.jp|or\.jp|co\.kr|kr|ne\.kr|or\.kr|co\.th|in\.th|com\.bd|com\.hk|hk|idv\.hk|org\.hk|com\.jo|jo|com\.kz|kz|org\.kz|com\.lk|lk|org\.lk|com\.my|my|com\.nf|info\.nf|net\.nf|nf|web\.nf|com\.ph|ph|com\.ps|net\.ps|org\.ps|ps|com\.sa|com\.sb|net\.sb|org\.sb|com\.sg|edu\.sg|org\.sg|per\.sg|sg|com\.tw|tw|com\.vn|net\.vn|org\.vn|vn|cx|fm|io|la|mn|nu|qa|tk|tl|tm|to|tv|ws|academy|careers|education|training|bike|biz|cat|co|com|info|me|mobi|name|net|org|pro|tel|travel|xxx|blackfriday|clothing|diamonds|shoes|tattoo|voyage|build|builders|construction|contractors|equipment|glass|lighting|plumbing|repair|solutions|buzz|sexy|singles|support|cab|limo|camera|camp|gallery|graphics|guitars|hiphop|photo|photography|photos|pics|center|florist|institute|christmas|coffee|kitchen|menu|recipes|company|enterprises|holdings|management|ventures|computer|systems|technology|directory|guru|tips|wiki|domains|link|estate|international|land|onl|pw|today|ac\.im|co\.im|com\.im|im|ltd\.co\.im|net\.im|org\.im|plc\.co\.im|am|at|co\.at|or\.at|ba|be|bg|biz\.pl|com\.pl|info\.pl|net\.pl|org\.pl|pl|biz\.tr|com\.tr|info\.tr|tv\.tr|web\.tr|by|ch|co\.ee|ee|co\.gg|gg|co\.gl|com\.gl|co\.hu|hu|co\.il|org\.il|co\.je|je|co\.nl|nl|co\.no|no|co\.rs|in\.rs|rs|co\.uk|org\.uk|uk\.net|com\.de|de|com\.es|es|nom\.es|org\.es|com\.gr|gr|com\.hr|com\.mk|mk|com\.mt|net\.mt|org\.mt|com\.pt|pt|com\.ro|ro|com\.ru|net\.ru|ru|su|com\.ua|ua|cz|dk|eu|fi|fr|pm|re|tf|wf|yt|gb\.net|ie|is|it|li|lt|lu|lv|md|mp|se|se\.net|si|sk|ac|ag|co\.ag|com\.ag|net\.ag|nom\.ag|org\.ag|ai|com\.ai|com\.ar|as|biz\.pr|com\.pr|net\.pr|org\.pr|pr|biz\.tt|co\.tt|com\.tt|tt|bo|com\.bo|com\.br|net\.br|tv\.br|bs|com\.bs|bz|co\.bz|com\.bz|net\.bz|org\.bz|ca|cl|co\.cr|cr|co\.dm|dm|co\.gy|com\.gy|gy|co\.lc|com\.lc|lc|co\.ms|com\.ms|ms|org\.ms|co\.ni|com\.ni|co\.ve|com\.ve|co\.vi|com\.vi|com\.co|net\.co|nom\.co|com\.cu|cu|com\.do|do|com\.ec|ec|info\.ec|net\.ec|com\.gt|gt|com\.hn|hn|com\.ht|ht|net\.ht|org\.ht|com\.jm|com\.kn|kn|com\.mx|mx|com\.pa|com\.pe|pe|com\.py|com\.sv|com\.uy|uy|com\.vc|net\.vc|org\.vc|vc|gd|gs|north\.am|south\.am|us|us\.org|sx|tc|vg|cd|cg|cm|co\.cm|com\.cm|net\.cm|co\.ke|or\.ke|co\.mg|com\.mg|mg|net\.mg|org\.mg|co\.mw|com\.mw|coop\.mw|mw|co\.na|com\.na|na|org\.na|co\.ug|ug|co\.za|com\.ly|ly|com\.ng|ng|com\.sc|sc|mu|rw|sh|so|st|club|kiwi|uno|email|ruhr|gle)))$/i
regex;

    /**
     * @var bool whether validation process should take into account IDN (internationalized
     * domain names). Defaults to false meaning that validation of URLs containing IDN will always
     * fail. Note that in order to use IDN validation you have to install and enable `intl` PHP
     * extension, otherwise an exception would be thrown.
     */
    public $enableIDN = false;

    public $clean = true;

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        if ($this->enableIDN && !function_exists('idn_to_ascii')) {
            throw new InvalidConfigException('In order to use IDN validation intl extension must be installed and enabled.');
        }
        if ($this->message === null) {
            $this->message = Yii::t('churros', '{attribute} is not a valid domain name.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        $result = $this->validateValue($value);
        if (!empty($result)) {
            $this->addError($model, $attribute, $result[0], $result[1]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function validateValue($value)
    {
        // make sure the length is limited to avoid DOS attacks
        if (is_string($value) && strlen($value) < 2000) {

            if($this->clean === true) {
                $value = preg_replace("/(^(http(s)?:\/\/|www\.))?(www\.)?([a-z-\.0-9]+)/","$5", trim($value));
                if(preg_match("/^(((?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?))|([a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,6}))/", $value, $domain)){
                    $value = $domain[1];
                } else {
                    return [$this->message, []];
                }
            }

            if ($this->enableIDN) {
                $value = preg_replace_callback('/:\/\/([^\/]+)/', function ($matches) {
                    return '://' . $this->idnToAscii($matches[1]);
                }, $value);
            }

            if (preg_match($this->pattern, $value)) {
                return null;
            }
        }

        return [$this->message, []];
    }

    private function idnToAscii($idn)
    {
        if (PHP_VERSION_ID < 50600) {
            // TODO: drop old PHP versions support
            return idn_to_ascii($idn);
        }

        return idn_to_ascii($idn, IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
    }

    /**
     * {@inheritdoc}
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        ValidationAsset::register($view);
        if ($this->enableIDN) {
            PunycodeAsset::register($view);
        }
        $options = $this->getClientOptions($model, $attribute);
        return "yii.churros.internetDomain(value, messages, " . Json::encode($options) . ");";
    }


    /**
     * {@inheritdoc}
     */
    public function getClientOptions($model, $attribute)
    {
        $pattern = $this->pattern;

        $options = [
            'pattern' => new JsExpression($pattern),
            'message' => Json::encode($this->formatMessage($this->message, [
                'attribute' => $model->getAttributeLabel($attribute),
            ])),
            'enableIDN' => (bool) $this->enableIDN,
            'clean' => (bool) $this->clean,
        ];
        if ($this->skipOnEmpty) {
            $options['skipOnEmpty'] = 1;
        }

        return $options;
    }
}
