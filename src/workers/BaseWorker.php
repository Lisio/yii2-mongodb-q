<?php

namespace yii\q\workers;

use yii\base\Component;

/**
 * Base class for queue workers.
 *
 * @property \yii\q\models\Job $_job job being processed
 * @property \yii\q\components\Overseer $_overseer Worker overseer
 */
class BaseWorker extends Component
{
    /**
     * @var \yii\q\models\Job job being processed
     */
    protected $_job;

    /**
     * @var \yii\q\components\Overseer Worker overseer
     */
    protected $_overseer;

    /**
     * Sets job for processing
     *
     * @param \yii\q\models\Job job for processing
     */
    public function setJob($job)
    {
        $this->_job = $job;
    }

    /**
     * Sets overseer
     *
     * @param \yii\q\components\Overseer;
     */
    public function setOverseer($overseer)
    {
        $this->_overseer = $overseer;
    }

    /**
     * Sets job progress
     *
     * @param float $progress progress value between 0 and 100
     */
    public function setProgress($progress = null)
    {
        $this->_overseer->setProgress($progress);
    }
}
