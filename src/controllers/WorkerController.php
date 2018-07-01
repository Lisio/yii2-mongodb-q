<?php

namespace yii\q\controllers;

use yii\web\Controller;

use yii\q\models\Worker;

class WorkerController extends Controller
{
    public $layout = 'main';

    public function actionIndex()
    {
        $workers = Worker::find()->all();

        return $this->render('index', [
            'workers' => $workers,
        ]);
    }
}
