<?php

namespace yii\q\controllers;

use Yii;
use yii\web\Controller;

use yii\q\components\Ajax;
use yii\q\models\Job;
use yii\q\models\Queue;

class QueueController extends Controller
{
    public $layout = 'main';

    public function actionIndex()
    {
        $queues = Queue::find()->with('workers')->all();

        return $this->render('index', [
            'queues' => $queues,
        ]);
    }

    public function actionJobs($queueName, $status)
    {
        $queue = Queue::findOne(['name' => $queueName]);

        if ($queue === null) {
            return $this->redirect(['queue/index']);
        }

        $jobs = $queue->getJobs()->andWhere(['status' => strtoupper($status)])->all();

        return $this->render('jobs', [
            'queue' => $queue,
            'jobs' => $jobs,
            'status' => strtoupper($status),
        ]);
    }

    public function actionAjaxPurge()
    {
        return $this->_purgeQueue();
    }

    public function actionAjaxRemove()
    {
        return $this->_purgeQueue(true);
    }

    protected function _purgeQueue($remove = false)
    {
        Yii::$app->response->format = 'json';

        $response = new Ajax;

        $name = Yii::$app->request->post('name', '');

        if (!is_string($name)) {
            return $response->addError('name', 'Invalid value');
        }

        $primaries = Job::getCollection()->aggregate([
            [
                '$match' => [
                    'queue' => $name,
                    'secondary' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => Job::collectionName(),
                    'localField' => 'primaryID',
                    'foreignField' => '_id',
                    'as' => 'primary',
                ],
            ],
            [
                '$match' => [
                    'primary.queue' => ['$ne' => $name],
                    'primary.status' => [Job::STATUS_IDLE, Job::STATUS_WORK, Job::STATUS_WAIT],
                ],
            ],
            [
                '$unwind' => '$primary',
            ],
            [
                '$group' => [
                    '_id' => '$primary._id',
                ],
            ],
        ]);

        if ($primaries !== []) {
            Yii::$app->session->setFlash('q', "Queue \"{$name}\" cannot be purged/removed while dependent primary jobs with statuses IDLE, WORK or WAIT exist in other queues");
            return $this->addError('name', 'denied');
        }

        if ($remove) {
            Queue::getCollection()->remove(['name' => $name]);
            sleep(3); // Wait 3 secs to be sure that no new jobs are queued
        }

        $primaries = Job::getCollection()->aggregate([
            [
                '$match' => [
                    'queue' => $name,
                    'secondary' => true,
                ],
            ],
            [
                '$lookup' => [
                    'from' => Job::collectionName(),
                    'localField' => 'primaryID',
                    'foreignField' => '_id',
                    'as' => 'primary',
                ],
            ],
            [
                '$match' => [
                    'primary.queue' => ['$ne' => $name],
                ],
            ],
            [
                '$unwind' => '$primary',
            ],
            [
                '$group' => [
                    '_id' => '$primary._id',
                ],
            ],
        ]);

        if ($primaries !== []) {
            Job::deleteAll(['_id' => array_column($primaries, '_id')]);
        }

        Job::getCollection()->remove(['queue' => $name]);

        return $response;
    }
}
