<?php

namespace yii\q\examples;

use yii\q\workers\BaseWorker;

class DownloadWorker extends BaseWorker
{
    public function setUp()
    {
        // Prepare curl or init some components
    }

    public function work()
    {
        return file_get_contents($this->_job->data);
    }

    public function tearDown()
    {
        // Collect some garbage here
    }
}
