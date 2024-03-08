<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $file_id
 * @property string $path
 * @property string $created_at
 * @property string $updated_at
 */
class Files extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'files';
    }

    public function getAccesses()
    {
        return $this->hasMany(FileAccess::className(), ['file_id' => 'id']);
    }
}


