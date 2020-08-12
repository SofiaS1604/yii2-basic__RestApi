<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "comments".
 *
 * @property int $id
 * @property string $author
 * @property string $comment
 * @property string $datatime
 * @property int $post_id
 */
class Comments extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'comments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['author', 'comment', 'datatime', 'post_id'], 'required'],
            [['author', 'comment', 'datatime'], 'string'],
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
            'author' => 'Author',
            'comment' => 'Comment',
            'datatime' => 'Datatime',
            'post_id' => 'Post ID',
        ];
    }
}
