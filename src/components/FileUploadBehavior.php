<?php
/**
 * @author Valentin Konusov <rlng-krsk@yandex.ru>
 * @author Alexey Samoylov <alexey.samoylov@gmail.com>
 * @link http://yiidreamteam.com/yii2/upload-behavior
 */
namespace santilin\churros\components;

use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\VarDumper;
use yii\web\UploadedFile;
use santilin\churros\exceptions\FileUploadException;

/**
 * Class FileUploadBehavior
 *
 * @property ActiveRecord $owner
 */
class FileUploadBehavior extends \yii\base\Behavior
{
    const EVENT_AFTER_FILE_SAVE = 'afterFileSave';

    /** @var string Name of attribute which holds the attachment. */
    public $attribute = 'upload';

    /** @var string Path template to use in storing files. */
    public $privateFilePath = '@app/runtime/uploads/';

    /** @var string Where to store images. */
    public $publicFilePath = '@uploads';

    /** @var string Name of file to store in the owner attribute. */
    public $fileAttrValue = '[[pk]].[[extension]]';


    /** @var \yii\web\UploadedFile */
    protected $file;

    protected $oldPath;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'beforeValidate',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * Before validate event.
     */
    public function beforeValidate()
    {
		$f = $_FILES;
		$r = $_REQUEST;
        if ($this->owner->{$this->attribute} instanceof UploadedFile) {
            $this->file = $this->owner->{$this->attribute};
            return;
        }

        $this->file = UploadedFile::getInstance($this->owner, $this->attribute);
        if (empty($this->file)) {
            $this->file = UploadedFile::getInstanceByName($this->attribute);
        }

        if ($this->file instanceof UploadedFile) {
            $this->owner->{$this->attribute} = $this->file;
        }

    }

    /**
     * Before save event.
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function beforeSave()
    {
		$this->oldPath = null;
		if ($this->file instanceof UploadedFile) {
			if (!$this->owner->isNewRecord) {
				/** @var ActiveRecord $oldModel */
				$oldModel = $this->owner->findOne($this->owner->primaryKey);
				$behavior = static::getInstance($oldModel, $this->attribute);
                if ( '' != $oldModel->{$this->attribute}) {
                    $behavior->cleanFiles();
                }
			}
			// Replaces the UploadedFile object into the path to the final file
			$this->owner->{$this->attribute} = implode('.',
					array_filter([$this->file->baseName, $this->file->extension]));
		} else {
			if( $this->owner->{$this->attribute} === '1' ) { /// Deleting the file
				$oldvalue = ArrayHelper::getValue($this->owner->oldAttributes, $this->attribute, null);
				$this->oldPath = $this->resolvePath($oldvalue);
				$this->owner->{$this->attribute} = null;
			} else if ( false === $this->owner->isNewRecord && empty($this->owner->{$this->attribute})) {
				$this->owner->{$this->attribute} = ArrayHelper::getValue($this->owner->oldAttributes, $this->attribute,
					null);
			}
		}
	}

    /**
     * Returns behavior instance for specified object and attribute
     *
     * @param Model $model
     * @param string $attribute
     * @return static
     */
    public static function getInstance(Model $model, $attribute)
    {
        foreach ($model->behaviors as $behavior) {
            if ($behavior instanceof self && $behavior->attribute == $attribute) {
                return $behavior;
            }
        }

        throw new InvalidCallException('Missing behavior for attribute ' . VarDumper::dumpAsString($attribute));
    }

    /**
     * Removes files associated with attribute
     */
    public function cleanFiles()
    {
        $path = $this->resolvePath($this->privateFilePath . $this->fileAttrValue);
        @unlink($path);
    }

    /**
     * Replaces all placeholders in path variable with corresponding values
     *
     * @param string $path
     * @return string
     */
    public function resolvePath($path)
    {
        if (!$this->owner->{$this->attribute}) {
            return '';
        }
        $path = Yii::getAlias($path);

        $pi = pathinfo($this->owner->{$this->attribute});
        $fileName = ArrayHelper::getValue($pi, 'filename');
        $extension = strtolower(ArrayHelper::getValue($pi, 'extension', ''));

        return str_replace('//','/', preg_replace_callback('|\[\[([\w\_/]+)\]\]|', function ($matches) use ($fileName, $extension) {
            $name = $matches[1];
            switch ($name) {
                case 'extension':
                    return $extension;
                case 'filename':
                    return $fileName;
                case 'basename':
                    return implode('.', array_filter([$fileName, $extension]));
                case 'app_root':
                    return Yii::getAlias('@app');
                case 'web_root':
                    return Yii::getAlias('@webroot');
                case 'base_url':
                    return Yii::getAlias('@web');
                case 'model':
                    $r = new \ReflectionClass($this->owner->className());
                    return lcfirst($r->getShortName());
                case 'attribute':
                    return lcfirst($this->attribute);
                case 'id':
                case 'pk':
                    $pk = implode('_', $this->owner->getPrimaryKey(true));
                    return lcfirst($pk);
                case 'id_path':
                    return static::makeIdPath($this->owner->getPrimaryKey());
            }
            if (preg_match('|^attribute_(\w+)$|', $name, $am)) {
                $attribute = $am[1];
                return $this->owner->{$attribute};
            }
            if (preg_match('|^md5_attribute_(\w+)$|', $name, $am)) {
                $attribute = $am[1];
                return md5($this->owner->{$attribute});
            }
            return '[[' . $name . ']]';
        }, $path));
    }

    /**
     * @param integer $id
     * @return string
     */
    protected static function makeIdPath($id)
    {
        $id = is_array($id) ? implode('', $id) : $id;
        $length = 10;
        $id = str_pad($id, $length, '0', STR_PAD_RIGHT);

        $result = [];
        for ($i = 0; $i < $length; $i++) {
            $result[] = substr($id, $i, 1);
        }

        return implode('/', $result);
    }

    /**
     * After insert event.
     */
    public function afterSave()
    {
        if ($this->file instanceof UploadedFile !== true) {
			if( $this->oldPath !== null ) { // Deleting the file
				$this->owner->{$this->attribute} = $this->oldPath;
				$path = $this->resolvePath($this->privateFilePath . $this->fileAttrValue);
				@unlink($path);
			}
            return;
        }

        $path = $this->getUploadedFilePath($this->attribute);

        FileHelper::createDirectory(pathinfo($path, PATHINFO_DIRNAME), 0775, true);

        if (!$this->file->saveAs($path)) {
			if( YII_ENV_TEST ) {
				if( !rename($this->file->tempName, Yii::getAlias($path)) ) {
					throw new FileUploadException($this->file->error, 'File saving error.');
				}
			} else {
				throw new FileUploadException($this->file->error, 'File saving error.');
			}
        }

        $this->owner->trigger(static::EVENT_AFTER_FILE_SAVE);
    }

    public function getUploadedFormFileUrl($attribute)
    {
		if ($this->owner->{$this->attribute} instanceof UploadedFile) {
			$file = $this->owner->{$this->attribute};
			if( $file->error == 0 ) {
				$raw_image = base64_encode(file_get_contents($file->tempName));
			} else {
				$raw_image = base64_encode(file_get_contents(Yii::getAlias('@churros/assets/i/wrong-uploaded-image.png')));
			}
			return 'data:image/png;base64,' . $raw_image;
		} else {
			return $this->getUploadedFilePath($attribute);
		}
    }

    /**
     * Returns file path for attribute.
     *
     * @param string $attribute
     * @return string
     */
    public function getUploadedFilePath($attribute)
    {
        if (!$this->owner->{$attribute}) {
            return '';
        }
        $behavior = static::getInstance($this->owner, $attribute);

        return $behavior->resolvePath($behavior->privateFilePath . $behavior->fileAttrValue);
    }

    /**
     * Before delete event.
     */
    public function beforeDelete()
    {
        $this->cleanFiles();
    }

    /**
     * Returns file url for the attribute.
     *
     * @param string $attribute
     * @return string|null
     */
    public function getUploadedFileUrl($attribute)
    {
        if (!$this->owner->{$attribute}) {
            return null;
        }

        $behavior = static::getInstance($this->owner, $attribute);

        return $behavior->resolvePath($behavior->publicFilePath . $behavior->fileAttrValue);
    }

    /**
     * Returns the value to be saved for the attribute.
     *
     * @param string $attribute
     * @return string|null
     * @author <santilin> software@noviolento.es
     */
    public function getUploadDestPath($attribute)
    {
        $behavior = static::getInstance($this->owner, $attribute);
        return $behavior->resolvePath($behavior->privateFilePath . $behavior->fileAttrValue);
    }

}
