<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tags".
 *
 * @property int $id
 * @property string $tag
 * @property int $post_id
 */
class Tags extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tags';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tag', 'post_id'], 'required'],
            [['tag'], 'string'],
            [['post_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag' => 'Tag',
            'post_id' => 'Post ID',
        ];
    }
}
