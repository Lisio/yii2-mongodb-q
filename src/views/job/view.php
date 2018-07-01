<?php

use yii\helpers\Url;

use yii\q\models\Job;

$this->title = 'Job - ' . $job->_id;

$routes = json_encode([
    'queueIndex' => Url::to(['queue/index']),
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
                <?php
                    switch ($job->status) {
                        case Job::STATUS_DONE:
                            $labelClass = 'label-success';
                            $boxClass = 'box-success';
                            break;
                        case Job::STATUS_FAIL:
                            $labelClass = 'label-danger';
                            $boxClass = 'box-danger';
                            break;
                        case Job::STATUS_IDLE:
                            $labelClass = 'label-primary';
                            $boxClass = 'box-primary';
                            break;
                        case Job::STATUS_STOP:
                        case Job::STATUS_WAIT:
                            $labelClass = 'label-default';
                            $boxClass = 'box-default';
                            break;
                        case Job::STATUS_WORK:
                            $labelClass = 'label-warning';
                            $boxClass = 'box-warning';
                            break;
                    }
                ?>
                <div class="box <?= $boxClass ?>">
                    <div class="box-header with-border">
                        <h3 class="box-title"><?= strtoupper($job->queue) ?> / <?= $job->_id ?></h3>
                        <div>
                            <span data-toggle="tooltip" class="label label-sm <?= $labelClass ?>">
                                Status: <?= $job->status ?>
                            </span>
                        </div>
                        <div class="box-tools pull-right">
                            <?php if ($job->status == Job::STATUS_FAIL) { ?>
                                <a href="#" class="btn btn-sm btn-primary js-retry" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Retry">
                                    <i class="fa fa-repeat"></i>
                                </a>
                            <?php } ?>
                            <?php if ($job->status == Job::STATUS_IDLE) { ?>
                                <a href="#" class="btn btn-sm btn-default js-pause" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Pause">
                                    <i class="fa fa-pause"></i>
                                </a>
                            <?php } ?>
                            <?php if ($job->status == Job::STATUS_STOP) { ?>
                                <a href="#" class="btn btn-sm btn-primary js-resume" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Resume">
                                    <i class="fa fa-play"></i>
                                </a>
                            <?php } ?>
                            <?php if (in_array($job->status, [Job::STATUS_IDLE, Job::STATUS_DONE, Job::STATUS_STOP, Job::STATUS_FAIL])) { ?>
                                <a href="#" class="btn btn-sm btn-danger js-remove" data-id="<?= $job->_id ?>" data-toggle="tooltip" title="Remove">
                                    <i class="fa fa-remove"></i>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="box-body">
                        <?php if ($job->status == Job::STATUS_WORK) { ?>
                            <div class="progress-group">
                                <span class="progress-text">Work progress</span>
                                <span class="progress-number"><b><?= $job->progress ?>%</b></span>
                                <div class="progress sm">
                                    <div class="progress-bar progress-bar-aqua" style="width: <?= (int)$job->progress->__toString() ?>%"></div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($job->priority || $job->schedule || $job->keepResult || $job->primary || $job->secondary) { ?>
                            <h4>Additional Params</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Param</th>
                                    <th>Value</th>
                                </tr>
                                <?php if ($job->priority) { ?>
                                    <tr>
                                        <td>Priority</td>
                                        <td><?= $job->priority ?></td>
                                    </tr>
                                <?php } ?>
                                <?php if ($job->schedule) { ?>
                                    <tr>
                                        <td>Schedule</td>
                                        <td><?= $job->schedule->toDateTime()->format('Y-m-d H:i:s') ?></td>
                                    </tr>
                                <?php } ?>
                                <tr>
                                    <td>Keep result</td>
                                    <td>
                                        <?php
                                            if ($job->keepResultDuration) {
                                                do {
                                                    $duration = $job->keepResultDuration;
                                                    $unit = 'seconds';
                                                    if ($duration % 60 === 0) {
                                                        $duration /= 60;
                                                        $unit = 'minutes';
                                                    }
                                                    if ($duration % 60 === 0) {
                                                        $duration /= 60;
                                                        $unit = 'hours';
                                                    }
                                                    if ($duration % 24 === 0) {
                                                        $duration /= 24;
                                                        $unit = 'days';
                                                    }
                                                } while (false);
                                                echo number_format($duration, 0, '', ' ') . ' ' . $unit;
                                            } else {
                                                echo 'No';
                                            }
                                        ?>
                                    </td>
                                </tr>
                                <?php if ($job->primary) { ?>
                                    <tr>
                                        <td>Primary</td>
                                        <td>Yes</td>
                                    </tr>
                                    <tr>
                                        <td>Remove secondaries</td>
                                        <td><?= $job->removeSecondaries ? 'Yes' : 'No' ?></td>
                                    </tr>
                                    <tr>
                                        <td>Secondaries</td>
                                        <td>
                                            <?php if ($count = count($job->secondaryID[Job::STATUS_WAIT])) { ?>
                                                <span class="label label-primary"><?= Job::STATUS_WAIT ?>: <?= $count ?></span>
                                            <?php } ?>
                                            <?php if ($count = count($job->secondaryID[Job::STATUS_DONE])) { ?>
                                                <span class="label label-success"><?= Job::STATUS_DONE ?>: <?= $count ?></span>
                                            <?php } ?>
                                            <?php if ($count = count($job->secondaryID[Job::STATUS_FAIL])) { ?>
                                                <span class="label label-danger"><?= Job::STATUS_FAIL ?>: <?= $count ?></span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if ($job->secondary) { ?>
                                    <tr>
                                        <td>Secondary</td>
                                        <td>Yes</td>
                                    </tr>
                                    <tr>
                                        <td>Primary Job ID</td>
                                        <td><a href="<?= Url::to(['job/view', 'id' => $job->primaryID]) ?>"><?= $job->primaryID ?></a></td>
                                    </tr>
                                <?php } ?>
                            </table>
                            <br>
                        <?php } ?>
                        <?php if ($job->status == Job::STATUS_FAIL) { ?>
                            <h4>Error</h4>
                            <pre><?= htmlspecialchars($job->error['message']) . PHP_EOL . htmlspecialchars($job->error['trace']) ?></pre>
                            <br>
                        <?php } ?>
                        <h4>Data</h4>
                        <pre><?= htmlspecialchars(json_encode($job->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        <?php if ($job->status == Job::STATUS_DONE) { ?>
                            <br>
                            <h4>Result</h4>
                            <pre><?= htmlspecialchars(json_encode($job->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
                        <?php } ?>
                        <br>
                        <h4>Work Log</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Worker ID</th>
                            </tr>
                            <?php foreach (array_reverse($job->log) as $log) { ?>
                                <tr>
                                    <td><?= $log['datetime']->toDateTime()->format('Y-m-d H:i:s') ?></td>
                                    <td><?= $log['status'] ?></td>
                                    <td><?= isset($log['workerID']) ? $log['workerID'] : 'N/A' ?></td>
                                </tr>
                            <?php } ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
