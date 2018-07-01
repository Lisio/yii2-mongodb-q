<?php

namespace yii\q;

class Module extends \yii\base\Module implements \yii\base\BootstrapInterface
{
    public function bootstrap($app)
    {
        if ($app instanceof yii\console\Application) {
            $this->controllerNamespace = 'yii\q\commands';
        }

        $app->getUrlManager()->addRules([
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id,
                'route' => $this->id . '/default/index',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/queue',
                'route' => $this->id . '/queue/index',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/queue/<queueName:[\w\-]+>/<status:(done|fail|idle|stop|wait|work)>',
                'route' => $this->id . '/queue/jobs',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/queue/ajax/purge',
                'route' => $this->id . '/queue/ajax-purge',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/queue/ajax/remove',
                'route' => $this->id . '/queue/ajax-remove',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/worker',
                'route' => $this->id . '/worker/index',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/job/<id:[a-f\d]{24}>',
                'route' => $this->id . '/job/view',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/job/ajax/pause',
                'route' => $this->id . '/job/ajax-pause',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/job/ajax/remove',
                'route' => $this->id . '/job/ajax-remove',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/job/ajax/resume',
                'route' => $this->id . '/job/ajax-resume',
            ],
            [
                'class' => '\yii\web\UrlRule',
                'pattern' => $this->id . '/job/ajax/retry',
                'route' => $this->id . '/job/ajax-retry',
            ],
        ]);
    }
}
