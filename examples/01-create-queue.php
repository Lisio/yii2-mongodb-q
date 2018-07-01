<?php

namespace yii\q\examples;

use Yii;

class Example
{
    public function run()
    {
        try {
            Yii::$app->queue->queueCreate('test');
        } catch (\Exception $e) {
            echo "Something wrong: {$e->getMessage()}" . PHP_EOL;
        }
    }
}
