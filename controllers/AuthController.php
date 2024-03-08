<?php

namespace app\controllers;

use Yii;
use yii\rest\ActiveController;
use app\models\User;
use yii\web\UnauthorizedHttpException;
use app\models\UploadForm;
use app\models\Files;
use yii\web\UploadedFile;
use yii\filters\Cors;
use yii\helpers\ArrayHelper;
use yii\filters\VerbFilter;
use yii\filters\ContentNegotiator;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\web\Response;

class AuthController extends ActiveController
{
    public $modelClass = 'app\models\User';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);

        return ArrayHelper::merge([
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    'Origin' => ['http://localhost:8081'],
                    'Access-Control-Request-Method' => ['POST', 'GET', 'DELETE', 'PATCH'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 3600,
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'update-file' => ['patch'],
                ],
            ],
        ],
            [
                'contentNegotiator' => [
                    'class' => \yii\filters\ContentNegotiator::className(),
                    'formats' => [
                        'application/json' => \yii\web\Response::FORMAT_JSON,
                    ],
                ],
                'authenticator' => [
                    'class' => \yii\filters\auth\CompositeAuth::className(),
                    'authMethods' => [
                        \yii\filters\auth\HttpBearerAuth::className(),
                    ],
                    'except' => ['options', 'authorization', 'registration'],
                ],
            ]);
    }


    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete'], $actions['view'], $actions['index']);  // Отключаем стандартные действия
        return $actions;
    }

    public function actionAuthorization()
    {
        $request = Yii::$app->request;
        $email = $request->post('email');
        $password = $request->post('password');
        $user = User::findByEmail($email);

        if (!$user || !$user->validatePassword($password)) {
            throw new UnauthorizedHttpException("Login failed", 401);
        }

// Генерация и сохранение токена
        $token = Yii::$app->security->generateRandomString();
        $user->token = $token;
        if (!$user->save()) {
            throw new \yii\web\ServerErrorHttpException('Failed to generate token');
        }

        return [
            'success' => true,
            'message' => 'Success',
            'token' => $token,
        ];
    }


    public function actionRegistration()
    {
        $model = new \app\models\User();
        $model->load(Yii::$app->request->post(), '');

        if ($model->validate() && $model->save()) {
            // Генерация токена после успешной валидации и сохранения пользователя
            $model->token = Yii::$app->security->generateRandomString();
            $model->save(false); // Сохраняем модель вновь, теперь уже с токеном

            return [
                'success' => true,
                'message' => 'Success',
                'token' => $model->token,
            ];
        } else {
            // Возвращение ошибок валидации
            Yii::$app->response->statusCode = 400;
            return [
                'success' => false,
                'message' => 'Validation Failed',
                'errors' => $model->getErrors(),
            ];
        }
    }

    public function actionLogout()
    {
        // Примерная логика удаления токена пользователя из БД или сессии
        Yii::$app->user->logout();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [
            'success' => true,
            'message' => 'Logout',
        ];
    }
}
