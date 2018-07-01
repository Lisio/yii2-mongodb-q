<?php

use yii\helpers\Url;

use yii\q\models\Worker;

$this->title = 'Workers';

?>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h3 class="box-title">Workers</h3>
                    </div>
                    <div class="box-body no-padding">
                        <table class="table table-bordered">
                            <tr>
                                <th>Worker ID</th>
                                <th>Queue</th>
                                <th>Host</th>
                                <th>PID</th>
                                <th>Status</th>
                                <th>Hearbeat</th>
                                <th>Job</th>
                            </tr>
                            <?php foreach ($workers as $worker) { ?>
                                <tr>
                                    <td><?= $worker->_id->__toString() ?></td>
                                    <td>
                                        <?= implode('<br>', $worker->queues) ?>
                                    </td>
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
                </div>
            </div>
        </div>
    </div>
</section>
