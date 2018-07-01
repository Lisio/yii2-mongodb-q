<?php

use yii\helpers\Url;

use yii\q\models\Job;
use yii\q\models\Worker;

$this->title = 'Dashboard';

?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-body no-padding">
                        <table class="table table-bordered">
                            <tr>
                                <th>Queue</th>
                                <th>Workers</th>
                                <th><span class="label label-primary">Status: IDLE</span></th>
                                <th><span class="label label-warning">Status: WORK</span></th>
                                <th><span class="label label-success">Status: DONE</span></th>
                                <th><span class="label label-danger">Status: FAIL</span></th>
                                <th><span class="label label-default">Status: STOP</span></th>
                                <th><span class="label label-default">Status: WAIT</span></th>
                            </tr>
                            <?php
                                $helpers = Worker::find()->where(['queues' => ['$eq' => []]])->count();
                            ?>
                            <?php foreach ($queues as $queue) { ?>
                                <?php
                                    $idle = $queue->getJobs()->andWhere(['status' => Job::STATUS_IDLE])->count();
                                    $fail = $queue->getJobs()->andWhere(['status' => Job::STATUS_FAIL])->count();
                                    $workers = $queue->getWorkers()->count();
                                ?>
                                <tr>
                                    <td><?= strtoupper($queue->name) ?></td>
                                    <?php if ($idle && !$workers) { ?>
                                        <?php if ($helpers) { ?>
                                            <td class="text-right">
                                                <?= $workers ?> + <?= $helpers ?>
                                                <div class="pull-left">
                                                    <i class="fa fa-exclamation-triangle text-primary" data-toggle="tooltip" title="No dedicated workers to process the jobs of this queue"></i>
                                                </div>
                                            </td>
                                        <?php } else { ?>
                                            <td class="text-right">
                                                <?= $workers ?> + <?= $helpers ?>
                                                <div class="pull-left">
                                                    <i class="fa fa-exclamation-triangle text-danger" data-toggle="tooltip" title="No workers to process the jobs"></i>
                                                </div>
                                            </td>
                                        <?php } ?>
                                    <?php } elseif ($idle && $idle / ($workers + $helpers) > 100) { ?>
                                        <td class="text-right">
                                            <?= $workers ?> + <?= $helpers ?>
                                            <div class="pull-left">
                                                <i class="fa fa-exclamation-triangle text-primary" data-toggle="tooltip" title="Consider increasing the number of workers for this queue"></i>
                                            </div>
                                        </td>
                                    <?php } else { ?>
                                        <td class="text-right"><?= $workers ?> + <?= $helpers ?></td>
                                    <?php } ?>
                                    <td class="text-right">
                                        <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'idle']) ?>">
                                            <?= $idle ?>
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'work']) ?>">
                                            <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_WORK])->count() ?>
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'done']) ?>">
                                            <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_DONE])->count() ?>
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'fail']) ?>">
                                            <?= $fail ?>
                                        </a>
                                        <?php if ($fail) { ?>
                                            <div class="pull-left">
                                                <i class="fa fa-exclamation-triangle text-danger" data-toggle="tooltip" title="Failed jobs require attention"></i>
                                            </div>
                                        <?php } ?>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'wait']) ?>">
                                            <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_WAIT])->count() ?>
                                        </a>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => '']) ?>">
                                            <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_STOP])->count() ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
