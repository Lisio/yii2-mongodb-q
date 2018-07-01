<?php

namespace yii\q\controllers;

use yii\web\Controller;

use yii\q\models\Queue;

class DefaultController extends Controller
{
    public $layout = 'main';

    public function actionIndex()
    {
        $queues = Queue::find()->all();

        return $this->render('index', [
            'queues' => $queues,
        ]);
    }
}
