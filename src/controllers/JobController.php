<?php

namespace yii\q\controllers;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

use Yii;
use yii\web\Controller;

use yii\q\components\Ajax;
use yii\q\models\Job;

class JobController extends Controller
{
    public $layout = 'main';

    public function actionView($id)
    {
        $job = Job::findOne($id);

        if ($job === null) {
            return $this->redirect(['queue/index']);
        }

        return $this->render('view', [
            'job' => $job,
        ]);
    }

    public function actionAjaxPause()
    {
        Yii::$app->response->format = 'json';

        $response = new Ajax;

        $id = Yii::$app->request->post('id', '');

        if (!is_string($id) || !preg_match('/^[a-f\d]{24}$/', $id)) {
            return $response->addError('id', 'Invalid value');
        }

        Job::getCollection()->findAndModify(
            [
                '_id' => new ObjectId($id),
                'status' => Job::STATUS_IDLE,
            ],
            [
                '$set' => [
                    'status' => Job::STATUS_STOP,
                ],
                '$push' => [
                    'log' => [
                        'status' => Job::STATUS_STOP,
                        'datetime' => new UTCDateTime(time() * 1000),
                    ],
                ],
            ]
        );

        return $response;
    }

    public function actionAjaxRemove()
    {
        Yii::$app->response->format = 'json';

        $response = new Ajax;

        $id = Yii::$app->request->post('id', '');

        if (!is_string($id) || !preg_match('/^[a-f\d]{24}$/', $id)) {
            return $response->addError('id', 'Invalid value');
        }

        Job::getCollection()->remove(
            [
                '_id' => new ObjectId($id),
                'status' => [
                    Job::STATUS_DONE,
                    Job::STATUS_FAIL,
                    Job::STATUS_IDLE,
                ],
            ]
        );

        return $response;
    }

    public function actionAjaxResume()
    {
        Yii::$app->response->format = 'json';

        $response = new Ajax;

        $id = Yii::$app->request->post('id', '');

        if (!is_string($id) || !preg_match('/^[a-f\d]{24}$/', $id)) {
            return $response->addError('id', 'Invalid value');
        }

        Job::getCollection()->findAndModify(
            [
                '_id' => new ObjectId($id),
                'status' => Job::STATUS_STOP,
            ],
            [
                '$set' => [
                    'status' => Job::STATUS_IDLE,
                ],
                '$push' => [
                    'log' => [
                        'status' => Job::STATUS_IDLE,
                        'datetime' => new UTCDateTime(time() * 1000),
                    ],
                ],
            ]
        );

        return $response;
    }

    public function actionAjaxRetry()
    {
        Yii::$app->response->format = 'json';

        $response = new Ajax;

        $id = Yii::$app->request->post('id', '');

        if (!is_string($id) || !preg_match('/^[a-f\d]{24}$/', $id)) {
            return $response->addError('id', 'Invalid value');
        }

        $job = Job::getCollection()->findAndModify(
            [
                '_id' => new ObjectId($id),
                'status' => Job::STATUS_FAIL,
            ],
            [
                '$set' => [
                    'status' => Job::STATUS_IDLE,
                ],
                '$unset' => [
                    'error' => true,
                ],
                '$push' => [
                    'log' => [
                        'status' => Job::STATUS_IDLE,
                        'datetime' => new UTCDateTime(time() * 1000),
                    ],
                ],
            ]
        );

        if ($job && $job['secondary']) {
            // Update primary job
            Job::getCollection()->findAndModify(
                [
                    '_id' => $job['primaryID'],
                    'secondaryID.' . Job::STATUS_FAIL => $job['_id'],
                ],
                [
                    '$pull' => [
                        'secondaryID.' . Job::STATUS_FAIL => $job['_id'],
                    ],
                    '$push' => [
                        'secondaryID.' . Job::STATUS_WAIT => $job['_id'],
                    ],
                ]
            );
        }

        return $response;
    }
}
