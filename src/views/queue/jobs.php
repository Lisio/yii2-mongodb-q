<?php

use yii\helpers\Url;

use yii\q\models\Job;

$this->title = 'Queue - ' . strtoupper($queue->name);

$routes = json_encode([
    'ajaxJobPause' => Url::to(['job/ajax-pause']),
    'ajaxJobRemove' => Url::to(['job/ajax-remove']),
    'ajaxJobResume' => Url::to(['job/ajax-resume']),
    'ajaxJobRetry' => Url::to(['job/ajax-retry']),
]);
$this->registerJs("app.router = {$routes};", $this::POS_END);

?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= strtoupper($queue->name) ?> / JOBS</h3>
                        <div>
                            <?php
                                switch ($status) {
                                    case Job::STATUS_DONE:
                                        $labelClass = 'label-success';
                                        break;
                                    case Job::STATUS_FAIL:
                                        $labelClass = 'label-danger';
                                        break;
                                    case Job::STATUS_IDLE:
                                        $labelClass = 'label-primary';
                                        break;
                                    case Job::STATUS_STOP:
                                    case Job::STATUS_WAIT:
                                        $labelClass = 'label-default';
                                        break;
                                    case Job::STATUS_WORK:
                                        $labelClass = 'label-warning';
                                        break;
                                }
                            ?>
                            <span data-toggle="tooltip" class="label label-sm <?= $labelClass ?>">
                                Status: <?= $status ?>
                            </span>
                        </div>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-bordered">
                            <tr>
                                <th>Job ID</th>
                                <th>Date & Time</th>
                                <th>Error</th>
                                <th>Action</th>
                            </tr>
                            <?php foreach ($jobs as $job) { ?>
                                <tr>
                                    <td><?= $job->_id ?></td>
                                    <td><?= $job->log[count($job->log) - 1]['datetime']->toDateTime()->format('Y-m-d H:i:s') ?></td>
                                    <td><?= $job->error['message'] ?></td>
                                    <td>
                                        <a href="<?= Url::to(['job/view', 'id' => $job->_id]) ?>" class="btn btn-xs btn-default" data-toggle="tooltip" title="View details"><i class="fa fa-eye"></i></a>
                                        <?php if ($job->status == Job::STATUS_FAIL) { ?>
                                            <a href="#" class="btn btn-xs btn-primary js-retry" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Retry"><i class="fa fa-repeat"></i></a>
                                        <?php } ?>
                                        <?php if ($job->status == Job::STATUS_IDLE) { ?>
                                            <a href="#" class="btn btn-xs btn-default js-pause" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Pause">
                                                <i class="fa fa-pause"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if ($job->status == Job::STATUS_STOP) { ?>
                                            <a href="#" class="btn btn-xs btn-primary js-resume" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Resume">
                                                <i class="fa fa-play"></i>
                                            </a>
                                        <?php } ?>
                                        <?php if (in_array($job->status, [Job::STATUS_IDLE, Job::STATUS_DONE, Job::STATUS_STOP, Job::STATUS_FAIL])) { ?>
                                            <a href="#" class="btn btn-xs btn-danger js-remove" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Remove"><i class="fa fa-remove"></i></a>
                                        <?php } ?>
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
