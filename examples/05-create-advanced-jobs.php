<?php

namespace yii\q\examples;

use Yii;

use DownloadWorker;
use ReportWorker;

class Example
{
    public function run()
    {
        Yii::$app->queue->queueCreate('download');
        Yii::$app->queue->queueCreate('report');

        $primaryJobOptions = [
            'keepResult' => true, // Keep job result for 24 hours
            'keepResultDuration' => 86400,
            'primary' => true, // This job is primary and will be processed after all secondary jobs are completed
            'removeSecondaries' => true, // Remove secondary jobs after all
        ];

        // Create primary job
        $primaryJob = Yii::$app->queue->jobCreate('report', ReportWorker::className, null, $primaryJobOptions);

        $secondaryJobOptions = [
            'keepResult' => true,
            'keepResultDuration' => 86400,
            'secondary' => true,
            'primaryID' => $primaryJob['_id'],
        ];

        $data = 'https://bing.com/';
        Yii::$app->queue->jobCreate('download', DownloadWorker::className(), $data, $secondaryJobOptions);

        $data = 'https://google.com/';
        Yii::$app->queue->jobCreate('download', DownloadWorker::className(), $data, array_merge($secondaryJobOptions, [
            'priority' => 100500, // Process this job ASAP
        ]));

        $data = 'https://yahoo.com/';
        Yii::$app->queue->jobCreate('download', DownloadWorker::className(), $data, array_merge($secondaryJobOptions, [
            'schedule' => date('c', strtotime('tomorrow midnight')), // Check it at midnight
        ]));

        // Resume primary job after all secondary jobs are queued
        Yii::$app->queue->jobResume($primaryJob['_id']);
    }
}
