<?php

namespace app\controllers;

use app\models\Comments;
use app\models\Posts;
use app\models\Users;
use Yii;
use yii\rest\ActiveController;

class CommentsController extends ActiveController
{
    public $modelClass = 'app\models\Comments';

    public function actions()
    {
        $actions = parent::actions(); // TODO: Change the autogenerated stub
        unset($actions['create']);
        unset($actions['delete']);
        return $actions;
    }

    public function getToken()
    {
        $token = Yii::$app->response->headers->get('Authorization');
        return !empty($token) && explode(' ', $token)[1] !== '-1' ? explode(' ', $token)[1] : null;
    }

    public function outputData($code, $text, $status, $message)
    {
        Yii::$app->response->setStatusCode($code, $text);
        if (!empty($message))
            return ['status' => $status, 'message' => $message];

        return ['status' => $status];
    }


    public function actionCreate($id)
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            if ($post = Posts::findOne(['id' => $id])) {
                $input = Yii::input();
                $comment = new Comments();
                $comment->post_id = $post->id;
                $comment->load($input, '');
                $comment->save();

                if (count($comment->errors)) {
                    return $this->outputData(400, 'Creating error', false, $comment->errors);
                } else {
                    return $this->outputData(201, 'Successful creation', true, null);
                }
            } else {
                return $this->outputData(404, 'Post not found', false, 'Post not found');
            }
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }

    public function actionDelete($post_id, $comment_id)
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            if ($comment = Comments::findOne(['id' => $comment_id, 'post_id' => $post_id])) {
                $comment->delete();
                return $this->outputData(201, 'Successful delete', true, null);
            } else {
                if ($post = Posts::findOne(['id' => $post_id])) {
                    return $this->outputData(404, 'Comment not found', false, 'Comment not found');
                }

                return $this->outputData(404, 'Post not found', false, 'Post not found');
            }
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }
}