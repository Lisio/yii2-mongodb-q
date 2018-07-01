<?php

use Yii;

use yii\q\workers\BaseWorker;

class DummyWorker extends BaseWorker
{
    public function setUp()
    {
    }

    public function work()
    {
        for ($i = 1; $i <= 100; $i++) {
            sleep(1);
            $this->setProgress($i); // Report the progress of our heavy work
        }
    }

    public function tearDown()
    {
    }
}
