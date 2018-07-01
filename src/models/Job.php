<?php

namespace yii\q\models;

use MongoDB\BSON\UTCDateTime;

use yii\mongodb\ActiveRecord;

class Job extends ActiveRecord
{
    const STATUS_DONE = 'DONE';
    const STATUS_FAIL = 'FAIL';
    const STATUS_IDLE = 'IDLE';
    const STATUS_STOP = 'STOP';
    const STATUS_WAIT = 'WAIT';
    const STATUS_WORK = 'WORK';

    public static function collectionName()
    {
        return 'job';
    }

    public function attributes()
    {
        return [
            '_id',

            'queue',
            'worker',
            'data',

            'status',
            'error',
            'result',

            'priority',

            'schedule',

            'keepResult',
            'keepResultDuration',

            'primary',
            'secondaryID',
            'removeSecondaries',

            'secondary',
            'primaryID',

            'log',
            'progress',

            'workerID',
        ];
    }

    public function fields()
    {
        return $this->attributes();
    }

    public function rules()
    {
        return [
            [
                'queue',
                'required',
                'message' => 'JOB_QUEUE_REQUIRED',
            ],
            [
                'queue',
                'string',
                'message' => 'JOB_QUEUE_FORMAT',
            ],
            [
                'queue',
                'exist',
                'targetClass' => Queue::className(),
                'targetAttribute' => 'name',
                'message' => 'JOB_QUEUE_NOT_FOUND',
            ],
            [
                'worker',
                'required',
                'message' => 'JOB_WORKER_REQUIRED',
            ],
            [
                'worker',
                function ($attribute, $params) {
                    if (!class_exists($this->$attribute)) {
                        $this->addError('worker', 'JOB_WORKER_NOT_FOUND');
                    }
                },
            ],
            [
                'data',
                'safe',
            ],
            [
                'priority',
                'integer',
                'min' => 0,
                'message' => 'JOB_PRIORITY_FORMAT',
                'tooSmall' => 'JOB_PRIORITY_RANGE',
            ],
            [
                'keepResult',
                'boolean',
                'message' => 'JOB_KEEP_RESULT_FORMAT',
            ],
            [
                'keepResultDuration',
                'required',
                'message' => 'JOB_KEEP_RESULT_DURATION_REQUIRED',
                'when' => function($model) {
                    return $model->keepResult;
                },
            ],
            [
                'keepResultDuration',
                'integer',
                'min' => 1,
                'tooSmall' => 'JOB_KEEP_RESULT_DURATION_RANGE',
                'message' => 'JOB_KEEP_RESULT_DURATION_FORMAT',
                'when' => function($model) {
                    return $model->keepResult;
                },
            ],
            [
                'schedule',
                'datetime',
                'format' => 'php:' . \DateTime::RFC3339,
                'timestampAttribute' => 'schedule',
                'message' => 'JOB_SCHEDULE_FORMAT',
            ],
            [
                'schedule',
                function($attribute, $params) {
                    if ($this->$attribute < time()) {
                        $this->addError($attribute, 'JOB_SCHEDULE_INCORRECT_VALUE');
                    }
                },
            ],
            [
                'primary',
                'boolean',
                'message' => 'JOB_PRIMARY_INVALID_FORMAT',
            ],
            [
                'secondary',
                'boolean',
                'message' => 'JOB_SECONDARY_INVALID_FORMAT',
            ],
            [
                'primaryID',
                'required',
                'when' => function($model) {
                    return $this->secondary;
                },
            ],
            [
                'primaryID',
                'exist',
                'targetAttribute' => '_id',
                'filter' => function($query) {
                    $query->andWhere(['primary' => true]);
                },
                'message' => 'JOB_PRIMARY_NOT_FOUND',
                'when' => function($model) {
                    return $this->secondary;
                },
            ],
            [
                'removeSecondaries',
                'boolean',
                'message' => 'JOB_REMOVE_SECONDARIES_INVALID_FORMAT',
                'when' => function($model) {
                    return $this->primary;
                },
            ],
        ];
    }

    public function getPrimary()
    {
        return $this->hasOne(self::className(), ['_id' => 'primaryID']);
    }

    public function getSecondaries()
    {
        return $this->hasMany(self::className(), ['primaryID' => '_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert && $this->secondary) {
            Job::getCollection()->findAndModify(
                [
                    '_id' => $this->primaryID,
                ],
                [
                    '$push' => [
                        'secondaryID.' . self::STATUS_WAIT => $this->_id,
                    ],
                ]
            );

            Job::getCollection()->findAndModify(
                [
                    '_id' => $this->_id,
                ],
                [
                    '$set' => [
                        'status' => self::STATUS_IDLE,
                    ],
                    '$push' => [
                        'log' => [
                            'status' => self::STATUS_IDLE,
                            'datetime' => new UTCDateTime(time() * 1000),
                        ],
                    ],
                ]
            );
        }

        return parent::afterSave($insert, $changedAttributes);
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            if ($this->primary) {
                $this->status = self::STATUS_WAIT;

                $this->primary = true;
                $this->secondaryID = [
                    self::STATUS_WAIT => [],
                    self::STATUS_DONE => [],
                    self::STATUS_FAIL => [],
                ];
            } elseif ($this->secondary) {
                $this->status = self::STATUS_WAIT;
            } else {
                $this->status = self::STATUS_IDLE;
            }

            $this->progress = new Decimal128('0.00');

            $this->log = [
                [
                    'status' => $this->status,
                    'datetime' => new UTCDateTime(time() * 1000),
                ],
            ];
        }

        return parent::beforeSave($insert);
    }
}
