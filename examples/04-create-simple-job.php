<?php

namespace yii\q\examples;

use Yii;

use DummyWorker;

class Example
{
    public function run()
    {
        try {
            Yii::$app->queue->jobCreate('test', DummyWorker::className());
        } catch (\Exception $e) {
            echo "Something wrong: {$e->getMessage()}" . PHP_EOL;
        }
    }
}
