<?php

namespace yii\q\models;

use MongoDB\BSON\UTCDateTime;

use yii\mongodb\ActiveRecord;

class Worker extends ActiveRecord
{
    const STATUS_IDLE = 'IDLE';
    const STATUS_WORK = 'WORK';

    public static function collectionName()
    {
        return 'worker';
    }

    public function attributes()
    {
        return [
            '_id',

            'hostname',
            'pid',

            'queues',

            'status',
            'jobID',

            'heartbeat',

            'stats',
        ];
    }

    public function fields()
    {
        return $this->attributes();
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->status = self::STATUS_IDLE;
            $this->stats = [
                Job::STATUS_DONE => 0,
                Job::STATUS_FAIL => 0,
            ];
        } else {
            if ($this->jobID) {
                $this->status = self::STATUS_WORK;
            } else {
                $this->status = self::STATUS_IDLE;
            }
        }

        $this->heartbeat = new UTCDateTime(time() * 1000);

        return parent::beforeSave($insert);
    }
}
