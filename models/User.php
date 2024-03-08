<?php

namespace app\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName()
    {
        return 'users';
    }

    public function rules()
    {
        return [
            [['email', 'password', 'first_name', 'last_name'], 'required'],
            ['email', 'email'],
            ['email', 'unique', 'targetClass' => self::class, 'message' => 'Этот email уже используется.'],
            ['password', 'string', 'min' => 3],
            ['password', 'match', 'pattern' => '/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d]{3,}/', 'message' => 'Пароль должен содержать минимум одну строчную букву, одну прописную букву и одну цифру.'],
            [['first_name', 'last_name'], 'string', 'max' => 255],
        ];
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord || $this->isAttributeChanged('password')) {
                $this->password = Yii::$app->security->generatePasswordHash($this->password);
            }
            return true;
        } else {
            return false;
        }
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['token' => $token]);
    }

    public static function findIdentity($id)
    {
        return static::findOne($id);
    }


    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    public static function  findByEmail($email){
        return static::findOne(['email' => $email]);
    }

    public function getId()
    {
        return $this->getPrimaryKey();
    }

    public function getAuthKey()
    {
        // Вам нужно будет добавить поддержку authKey, если она вам необходима
        throw new NotSupportedException('"getAuthKey" is not implemented.');
    }

    public function validateAuthKey($authKey)
    {
        // И здесь также, если authKey используется в вашем приложении
        throw new NotSupportedException('"validateAuthKey" is not implemented.');
    }

    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password);
    }

    public function getFiles()
    {
        return $this->hasMany(Files::className(), ['user_id' => 'id']);
    }
}
