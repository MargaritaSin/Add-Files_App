<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'WhXnWLrRrbfB1lxgzDZdcl_iabWLBkCp',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
            'charset' => 'UTF-8',
        ],

        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
                'identityClass' => 'app\models\User',
                'enableSession' => false, // API не должно поддерживать сессии
                'loginUrl' => null,       // Перенаправление отключено
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,

        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST authorization' => 'auth/authorization',
                'POST registration' => 'auth/registration',
                'GET logout' => 'auth/logout',
                'POST upload' => 'file/files',
                'PUT,PATCH files/update/<file_id:\d+>' => 'file/update-file',
                'DELETE files/delete/<file_id:\d+>' => 'file/delete-file',
                'GET files/download/<file_id:\d+>' => 'file/download',
                'POST files/<file_id:\d+>/accesses' => 'file/add-access',
                'DELETE files/<file_id:\d+>/accesses' => 'file/remove-access',
                'GET files/disk' => 'file/disk',
                'GET shared' => 'file/shared',
                'GET files/<file_id:\d+>/co-authors' => 'file/get-co-authors',
            ],
        ],
    ],
    'params' => $params,
    'on beforeRequest' => function ($event) {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE, PATCH");
        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
            header("Access-Control-Allow-Headers: Content-Type, Registration,  Authorization, X-Requested-With");
            Yii::$app->getResponse()->getHeaders()->set('Access-Control-Max-Age', 86400);
            Yii::$app->end();
        }
    },
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
