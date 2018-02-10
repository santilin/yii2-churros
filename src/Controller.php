<?php

namespace santilin\Churros;

use Yii;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use SaveModelException;

/**
 * BaseController implements the CRUD actions for yii2gen models
 */
class Controller extends \yii\web\Controller
{
    public function behaviors()
    {
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
//                         'actions' => ['index', 'view', 'create', 'update', 'delete', 'pdf', 'save-as-new'],
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
     * Lists all BaseField models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = $this->createSearchModel();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single BaseField model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        return $this->render('view', [
            'model' => $model,
            'relationsProviders' => $this->getRelationsProviders($model)
        ]);
    }

    /**
     * Creates a new BaseField model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = $this->findModel();

        if ($model->loadAll(Yii::$app->request->post())) {
			$saved = false;
			$fileAttributes = $this->setFileInstances($model);
			if( count($fileAttributes) == 0 ) {
				$saved = $model->saveAll();
			} else {
				$transaction = $model->getDb()->beginTransaction();
				$saved = $model->saveAll();
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
				if (Yii::$app->request->post('_and_create') != '1') {
					return $this->redirect(['view', 'id' => $model->id]);
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
     * Updates an existing BaseField model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        if (Yii::$app->request->post('_asnew') == '1') {
            $model = $this->findModel();
        } else {
            $model = $this->findModel($id);
        }

		$oldModelAttributes = $model->getAttributes();
        if ($model->loadAll(Yii::$app->request->post())) {
			$saved = false;
			$fileAttributes = $this->setFileInstances($model);
			if( count($fileAttributes) == 0 ) {
				$saved = $model->saveAll();
			} else {
				$transaction = $model->getDb()->beginTransaction();
				$saved = $model->saveAll();
				if ($saved) {
					$saved = $this->saveFileInstances($model, $fileAttributes, $oldModelAttributes);
				}
				if ($saved) {
					$transaction->commit();
				} else {
					$transaction->rollBack();
				}
			}
			if ($saved) {
				return $this->redirect(['view', 'id' => $model->id]);
			}
		}
		return $this->render('update', [
			'model' => $model,
		]);
    }

    /**
     * Deletes an existing BaseField model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @todo delete uploaded files
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->deleteWithRelated();

        return $this->redirect(['index']);
    }
    
    /**
     * 
     * Export BaseField information into PDF format.
     * @param integer $id
     * @return mixed
     */
    public function actionPdf($id) {
        $model = $this->findModel($id);

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

    /**
    * Creates a new BaseField model by another data,
    * so user don't need to input all field from scratch.
    * If creation is successful, the browser will be redirected to the 'view' page.
    *
    * @param mixed $id
    * @return mixed
    * @todo manage uploaded files
    */
    public function actionSaveAsNew($id) {
        $model = $this->findModel();

        if (Yii::$app->request->post('_asnew') != '1') {
            $model = $this->findModel($id);
        }
    
        if ($model->loadAll(Yii::$app->request->post()) && $model->saveAll()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('saveAsNew', [
                'model' => $model,
            ]);
        }
    }
    
    protected function getRelationsProviders($model)
    {
		return [];
	}

	protected function setFileInstances($model)
	{
		$fileAttributes = $model->getFileAttributes();
		foreach( $fileAttributes as $attr ) {
			$instances = UploadedFile::getInstances($model, $attr);
			if (count($instances) == 0 ) {
				unset($fileAttributes[$attr]);
				$model->$attr = null;
			} else {
				$attr_value = [];
				foreach ($instances as $file) {
					$filename = $this->getFileInstanceKey($file, $model, $attr);
					$attr_value[$filename] = [ $file->name, $file->size ];
				}
				$model->$attr = $attr_value == [] ? null : serialize($attr_value);
			}
		}
		return $fileAttributes;
	}

	protected function saveFileInstances($model, $fileAttributes, $oldModelAttributes = null)
	{
		$saved = true;
		foreach( $fileAttributes as $attr ) {
			// For each image attribute, delete the old file or gallery
			if( $oldModelAttributes != null ) {
				if( isset($oldModelAttributes[$attr]) && $oldModelAttributes[$attr] != '' ) {
					$attr_files = unserialize($oldModelAttributes[$attr]);
					foreach( $attr_files as $filename => $titleandsize ) {
						$oldfilename = Yii::getAlias('@runtime/uploads/') . $filename;
						if (!@unlink($oldfilename) && file_exists($oldfilename) ) {
							$model->addError($attr, "No se ha podido borrar el archivo $oldfilename" . posix_strerror( $file->error ));
							return false;
						}
					}
				}
			}
			$instances = UploadedFile::getInstances($model, $attr);
			foreach($instances as $file) {
				$filename = $this->getFileInstanceKey($file, $model, $attr);
				$saved = false;
				try {
					$saved = $file->saveAs(Yii::getAlias('@runtime/uploads/') .$filename);
					if (!$saved) {
						$model->addError($attr, "No se ha podido guardar el archivo $filename: " . posix_strerror( $file->error ));
					}
				} catch( yii\base\ErrorException $e ) {
					$model->addError($attr, "No se ha podido guardar el archivo $filename: " . $e->getMessage());
				}
				if (!$saved) {
					break;
				}
			}			
		}
		return $saved;
	}
	
	private function getFileInstanceKey($uploadedfile, $model, $attr)
	{
		$filename = basename(str_replace('\\', '/', $model->className())) . "_$attr" . "_" . basename($uploadedfile->tempName) . "." . $uploadedfile->getExtension();
		return $filename;
	}
	
}
