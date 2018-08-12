<?php

namespace yii\q\components;

use MongoDB\BSON\Decimal128;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Operation\Watch;

use yii\base\Component;

use yii\q\models\Job;
use yii\q\models\Queue;
use yii\q\models\Worker;

/**
 * Overseer class for current worker.
 *
 * @property array $_queues Queues to bind to
 * @property \yii\q\models\Worker $_worker worker
 * @property \yii\q\models\Job $_job current job
 */
class Overseer extends Component
{
    /**
     * @var array $_queues Queues to bind to
     */
    protected $_queues = [];

    /**
     * @var \yii\q\models\Worker $_worker worker
     */
    protected $_worker;

    /**
     * @var \yii\q\models\Job $_job current job
     */
    protected $_job;

    /**
     * Sets queues to bind to.
     *
     * @param array $queues List of queue names to bind to.
     */
    public function setQueues($queues)
    {
        $this->_queues = $queues;
    }

    /**
     * Main loop.
     */
    public function work()
    {
        $this->_worker = new Worker;
        $this->_worker->hostname = gethostname();
        $this->_worker->pid = getmypid();
        $this->_worker->queues = $this->_queues;
        $this->_worker->insert();

        $changeStream = $this->_getChangeStream();
        $init = true;
        $skip = false;

        for ($changeStream->rewind(); true; $changeStream->next()) {
            if ($changeStream->valid()) {
                $changeStream->current();
                if ($skip) {
                    $init = true;
                    continue;
                }
            } elseif (!$init) {
                $this->_heartbeat();
                continue;
            } else {
                $init = false;
                $skip = false;
            }

            while ($this->_job = $this->_getJob()) {
                echo $this->_job->_id . PHP_EOL;

                try {
                    $worker = new $this->_job->worker;
                    $worker->overseer = $this;
                    $worker->job = $this->_job;
                    $worker->setUp();
                    $result = $worker->work();
                    $worker->tearDown();

                    $this->_ack($result);
                } catch (\Exception $e) {
                    $this->_nack($e);
                } catch (\Error $e) {
                    $this->_nack($e);
                }

                $skip = true;
            }
        }
    }

    /**
     * Saves job progress.
     *
     * @param float $progress job progress value between 0 and 100
     */
    public function setProgress($progress)
    {
        if ($progress !== null) {
            Job::getCollection()->findAndModify(
                [
                    '_id' => $this->_job->_id,
                ],
                [
                    '$set' => [
                        'progress' => new Decimal128(round($progress, 2)),
                    ],
                ]
            );
        }

        $this->_heartbeat();
    }

    /**
     * Subscribes to jobs changes.
     *
     * @return \MongoDB\ChangeStream MongoDB Change Stream
     */
    protected function _getChangeStream()
    {
        if ($this->_queues === []) {
            $match = [];
        } else {
            $match = ['fullDocument.queue' => ['$in' => $this->_queues]];
        }

        $pipeline = [
            [
                '$match' => array_merge($match, [
                    'fullDocument.status' => Job::STATUS_IDLE,
                    '$and' => [
                        [
                            '$or' => [
                                [
                                    'fullDocument.schedule' => ['$exists' => false],
                                ],
                                [
                                    'fullDocument.schedule' => ['$lte' => new UTCDateTime(time() * 1000)],
                                ],
                            ],
                        ],
                        [
                            '$or' => [
                                [
                                    'fullDocument.primary' => ['$exists' => false],
                                ],
                                [
                                    'fullDocument.secondaryID.' . Job::STATUS_WAIT => ['$size' => 0],
                                    'fullDocument.secondaryID.' . Job::STATUS_FAIL => ['$size' => 0],
                                ],
                            ],
                        ],
                    ],
                ]),
            ],
        ];

        $options = [
            'fullDocument' => \MongoDB\Operation\Watch::FULL_DOCUMENT_UPDATE_LOOKUP,
        ];

        $manager = Job::getDb()->manager;

        $watch = new Watch($manager, Job::getDb()->getDatabase()->name, Job::collectionName(), $pipeline, $options);

        $server = $manager->selectServer($manager->getReadPreference());

        return $watch->execute($server);
    }

    /**
     * Reserves job for processing.
     *
     * @return \yii\q\models\Job reserved job for processing.
     */
    protected function _getJob()
    {
        $query = [
            'status' => Job::STATUS_IDLE,
            '$and' => [
                [
                    '$or' => [
                        [
                            'schedule' => ['$exists' => false],
                        ],
                        [
                            'schedule' => ['$lte' => new UTCDateTime(time() * 1000)],
                        ],
                    ],
                ],
                [
                    '$or' => [
                        [
                            'primary' => ['$exists' => false],
                        ],
                        [
                            'secondaryID.' . Job::STATUS_WAIT => ['$size' => 0],
                            'secondaryID.' . Job::STATUS_FAIL => ['$size' => 0],
                        ],
                    ],
                ],
            ],
        ];

        if ($this->_queues !== []) {
            $query['queue'] = ['$in' => $this->_queues];
        }

        $job = Job::getCollection()->findAndModify(
            $query,
            [
                '$set' => [
                    'status' => Job::STATUS_WORK,
                    'workerID' => $this->_worker->_id,
                ],
                '$push' => [
                    'log' => [
                        'status' => Job::STATUS_WORK,
                        'datetime' => new UTCDateTime(time() * 1000),
                        'workerID' => $this->_worker->_id,
                    ],
                ],
            ],
            [
                'new' => true,
                'sort' => [
                    'priority' => SORT_DESC,
                    '_id' => SORT_ASC,
                ],
            ]
        );

        if ($job === null) {
            $this->_heartbeat();
            return null;
        }

        $this->_worker->jobID = $job['_id'];
        $this->_worker->heartbeat = new UTCDateTime(time() * 1000);
        $this->_worker->update();

        $j = new Job;

        Job::populateRecord($j, $job);

        return $j;
    }

    /**
     * Acknowledges job.
     *
     * @param mixed $result result of job processing for jobs with keepResult set to true.
     */
    protected function _ack($result)
    {
        if ($this->_job->keepResult) {
            // Update job status and save result data
            Job::getCollection()->findAndModify(
                [
                    '_id' => $this->_job->_id,
                ],
                [
                    '$set' => [
                        'status' => Job::STATUS_DONE,
                        'result' => $result,
                    ],
                    '$unset' => [
                        'workerID' => true,
                    ],
                    '$push' => [
                        'log' => [
                            'status' => Job::STATUS_DONE,
                            'datetime' => new UTCDateTime(time() * 1000),
                        ],
                    ],
                ]
            );
        } else {
            // Remove finished job
            Job::deleteAll(['_id' => $this->_job->_id]);
        }

        // Remove all secondaries when needed
        if ($this->_job->primary && $this->_job->removeSecondaries) {
            Job::deleteAll(['primaryID' => $this->_job->_id]);
        }

        if ($this->_job->secondary) {
            // Update primary job
            Job::getCollection()->findAndModify(
                [
                    '_id' => $this->_job->primaryID,
                ],
                [
                    '$pull' => [
                        'secondaryID.' . Job::STATUS_WAIT => $this->_job->_id,
                    ],
                    '$push' => [
                        'secondaryID.' . Job::STATUS_DONE => $this->_job->_id,
                    ],
                ]
            );

            // Change primary job status to IDLE if there are no secondaries left for processing
            Job::getCollection()->findAndModify(
                [
                    '_id' => $this->_job->primaryID,
                    'status' => Job::STATUS_WAIT,
                    'secondaryID.' . Job::STATUS_WAIT => ['$size' => 0],
                    'secondaryID.' . Job::STATUS_FAIL => ['$size' => 0],
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
        }

        $stats = $this->_worker['stats'];
        $stats[Job::STATUS_DONE]++;
        $this->_worker->stats = $stats;

        $this->_worker->jobID = null;
        $this->_worker->heartbeat = new UTCDateTime(time() * 1000);
        $this->_worker->update();
    }

    /**
     * Negatively acknowledges job.
     *
     * @param \Exception|\Error $e exception or error to log.
     */
    protected function _nack($e)
    {
        // Update job status and save exception data
        Job::getCollection()->findAndModify(
            [
                '_id' => $this->_job->_id,
            ],
            [
                '$set' => [
                    'status' => Job::STATUS_FAIL,
                    'error' => [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
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

        if ($this->_job->secondary) {
            // Update primary job
            Job::getCollection()->findAndModify(
                [
                    '_id' => $this->_job->primaryID,
                ],
                [
                    '$pull' => [
                        'secondaryID.' . Job::STATUS_WAIT => $this->_job->_id,
                    ],
                    '$push' => [
                        'secondaryID.' . Job::STATUS_FAIL => $this->_job->_id,
                    ],
                ]
            );
        }

        $stats = $this->_worker['stats'];
        $stats[Job::STATUS_FAIL]++;
        $this->_worker->stats = $stats;

        $this->_worker->jobID = null;
        $this->_worker->heartbeat = new UTCDateTime(time() * 1000);
        $this->_worker->update();
    }

    /**
     * Sends heartbeat of this worker.
     */
    protected function _heartbeat()
    {
        Worker::getCollection()->findAndModify(
            [
                '_id' => $this->_worker->_id,
            ],
            [
                '$set' => [
                    'heartbeat' => new UTCDateTime(time() * 1000),
                ],
            ]
        );
    }
}
