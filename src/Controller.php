<?php

namespace santilin\Churros;

use Yii;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\base\ErrorException;
use SaveModelException;
use DataException;

/**
 * BaseController implements the CRUD actions for yii2gen models
 */
class Controller extends \yii\web\Controller {

    public function behaviors() {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
//             'access' => [
//                 'class' => \yii\filters\AccessControl::className(),
//                 'rules' => [
//                     [
//                         'allow' => true,
//                         'actions' => ['index', 'view', 'create', 'update', 'delete', 'pdf', 'duplicate'],
//                         'roles' => ['@']
//                     ],
//                     [
//                         'allow' => false
//                     ]
//                 ]
//             ]
        ];
    }

    /**
     * Lists all models.
     * @return mixed
     */
    public function actionIndex() {
        $searchModel = $this->createSearchModel();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
                    'searchModel' => $searchModel,
                    'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id) {
        $model = $this->findModel($id);
        return $this->render('view', [
                    'model' => $model,
                    'relationsProviders' => $this->getRelationsProviders($model)
        ]);
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate() {
        $model = $this->findModel();

        if ($model->loadAll(Yii::$app->request->post(), $this->formRelations()) ) {
            $saved = false;
            $fileAttributes = $this->addFileInstances($model);
            if (count($fileAttributes) == 0) {
                $saved = $model->saveAll($this->formRelations());
            } else {
                $transaction = $model->getDb()->beginTransaction();
                $saved = $model->saveAll($this->formRelations());
                if ($saved) {
                    $saved = $this->saveFileInstances($model, $fileAttributes);
                }
                if ($saved) {
                    $transaction->commit();
                } else {
                    $transaction->rollBack();
                }
            }
            if ($saved) {
                Yii::$app->session->setFlash('success',
                        $model->t('app', "{La} {title} <strong>{record}</strong> se ha creado correctamente."));
                if (Yii::$app->request->post('_and_create') != '1') {
                    return $this->redirect(['view', 'id' => $model->getPrimaryKey()]);
                } else {
                    return $this->redirect(['create']);
                }
            }
        }
        return $this->render('create', [
                    'model' => $model,
        ]);
    }

    /**
     * Creates a new model by another data,
     * so user don't need to input all field from scratch.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @param mixed $id
     * @return mixed
     */
    public function actionDuplicate($id) {
        $model = $this->findModel($id);

        if (Yii::$app->request->post('_asnew') != 0) {
            $model = $this->findModel(Yii::$app->request->post('_asnew'));
        }

        if ($model->loadAll(Yii::$app->request->post(), $this->formRelations())) {
            $model->setIsNewRecord(true);
            foreach ($model->primaryKey() as $primary_key) {
                $model->$primary_key = null;
            }
            $saved = false;
            $fileAttributes = $this->addFileInstances($model);
            if (count($fileAttributes) == 0) {
                $saved = $model->saveAll($this->formRelations());
            } else {
                $transaction = $model->getDb()->beginTransaction();
                $saved = $model->saveAll($this->formRelations());
                if ($saved) {
                    $saved = $this->saveFileInstances($model, $fileAttributes);
                }
                if ($saved) {
                    $transaction->commit();
                } else {
                    $transaction->rollBack();
                }
            }
            if ($saved) {
                Yii::$app->session->setFlash('success',
                        $model->t('app', "{La} {title} <strong>{record}<strong> se ha duplicado correctamente."));
                return $this->redirect(['view', 'id' => $model->getPrimaryKey()]);
            }
        }
        return $this->render('saveAsNew', [
                    'model' => $model,
        ]);
    }

    /**
     * Updates an existing model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id) {
        $model = $this->findModel($id);

        if ($model->loadAll(Yii::$app->request->post(), $this->formRelations()) ) {
            $saved = false;
            $fileAttributes = $this->addFileInstances($model);
            if (count($fileAttributes) == 0) {
                $saved = $model->saveAll($this->formRelations());
            } else {
                $transaction = $model->getDb()->beginTransaction();
                $saved = $model->saveAll($this->formRelations());
                if ($saved) {
                    $saved = $this->saveFileInstances($model, $fileAttributes);
                }
                if ($saved) {
                    $transaction->commit();
                } else {
                    $transaction->rollBack();
                }
            }
            if ($saved) {
                Yii::$app->session->setFlash('success',
                        $model->t('app', "{La} {title} <strong>{record}</strong> se ha modificado correctamente."));
                return $this->redirect(['view', 'id' => $model->getPrimaryKey()]);
            }
        }
        return $this->render('update', [
                    'model' => $model,
        ]);
    }

    /**
     * Deletes an existing model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @todo delete uploaded files
     */
    public function actionDelete($id) {
        $model = $this->findModel($id);
        $model->deleteWithRelated();
        Yii::$app->session->setFlash('success',
                $model->t('app', "{La} {title} <strong>{record}</strong> se ha  borrado correctamente."));
        return $this->redirect(['index']);
    }

    /**
     *
     * Export model information into PDF format.
     * @param integer $id
     * @return mixed
     */
    public function actionPdf($id) {
        $model = $this->findModel($id);
        $this->layout = 'pdf_model';

        $content = $this->renderAjax('_pdf', [
            'model' => $model,
        ]);

        $pdf = new \kartik\mpdf\Pdf([
            'mode' => \kartik\mpdf\Pdf::MODE_CORE,
            'format' => \kartik\mpdf\Pdf::FORMAT_A4,
            'orientation' => \kartik\mpdf\Pdf::ORIENT_PORTRAIT,
            'destination' => \kartik\mpdf\Pdf::DEST_BROWSER,
            'content' => $content,
            'cssFile' => '@vendor/kartik-v/yii2-mpdf/assets/kv-mpdf-bootstrap.min.css',
            'cssInline' => '.kv-heading-1{font-size:18px}',
            'options' => ['title' => \Yii::$app->name],
            'methods' => [
                'SetHeader' => [\Yii::$app->name],
                'SetFooter' => ['{PAGENO}'],
            ]
        ]);

        return $pdf->render();
    }

    protected function getRelationsProviders($model) {
        return [];
    }

    // Método para eliminar una imagen de una galería
    public function actionRemoveImage($id, $field, $filename) {
        $model = $this->findModel($id);
        if ($model->$field == '') {
            throw new DataException($model->className() . "->$field is empty when removing an image");
        }
        $images = unserialize($model->$field);
        if (!is_array($images)) {
            throw new DataException($model->className() . "->$field is not an array");
        }
        if (isset($images[$filename])) {
            if ($this->unlinkImage($model, $filename)) {
                unset($images[$filename]);
                if( $images==[] ) {
                    $model->$field = null;
                } else {
                    $model->$field = serialize($images);
                }
                if (!$model->save()) {
                    throw new SaveModelException($model);
                }
            } else {
                throw new DataException("Unable to delete " . $model->className() . "->$field[$filename]");
            }
        }
        return json_encode("Ok");
    }

    protected function addFileInstances($model) {
        $fileAttributes = $model->getFileAttributes();
        foreach ($fileAttributes as $key => $attr) {
            $instances = UploadedFile::getInstances($model, $attr);
            if (count($instances) == 0) {
                unset($fileAttributes[$key]);
                // Recupera el valor sobreescrito por el LoadAll del controller
                $model->$attr = $model->getOldAttribute($attr);
            } else {
				try {
                $attr_value = ($model->getOldAttribute($attr) != '' ? unserialize($model->getOldAttribute($attr)) : []);
                } catch( ErrorException $e) {
					throw new ErrorException($e->getMessage() . "<br/>\n" . $model->getOldAttribute($attr));
                }
                foreach ($instances as $file) {
                    if ($file->error == 0) {
                        $filename = $this->getFileInstanceKey($file, $model, $attr);
                        $attr_value[$filename] = [$file->name, $file->size];
                    } else {
                        throw new HttpException(500, $this->fileUploadErrorMessage($model, $attr, $file));
                    }
                }
                if ($attr_value == []) {
					$model->$attr = null;
				} else {
					$model->$attr = serialize($attr_value);
				}
            }
			if ($model->$attr == []) {
				$model->$attr = null;
			}
        }
        return $fileAttributes;
    }

    protected function saveFileInstances($model, $fileAttributes) {
        $saved = true;
        foreach ($fileAttributes as $attr) {
            $instances = UploadedFile::getInstances($model, $attr);
            foreach ($instances as $file) {
                $filename = $this->getFileInstanceKey($file, $model, $attr);
                $saved = false;
                try {
                    $saved = $file->saveAs(Yii::getAlias('@runtime/uploads/') . $filename);
                    if (!$saved) {
                        $model->addError($attr, "No se ha podido guardar el archivo $filename: " . posix_strerror($file->error));
                    }
                } catch (yii\base\ErrorException $e) {
                    $model->addError($attr, "No se ha podido guardar el archivo $filename: " . $e->getMessage());
                }
                if (!$saved) {
                    break;
                }
            }
        }
        return $saved;
    }

    private function getFileInstanceKey($uploadedfile, $model, $attr) {
        $filename = basename(str_replace('\\', '/', $model->className())) . "_$attr" . "_" . basename($uploadedfile->tempName) . "." . $uploadedfile->getExtension();
        return $filename;
    }

    private function unlinkImage($model, $filename) {
        $oldfilename = Yii::getAlias('@runtime/uploads/') . $filename;
        if (file_exists($oldfilename) && !@unlink($oldfilename)) {
            $model->addError($attr, "No se ha podido borrar el archivo $oldfilename" . posix_strerror($file->error));
            return false;
        } else {
            return true;
        }
    }

    private function fileUploadErrorMessage($model, $attr, $file) {
        $message = "Error uploading " . $model->className() . ".$attr: ";
        switch ($file->error) {
            case UPLOAD_ERR_OK:
                return "";
            case UPLOAD_ERR_INI_SIZE:
                $message .= "The uploaded file exceeds the upload_max_filesize directive in php.ini.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message .= "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message .= "The uploaded file was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message .= "No file was uploaded.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message .= "Missing a temporary folder.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message .= "Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                break;
        }
        return $message;
    }

    protected function formRelations()
    {
		return [];
    }

}
