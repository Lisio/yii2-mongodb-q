<?php

use yii\helpers\Html;

use yii\q\assets\QAsset;
use yii\q\models\Job;

QAsset::register($this);

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <base href="/">

        <meta charset="<?= Yii::$app->charset ?>">
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
        <?= Html::csrfMetaTags() ?>

        <title>MongoDB Q</title>

        <link href="//code.ionicframework.com/ionicons/1.5.2/css/ionicons.min.css" rel="stylesheet" type="text/css"/>
        <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic" rel="stylesheet" media="all">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" rel="stylesheet" media="all">

        <?php $this->head() ?>

        <!--[if lt IE 9]>
            <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
            <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
        <![endif]-->
    </head>
    <body class="hold-transition <?= Job::find()->where(['status' => Job::STATUS_FAIL])->count() ? 'skin-red' : 'skin-green' ?> sidebar-mini" data-module="<?= Yii::$app->controller->module->id ?>" data-controller="<?= Yii::$app->controller->id ?>" data-action="<?= Yii::$app->controller->action->id ?>">
        <?php $this->beginBody() ?>
        <div class="wrapper">
            <header class="main-header">
                <a href="/console" class="logo">MongoDB Queue</a>
                <nav class="navbar navbar-static-top" role="navigation">
                </nav>
            </header>
            <aside class="main-sidebar">
                <section class="sidebar">
                    <?= $this->render('sidebar') ?>
                </section>
            </aside>
            <div class="content-wrapper">
                <?= $content ?>
            </div>
            <footer class="main-footer">
                <strong><a href="http://kepler.space">MongoDB Queue at GitHub</a></strong>
            </footer>
        </div>
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
