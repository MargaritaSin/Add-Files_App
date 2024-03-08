<?php

namespace app\models;

use yii\db\ActiveRecord;

class FileAccess extends ActiveRecord
{
    public static function tableName()
    {
        return 'file_access';
    }

    public function rules()
    {
        return [
            [['file_id', 'user_id', 'access_type'], 'required'],
            [['file_id', 'user_id'], 'integer'],
            ['access_type', 'string', 'max' => 50],
            [['file_id', 'user_id'], 'unique', 'targetAttribute' => ['file_id', 'user_id']],
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    public function getFile()
    {
        return $this->hasOne(Files::className(), ['id' => 'file_id']);
    }
}
