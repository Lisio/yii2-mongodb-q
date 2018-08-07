<?php

namespace yii\q\components;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

use yii\base\Component;
use yii\mongodb\Exception;

use yii\q\models\Job;
use yii\q\models\Queue;

/**
 * Main MongoDB Queue Server class. Used to manipulate queues and jobs through code.
 *
 * @property string $prefix prefix for queue collections
 */
class QueueServer extends Component
{
    /**
     * @var string $prefix prefix for queue collections
     */
    public $prefix = 'q';

    /**
     * Sets prefix for queue collections.
     *
     * @param string $prefix prefix for queue collections
     */
    public function setCollectionPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Creates queue with the given name.
     *
     * @param string $queueName name of the queue to create
     * @return \yii\q\models\Queue queue model
     * @throws \Exception if queue already exists
     */
    public function queueCreate($queueName)
    {
        try {
            $queue = new Queue;

            $queue->name = $queueName;

            if (!$queue->validate()) {
                foreach ($queue->getErrors() as $attribute => $errors) {
                    throw new \Exception($queue->getErrorSummary(true)[0]);
                }
            }

            $queue->insert();
        } catch (Exception $e) {
            if (strncmp($e->getMessage(), 'E11000', 6) === 0) {
                throw new \Exception('QUEUE_NAME_EXISTS');
            }

            throw $e;
        }

        return $queue;
    }

    /**
     * Purges queue with the given name.
     * Queue can be purged only if it doesn't contain secondary jobs that will be used in primary jobs.
     *
     * @param string $queueName name of the queue to purge
     * @return boolean whether the queue is purged
     * @throws \Exception if queue cannot be purged
     */
    public function queuePurge($queueName)
    {
        return $this->_purgeQueue($queueName);
    }

    /**
     * Removes queue with the given name and all jobs in it.
     * Queue can be removed only if it doesn't contain secondary jobs that will be used in primary jobs.
     *
     * @param string $queueName name of the queue to remove
     * @return boolean whether the queue is removed
     * @throws \Exception if queue cannot be removed
     */
    public function queueRemove($queueName)
    {
        return $this->_purgeQueue($queueName, true);
    }

    /**
     * Creates job.
     *
     * Allowed job options include the following:
     * priority: non-negative integer. The higher the number - the faster job will be processed.
     * schedule: RFC3339 compliant datetime string. Used to schedule the time when job will get in queue for processing
     * keepResult: boolean. Whether to keep result after processing.
     * keepResultDuration: positive integer. A number of seconds to keep result after processing. Required if keepResult is true.
     * primary: boolean. Whether this job is primary for another jobs (will be processed after all secondary jobs get DONE status).
     * removeSecondaries: boolean. Wheter to remove secondary jobs after this primary job get DONE status.
     * secondary: boolean. Whether this job is secondary.
     * primaryID: \MongoDB\BSON\ObjectId. ID of primary job. Required if secondary is true.
     *
     * Primary jobs receive WAIT status and should be resumed via jobResume method after all secodaries are added.
     *
     * @param string $queueName name of the queue to put job in
     * @param string $className fully qualified name of the worker class which will process this job
     * @param mixed $data custom job data that worker will use to process it
     * @param array $options additional job params
     * @return \yii\q\models\Job job model
     * @throws \Exception if job cannot be created
     */
    public function jobCreate($queueName, $className, $data = null, $options = [])
    {
        $job = new Job;

        try {
            $job->queue = $queueName;
            $job->worker = $className;
            $job->data = $data;

            foreach ($options as $option => $value) {
                $job->$option = $value;
            }

            if (!$job->validate()) {
                throw new \Exception($job->getErrorSummary(true)[0]);
            }

            $job->insert();
        } catch (\Exception $e) {
            throw $e;
        }

        return $job;
    }

    /**
     * Prevents job from processing until resumed.
     * Job can be paused only when it is in IDLE state.
     *
     * @param \MongoDB\BSON\ObjectId @jobID ID of the job to pause
     * @return \yii\q\models\Job job model
     * @throws \Exception if job not found or cannot be paused at the moment
     */
    public function jobPause($jobID)
    {
        $j = Job::getCollection()->findAndModify(
            [
                '_id' => $jobID,
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
            ],
            [
                'new' => true,
            ]
        );

        if ($j === null) {
            $job = Job::findOne(['_id' => $jobID]);

            if ($job === null) {
                throw new \Exception('JOB_NOT_FOUND');
            } else {
                throw new \Exception('JOB_NOT_PAUSABLE');
            }
        }

        $job = new Job;

        Job::populateRecord($job, $j);

        return $job;
    }

    /**
     * Removes job.
     * Job can be removed only when it is in IDLE, DONE of FAIL state and no unprocessed primary job depend on it.
     *
     * @param \MongoDB\BSON\ObjectId @jobID ID of the job to pause
     * @return \yii\q\models\Job removed job model
     * @throws \Exception if job not found or cannot be removed at the moment
     */
    public function jobRemove($jobID)
    {
        $job = Job::findOne($jobID);

        if ($job === null) {
            throw new \Exception('JOB_NOT_FOUND');
        }

        if ($job->secondary) {
            $primary = Job::findOne($job->primaryID);
            if ($primary && in_array($primary->status, [Job::STATUS_IDLE, Job::STATUS_WORK, Job::STATUS_WAIT])) {
                throw new \Exception('JOB_NOT_REMOVABLE');
            }
        }

        $success = Job::getCollection()->remove(
            [
                '_id' => $jobID,
                'status' => [
                    Job::STATUS_DONE,
                    Job::STATUS_FAIL,
                    Job::STATUS_IDLE,
                ],
            ]
        );

        if ($success) {
            return $job;
        }

        throw new \Exception('JOB_NOT_REMOVABLE');
    }

    /**
     * Resumes job for further processing.
     * Job can be resumed only when it is in STOP or WAIT state.
     * This method is also used to put primary job in IDLE state when all secondaries are added.
     *
     * @param \MongoDB\BSON\ObjectId @jobID ID of the job to pause
     * @return \yii\q\models\Job job model
     * @throws \Exception if job not found or cannot be resumed
     */
    public function jobResume($jobID)
    {
        $j = Job::getCollection()->findAndModify(
            [
                '_id' => $jobID,
                'status' => [
                    Job::STATUS_STOP,
                    Job::STATUS_WAIT,
                ],
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
            ],
            [
                'new' => true,
            ]
        );

        if ($j === null) {
            $job = Job::findOne(['_id' => $jobID]);

            if ($job === null) {
                throw new \Exception('JOB_NOT_FOUND');
            } else {
                throw new \Exception('JOB_NOT_RESUMABLE');
            }
        }

        $job = new Job;

        Job::populateRecord($job, $j);

        return $job;
    }

    /**
     * Resumes failed job for further processing.
     * Job can be retried only when it is in FAIL state.
     *
     * @param \MongoDB\BSON\ObjectId @jobID ID of the job to pause
     * @return \yii\q\models\Job job model
     * @throws \Exception if job not found or cannot be retried
     */
    public function jobRetry($jobID)
    {
        $j = Job::getCollection()->findAndModify(
            [
                '_id' => $jobID,
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
            ],
            [
                'new' => true,
            ]
        );

        if ($j === null) {
            throw new \Exception('JOB_NOT_FOUND');
        }

        if ($j && $j['secondary']) {
            // Update primary job
            Job::getCollection()->findAndModify(
                [
                    '_id' => $j['primaryID'],
                    'secondaryID.' . Job::STATUS_FAIL => $j['_id'],
                ],
                [
                    '$pull' => [
                        'secondaryID.' . Job::STATUS_FAIL => $j['_id'],
                    ],
                    '$push' => [
                        'secondaryID.' . Job::STATUS_WAIT => $j['_id'],
                    ],
                ]
            );
        }

        $job = new Job;

        Job::populateRecord($job, $j);

        return $job;
    }

    /**
     * Returns job.
     *
     * @param \MongoDB\BSON\ObjectId @jobID ID of the job to pause
     * @return \yii\q\models\Job job model
     * @throws \Exception if job not found
     */
    public function jobStatus($jobID)
    {
        $job = Job::findOne($jobID);

        if ($job === null) {
            throw new \Exception('JOB_NOT_FOUND');
        }

        return $job;
    }

    /**
     * Internal method to purge and/or remove queue.
     *
     * @param string $queueName name of the queue to purge/remove
     * @param boolean $remove whether to remove job after purging
     * @return boolean whether queue is purged/removed
     * @throws \Exception if queue not purgeable
     */
    protected function _purgeQueue($queueName, $remove = false)
    {
        $primaries = Job::getCollection()->aggregate([
            [
                '$match' => [
                    'queue' => $queueName,
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
                    'primary.queue' => ['$ne' => $queueName],
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
            throw new \Exception('QUEUE_NOT_PURGEABLE');
        }

        if ($remove) {
            Queue::getCollection()->remove(['name' => $queueName]);
            sleep(3); // Wait 3 secs to be sure that no new jobs are queued
        }

        $primaries = Job::getCollection()->aggregate([
            [
                '$match' => [
                    'queue' => $queueName,
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
                    'primary.queue' => ['$ne' => $queueName],
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

        Job::getCollection()->remove(['queue' => $queueName]);

        return true;
    }
}
