<?php

use Yii;

use yii\q\workers\BaseWorker;

class ReportWorker extends BaseWorker
{
    protected $_downloads = [];

    public function setUp()
    {
        foreach ($this->_job->secondaries as $job) {
            $this->_downloads[$job->data] = strlen($job->result);
        }
    }

    public function work()
    {
        return [
            'maxPageSize' => max($this->_downloads),
            'totalSize' => array_sum($this->_downloads),
        ];
    }

    public function tearDown()
    {
        $this->_downloads = null;
    }
}
