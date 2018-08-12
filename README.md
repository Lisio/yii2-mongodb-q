# MongoDB Queue Server extension for Yii2

This extension allow to use MongoDB as queue server without bloating project's stack and provides GUI.

Installation
------------
This extension requires MongoDB server version 3.6 or higher.
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).
Either run

```
php composer.phar require --prefer-dist lisio/yii2-mongodb-q
```
or add
```
"lisio/yii2-mongodb-q": "~1.0.0"
```
to the require section of your composer.json.

Configuration
-------------
To use this extension, add the following code to application web configuration:
```php
return [
    // ...
    'bootstrap' => [
        // ...,
        'q',
    ],
    'components' => [
        // ...
        'queue' => [
            'class' => '\yii\q\components\QueueServer',
        ],
    ],
    'modules' => [
        // ...
        'q' => [
            'class' => 'yii\q\Module',
        ],
    ],
];
```
And to application console configuration:
```php
return [
    // ...
    'controllerMap' => [
        // ...
        'q' => 'yii\q\commands\QueueController',
    ],
    'components' => [
        // ...
        'queue' => [
            'class' => '\yii\q\components\QueueServer',
        ],
    ],
];
```

Create indexes:
```php
./yii q/create-indexes
```

Usage
-----

This extension provides sample code for different cases (see folder `examples/`).

### Create queue
```php
Yii::$app->queue->queueCreate('test');
```

### Purge queue
```php
Yii::$app->queue->queuePurge('test');
```

### Remove queue
```php
Yii::$app->queue->queueRemove('test');
```

### Create job
```php
$data = [
    'someParam' => 'someValue',
];

$options = [
    'keepResult' => true,
    'keepResultDuration' => 86400,
];

$job = Yii::$app->queue->jobCreate('test', SomeWorker::className(), $data, $options);
```

### Pause job
```php
Yii::$app->queue->jobPause($job->_id);
```

### Resume job
```php
Yii::$app->queue->jobResume($job->_id);
```

### Remove job
```php
Yii::$app->queue->jobRemove($job->_id);
```

### Retry failed job
```php
Yii::$app->queue->jobRetry($job->_id);
```

### Get job data
```php
Yii::$app->queue->jobStatus($job->_id);
```

### Spawn worker and bind it to all queues
```
./yii q/spawn
```

### Spawn 3 workers and bind them to queues `download` and `report`.
```
./yii q/spawn 3 download,report
```

### Remove dead workers which processes are not found at this host
```
./yii q/remove-dead-workers
```

### Remove stale jobs which keepResultDuration is passed
```
./yii q/remove-stale-jobs
```

GUI
-----

GUI can be accessed at http://your.project.com/q.
