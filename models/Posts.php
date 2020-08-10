<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "posts".
 *
 * @property int $id
 * @property string $title
 * @property string $anons
 * @property string $datatime
 * @property string $image
 * @property string $text
 * @property int $user_id
 */
class Posts extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'posts';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'anons', 'datatime', 'image', 'text', 'user_id'], 'required'],
            [['title', 'anons', 'datatime', 'image', 'text'], 'string'],
            [['user_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'anons' => 'Anons',
            'datatime' => 'Datatime',
            'image' => 'Image',
            'text' => 'Text',
            'user_id' => 'User ID',
        ];
    }
}
