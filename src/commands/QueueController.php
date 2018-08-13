<?php

namespace yii\q\commands;

use MongoDB\BSON\UTCDateTime;

use yii\console\Controller;

use yii\q\components\Overseer;
use yii\q\models\Job;
use yii\q\models\Queue;
use yii\q\models\Worker;

/**
 * Console controller to manage workers and remove stale jobs.
 */
class QueueController extends Controller
{
    /**
     * Spawns new workers in separate processes.
     *
     * Skip $queues param to bind worker to all queues.
     *
     * @param integer $teamSize number of workers to spawn
     * @param string $queues comma-separated list of queue names
     */
    public function actionSpawn($teamSize = 1, $queues = null)
    {
        pcntl_async_signals(true);

        for ($i = 1; $i <= $teamSize; ++$i) {
            $pid = pcntl_fork();
            if ($pid == 0) {
                $overseer = new Overseer;

                pcntl_signal(SIGTERM, [$overseer, 'stop']);
                pcntl_signal(SIGHUP, [$overseer, 'stop']);
                pcntl_signal(SIGINT, [$overseer, 'stop']);
                pcntl_signal(SIGUSR1, [$overseer, 'stop']);

                if ($queues !== null) {
                    $overseer->setQueues(explode(',', $queues));
                }

                $overseer->work();

                exit;
            } elseif ($pid == -1) {
                echo "Can't spawn worker #{$i}" . PHP_EOL;
            }
        }
    }

    /**
     * Stops all workers nicely.
     */
    public function actionStopAllWorkers()
    {
        Worker::updateAll(['stop' => true]);
    }

    /**
     * Stops workers nicely.
     * List of queue names must be exactly the same that was used for spawning workers.
     *
     * @param string $queues comma-separated list of queue names
     */
    public function actionStopWorkers($queues = null)
    {
        if ($queues === null) {
            Worker::updateAll(['stop' => true], ['queues' => ['$eq' => []]]);
        } else {
            Worker::updateAll(['stop' => true], ['queues' => ['$eq' => explode(',', $queues)]]);
        }
    }

    /**
     * Removes dead workers and puts their jobs in FAIL state.
     *
     * This method should be called by cron at every host where workers are spawned.
     */
    public function actionRemoveDeadWorkers()
    {
        $pids = explode("\n", trim(`ps ahxwwo pid`));

        $workers = Worker::findAll([
            'hostname' => gethostname(),
            'heartbeat' => [
                '$lte' => new UTCDateTime((time() - 60) * 1000),
            ],
        ]);

        foreach ($workers as $worker) {
            if (in_array($worker->pid, $pids)) {
                continue;
            }

            if ($worker->jobID) {
                // Update job status and save exception data
                $job = Job::getCollection()->findAndModify(
                    [
                        '_id' => $worker->jobID,
                        'status' => Job::STATUS_WORK,
                        'workerID' => $worker->_id,
                    ],
                    [
                        '$set' => [
                            'status' => Job::STATUS_FAIL,
                            'error' => [
                                'message' => "Worker's process not found",
                                'trace' => "Hostname: {$worker->hostname}\nPID: {$worker->pid}",
                            ],
                        ],
                        '$unset' => [
                            'workerID' => true,
                        ],
                        '$push' => [
                            'log' => [
                                'status' => Job::STATUS_FAIL,
                                'datetime' => new UTCDateTime(time() * 1000),
                            ],
                        ],
                    ]
                );

                if ($job && isset($job['secondary'])) {
                    // Update primary job
                    Job::getCollection()->findAndModify(
                        [
                            '_id' => $job['primaryID'],
                            'secondaryID.' . Job::STATUS_WAIT => $job['_id'],
                        ],
                        [
                            '$pull' => [
                                'secondaryID.' . Job::STATUS_WAIT => $job['_id'],
                            ],
                            '$push' => [
                                'secondaryID.' . Job::STATUS_FAIL => $job['_id'],
                            ],
                        ]
                    );
                }
            }

            $worker->delete();
        }
    }

    /**
     * Removes completed jobs after keepResultDuration seconds.
     */
    public function actionRemoveStaleJobs()
    {
        $jobs = Job::findAll([
            'status' => Job::STATUS_DONE,
            'keepResult' => true,
            '$expr' => [
                '$lte' => [
                    [
                        '$arrayElemAt' => ['$log.datetime', -1],
                    ],
                    [
                        '$subtract' => [
                            new UTCDateTime(time() * 1000),
                            [
                                '$multiply' => [
                                    '$keepResultDuration',
                                    1000,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        foreach ($jobs as $job) {
            if ($job->primary) {
                Job::deleteAll(['primaryID' => $job->_id]);
            } elseif ($job->secondary && $job->primary !== null) {
                continue;
            }

            $job->delete();
        }
    }

    /**
     * Creates indexes for queue server collections.
     */
    public function actionCreateIndexes()
    {
        Queue::getCollection()->createIndex(['name' => 1]);

        Job::getCollection()->createIndex(['queue' => 1]);
        Job::getCollection()->createIndex(['status' => 1, 'schedule' => 1, 'primary' => 1, 'priority' => -1, '_id' => 1]);
        Job::getCollection()->createIndex(['primaryID' => 1]);

        Worker::getCollection()->createIndex(['hostname' => 1, 'heartbeat' => 1]);
    }

    /**
     * Removes indexes of queue server collections.
     */
    public function actionDropIndexes()
    {
        Queue::getCollection()->dropIndex(['name' => 1]);

        Job::getCollection()->dropIndex(['queue' => 1]);
        Job::getCollection()->dropIndex(['status' => 1, 'schedule' => 1, 'primary' => 1, 'priority' => -1, '_id' => 1]);
        Job::getCollection()->dropIndex(['primaryID' => 1]);

        Worker::getCollection()->dropIndex(['hostname' => 1, 'heartbeat' => 1]);
    }
}
