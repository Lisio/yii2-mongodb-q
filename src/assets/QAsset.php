<?php

namespace yii\q\assets;

use yii\web\AssetBundle;

class QAsset extends AssetBundle
{
    public $sourcePath = '@yii/q/web';

    public $js = [
        'js/app.js',
        'js/controllers/job.js',
        'js/controllers/queue.js',
    ];

    public $depends = [
        'dmstr\web\AdminLteAsset',
    ];
}
