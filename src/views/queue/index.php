<?php

use yii\helpers\Url;

use yii\q\models\Job;
use yii\q\models\Worker;

$this->title = 'Queues';

$routes = json_encode([
    'ajaxQueuePurge' => Url::to(['queue/ajax-purge']),
    'ajaxQueueRemove' => Url::to(['queue/ajax-remove']),
]);
$this->registerJs("app.router = {$routes};", $this::POS_END);

?>

<section class="content">
    <div class="container-fluid">
        <?php if (Yii::$app->session->hasFlash('q')) { ?>
            <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                <h4><i class="icon fa fa-ban"></i> Warning!</h4>
                <?= Yii::$app->session->getFlash('q') ?>
            </div>
        <?php } ?>
        <?php foreach ($queues as $queue) { ?>
            <?php
                $idle = $queue->getJobs()->andWhere(['status' => Job::STATUS_IDLE])->count();
                $fail = $queue->getJobs()->andWhere(['status' => Job::STATUS_FAIL])->count();
                $workers = $queue->getWorkers()->count();
                $helpers = Worker::find()->where(['queues' => '*'])->andWhere(['queues' => ['$ne' => $queue->name]])->count();
                if ($fail) {
                    $className = 'box-danger';
                } elseif ($idle && !$workers) {
                    $className = $helpers ? 'box-warning' : 'box-danger';
                } else {
                    $className = 'box-primary';
                }
            ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="box <?= $className ?>">
                        <div class="box-header with-border">
                            <h3 class="box-title"><?= strtoupper($queue->name) ?></h3>
                            <div>
                                <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'idle']) ?>" class="label label-primary">
                                    IDLE: <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_IDLE])->count() ?>
                                </a>
                                <?php if ($queue->getJobs()->andWhere(['status' => Job::STATUS_WORK])->count()) { ?>
                                    <span>&nbsp;</span>
                                    <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'work']) ?>" class="label label-warning">
                                        WORK: <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_WORK])->count() ?>
                                    </a>
                                <?php } ?>
                                <?php if ($queue->getJobs()->andWhere(['status' => Job::STATUS_FAIL])->count()) { ?>
                                    <span>&nbsp;</span>
                                    <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'fail']) ?>" class="label label-danger">
                                        FAIL: <?= $fail ?>
                                    </a>
                                <?php } ?>
                                <?php if ($queue->getJobs()->andWhere(['status' => Job::STATUS_DONE])->count()) { ?>
                                    <span>&nbsp;</span>
                                    <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'done']) ?>" class="label label-success">
                                        DONE: <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_DONE])->count() ?>
                                    </a>
                                <?php } ?>
                                <?php if ($queue->getJobs()->andWhere(['status' => Job::STATUS_STOP])->count()) { ?>
                                    <span>&nbsp;</span>
                                    <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'stop']) ?>" class="label label-default">
                                        STOP: <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_STOP])->count() ?>
                                    </a>
                                <?php } ?>
                                <?php if ($queue->getJobs()->andWhere(['status' => Job::STATUS_WAIT])->count()) { ?>
                                    <span>&nbsp;</span>
                                    <a href="<?= Url::to(['queue/jobs', 'queueName' => $queue->name, 'status' => 'wait']) ?>" class="label label-default">
                                        WAIT: <?= $queue->getJobs()->andWhere(['status' => Job::STATUS_WAIT])->count() ?>
                                    </a>
                                <?php } ?>
                            </div>
                            <div class="box-tools pull-right">
                                <button class="btn btn-sm btn-warning js-purge" data-queue="<?= $queue->name ?>" data-toggle="tooltip" title="Purge"><i class="fa fa-trash"></i></button>
                                <button class="btn btn-sm btn-danger js-remove" data-queue="<?= $queue->name ?>" data-toggle="tooltip" title="Remove"><i class="fa fa-remove"></i></button>
                            </div>
                        </div>
                        <?php if ($queue->getWorkers()->count()) { ?>
                            <div class="box-body no-padding">
                                <table class="table table-bordered">
                                    <tr>
                                        <th>Worker ID</th>
                                        <th>Host</th>
                                        <th>PID</th>
                                        <th>Status</th>
                                        <th>Hearbeat</th>
                                        <th>Job</th>
                                    </tr>
                                    <?php foreach ($queue->getWorkers()->all() as $worker) { ?>
                                        <tr>
                                            <td><?= $worker->_id->__toString() ?></td>
                                            <td><?= $worker->hostname ?></td>
                                            <td><?= $worker->pid ?></td>
                                            <td><?= $worker->status ?></td>
                                            <td><?= $worker->heartbeat->toDateTime()->setTimeZone(new \DateTimeZone(Yii::$app->timeZone))->format('Y-m-d H:i:s') ?></td>
                                            <td>
                                                <?php if ($worker->jobID) { ?>
                                                    <a href="<?= Url::to(['job/view', 'id' => $worker->jobID]) ?>">
                                                        <?= $worker->jobID ?>
                                                    </a>
                                                <?php } else { ?>
                                                    N/A
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</section>
