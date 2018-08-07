<?php

namespace yii\q\models;

use Yii;
use yii\mongodb\ActiveRecord;

class Queue extends ActiveRecord
{
    public static function collectionName()
    {
        return Yii::$app->queue->prefix . '.' . parent::collectionName();
    }

    public function attributes()
    {
        return [
            '_id',
            'name',
        ];
    }

    public function fields()
    {
        return $this->attributes();
    }

    public function getJobs()
    {
        return $this->hasMany(Job::className(), ['queue' => 'name']);
    }

    public function getWorkers()
    {
        return $this->hasMany(Worker::className(), ['queues' => 'name']);
    }

    public function rules()
    {
        return [
            [
                'name',
                'unique',
                'message' => 'QUEUE_NAME_EXISTS',
            ],
        ];
    }
}
