<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile[]
     */
    public $files;

    public function rules()
    {
        return [
            [['files'], 'file', 'skipOnEmpty' => false, 'extensions' => 'doc, pdf, docx, zip, jpeg, jpg, png', 'maxFiles' => 10, 'maxSize' => 2 * 1024 * 1024],
        ];
    }
}

