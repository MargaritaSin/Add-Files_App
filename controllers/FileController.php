<?php

namespace app\controllers;

use app\models\FileAccess;
use Yii;
use yii\rest\ActiveController;
use app\models\User;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
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


class FileController extends ActiveController
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
                    'Access-Control-Request-Headers' => ['Content-Type', 'Authorization', 'X-Requested-With'], // Замена строки массивом
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Max-Age' => 3600, // предварительное разрешение кеширования на 1 час
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
                    'except' => ['options'], // исключение метода options из аутентификации
                ],
            ]);
    }

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete'], $actions['view'], $actions['index']);  // Отключаем стандартные действия
        return $actions;
    }

    public function actionFiles()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $model = new UploadForm();

        if (Yii::$app->request->isPost) {
            $model->files = UploadedFile::getInstances($model, 'files');
            $results = [];

            foreach ($model->files as $file) {
                $originalName = $file->baseName;
                $extension = $file->extension;
                $folder = Yii::getAlias('@webroot') . '/files/';
                $fileId = Yii::$app->security->generateRandomString(10);
                $userId = Yii::$app->user->id;


                $name = "{$originalName}.{$extension}";
                $counter = 0;

                while (Files::find()->where(['user_id' => $userId, 'name' => $name])->exists()) {
                    $name = "{$originalName} ({$counter}).{$extension}";
                    $counter++;
                }

                $path = $folder . $name;

                if ($file->saveAs($path)) {
                    $fileModel = new Files();
                    $fileModel->user_id = $userId;
                    $fileModel->name = $name;
                    $fileModel->file_id = $fileId;
                    $fileModel->path = $path;

                    if ($fileModel->save()) {

                        $access = new FileAccess();
                        $access->file_id = $fileModel -> id;
                        $access->user_id = $userId;
                        $access->access_type = 'author';

                        if (!$access->save()) {
                            Yii::error("Failed to save FileAccess for file {$fileId} and user {$userId}. Error: " . implode("; ", $access->getErrorSummary(true)));
                        }
                        $results[] = [
                            "success" => true,
                            "message" => "Success",
                            "name" => $name,
                            "url" => Yii::$app->request->hostInfo . '/files/' . $fileId,
                            "file_id" => $fileId
                        ];
                    } else {
                        // Сбой при сохранении информации о файле в БД
                        $results[] = [
                            "success" => false,
                            "message" => "Database error",
                            "name" => $name
                        ];
                    }
                } else {
                    // Сбой при сохранении файла на сервер
                    $results[] = [
                        "success" => false,
                        "message" => "File not saved",
                        "name" => $name
                    ];
                }
            }
            return $results;
        }
        throw new \yii\web\BadRequestHttpException('Bad request');


    }

    public function actionUpdateFile($file_id)
    {
        $user = Yii::$app->user->identity;
        $file = Files::findOne($file_id);

        if (!$file) {
            return $this->asJson([
                'message' => 'Not found',
            ])->setStatusCode(404);
        }

        if ($file->user_id !== $user->id) {
            return $this->asJson([
                'message' => 'Forbidden for you',
            ])->setStatusCode(403);
        }

        $bodyParams = Yii::$app->getRequest()->getBodyParams();

        if (isset($bodyParams['name']) && !empty($bodyParams['name'])) {
            $file->name = $bodyParams['name'];

            if ($file->validate() && $file->save()) {
                return [
                    'success' => true,
                    'message' => 'Renamed',
                ];
            } else {
                return $this->asJson([
                    'success' => false,
                    'message' => $file->getErrors(),
                ])->setStatusCode(422);
            }
        } else {
            return $this->asJson([
                'success' => false,
                'message' => ['name' => ['Name cannot be blank.']],
            ])->setStatusCode(422);
        }
    }

    public function actionDeleteFile($file_id)
    {
        $user = Yii::$app->user->identity;
        $file = Files::findOne($file_id);

        if (!$file) {
            throw new NotFoundHttpException("Not found");
        }

        if ($file->user_id !== $user->id) {
            throw new ForbiddenHttpException("Forbidden for you");
        }

        if ($file->delete()) {
            return [
                'success' => true,
                'message' => "File already deleted",
            ];
        } else {
            throw new \yii\web\ServerErrorHttpException('Internal server error');
        }
    }

    public function actionAddAccess($file_id)
    {
        $request = Yii::$app->request;
        $user = Yii::$app->user->identity;
        $file = Files::findOne($file_id);

        if (!$file || $file->user_id !== $user->id) {
            throw new \yii\web\ForbiddenHttpException("You don't have permission to manage this file");
        }

        $userEmail = $request->post('email');
        $userToAdd = User::findOne(['email' => $userEmail]);
        if (!$userToAdd) {
            throw new \yii\web\NotFoundHttpException("User not found");
        }

        $access = new FileAccess();
        $access->file_id = $file->id;
        $access->user_id = $userToAdd->id;
        $access->access_type = 'co-author';

        if ($access->save()) {
            $accesses = FileAccess::find()->where(['file_id' => $file_id])->all();
            $response = [];
            foreach ($accesses as $acc) {
                $user = $acc->user;
                $response[] = [
                    'fullname' => $user->first_name . " " . $user->last_name,
                    'email' => $user->email,
                    'type' => $acc->access_type,
                ];
            }
            return $response;
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to add access for user');
        }
    }

    public function actionRemoveAccess($file_id)
    {
        $request = Yii::$app->request;
        $user = Yii::$app->user->identity;
        $file = Files::findOne($file_id);

        if (!$file || $file->user_id !== $user->id) {
            throw new \yii\web\ForbiddenHttpException("You don't have permission to manage this file");
        }

        $userEmail = $request->post('email');
        $userToRemove = User::findOne(['email' => $userEmail]);

        if (!$userToRemove) {
            throw new \yii\web\NotFoundHttpException("User not found");
        }

        // Проверка попытки удаления самого себя
        if ($userToRemove->id === $user->id) {
            throw new \yii\web\ForbiddenHttpException("You cannot remove yourself");
        }

        $access = FileAccess::findOne(['file_id' => $file->id, 'user_id' => $userToRemove->id]);

        if (!$access) {
            throw new \yii\web\NotFoundHttpException("The specified user does not have access to this file");
        }

        if ($access->delete()) {
            $remainingAccesses = FileAccess::find()->where(['file_id' => $file_id])->all();
            $response = [];
            foreach ($remainingAccesses as $acc) {
                $accUser = $acc->user;
                $response[] = [
                    'fullname' => $accUser->first_name . " " . $accUser->last_name,
                    'email' => $accUser->email,
                    'type' => $acc->access_type,
                ];
            }
            return $response;
        } else {
            throw new \yii\web\ServerErrorHttpException('Failed to remove access for user');
        }
    }

    public function actionDisk()
    {
        $userId = Yii::$app->user->id;

        // Найдем все доступы пользователя, где он является автором
        $authorAccesses = FileAccess::find()
            ->joinWith(['file', 'user']) // Добавляем связь с файлом и пользователем
            ->where(['file_access.access_type' => 'author']) // Ищем только те, где тип доступа 'автор'
            ->andWhere(['file_access.user_id' => $userId]) // И где пользователь является текущим пользователем
            ->all();

        $response = [];
        foreach ($authorAccesses as $authorAccess) {
            $file = $authorAccess->file; // Обращаемся к файлу через связь

            $fileData = [
                'file_id' => $file->id,
                'name' => $file->name,
                'url' => Yii::$app->request->baseUrl . '/files/' . $file->id,
            ];

            // Поскольку нам нужны только файлы, где пользователь - автор, этот шаг можно опустить
            // и выводить информацию только об этих файлах.

            // Если тебе всё же нужно добавить информацию о доступах, то можно включить этот код снова.
            /* $accesses = $file->getAccesses()->with('user')->all();
            foreach ($accesses as $access) {
                $fileData['accesses'][] = [
                    'fullname' => $access->user->first_name . ' ' . $access->user->last_name,
                    'email' => $access->user->email,
                    'type' => $access->access_type,
                ];
            } */

            $response[] = $fileData;
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $response;
    }

    public function actionShared()
    {
        $userId = Yii::$app->user->id;
        $sharedFiles = FileAccess::find()
            ->joinWith('file') // Убедитесь, что связь 'file' определена в модели FileAccess
            ->where(['file_access.access_type' => 'co-author']) // Уточнение таблицы через table_name.column_name
            ->andWhere(['file_access.user_id' => $userId]) // Пользователь имеет к нему доступ как со-автор
            ->all();

        $response = [];
        foreach ($sharedFiles as $sharedFile) {
            $file = $sharedFile->file; // Доступ к связанной модели File через связь 'file'
            $response[] = [
                'file_id' => $file->id,
                'name' => $file->name,
                'url' => Yii::$app->request->baseUrl . '/files/' . $file->id,
            ];
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $response;
    }

    public function actionDownload($file_id)
    {
        // Проверка на авторизацию пользователя
        if (Yii::$app->user->isGuest) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            throw new \yii\web\HttpException(403, 'Login failed');
        }

        $user_id = Yii::$app->user->identity->id;
        $file = Files::findOne($file_id);

        if (!$file) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            throw new \yii\web\HttpException(404, 'Not found');
        }

        // Допустим, у модели File есть метод checkAccess, который проверяет, есть ли у пользователя доступ к файлу
        if (!$file->getAccesses()->where(['user_id' => $user_id])->one()) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            throw new \yii\web\HttpException(403, 'Forbidden for you');
        }

        // Предполагается, что у модели File есть атрибут path, указывающий путь до файла
        $pathToFile = $file->path;

        if (file_exists($pathToFile)) {
            return Yii::$app->response->sendFile($pathToFile, $file->name);
        } else {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            throw new \yii\web\HttpException(404, 'File not found on server');
        }
    }

    public function actionGetCoAuthors($file_id)
    {
        $file = Files::findOne($file_id);
        if ($file) {
            $coAuthorsAccesses = $file->getAccesses()->where(['access_type' => 'co-author'])->all();

            $coAuthors = [];
            foreach ($coAuthorsAccesses as $access) {
                $user = $access->user;
                if ($user) {
                    $coAuthors[] = [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email
                    ];
                }
            }

            return $coAuthors;
        }
        Yii::$app->response->statusCode = 404;
        return [
            'error' => 'File not found'
        ];
    }
}