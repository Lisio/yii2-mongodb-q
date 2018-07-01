<?php

echo dmstr\widgets\Menu::widget([
    'items' => [
        [
            'url' => ['default/index'],
            'icon' => 'dashboard',
            'label' => 'Dashboard',
            'active' => Yii::$app->controller->id == 'default',
        ],
        [
            'url' => ['queue/index'],
            'icon' => 'list',
            'label' => 'Queues',
            'active' => Yii::$app->controller->id == 'queue',
        ],
        [
            'url' => ['worker/index'],
            'icon' => 'wrench',
            'label' => 'Workers',
            'active' => Yii::$app->controller->id == 'worker',
        ],
    ],
]);
