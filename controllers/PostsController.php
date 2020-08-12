<?php

namespace app\controllers;

use app\models\Comments;
use app\models\Posts;
use app\models\Tags;
use app\models\Users;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\ActiveController;
use yii\web\UploadedFile;

class PostsController extends ActiveController
{
    public function actionIndex()
    {
        return $this->render('index');
    }

    public $modelClass = 'app\models\Posts';

    public function behaviors()
    {
        $behaviors = parent::behaviors(); // TODO: Change the autogenerated stub
        $behaviors['authentication'] = [
            'class' => \yii\filters\auth\CompositeAuth::className(),
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
            'except' => ['create', 'update']
        ];

        $behaviors['corsFilter'] = [
            'class' => Cors::className(),
            'cors' => [
                'Origin' => ['*'],
            ],
        ];

        return $behaviors;
    }

    public function actions()
    {
        $actions = parent::actions(); // TODO: Change the autogenerated stub
        unset($actions['create']);
        unset($actions['update']);
        return $actions;
    }

    public function outputData($code, $text, $status, $message)
    {
        Yii::$app->response->setStatusCode($code, $text);
        if (!empty($message))
            return ['status' => $status, 'message' => $message];

        return ['status' => $status];
    }

    public function getPost($post, $comment_check)
    {
        $tags_array = [];

        if($tags = Tags::findAll(['post_id' => $post->id])){
            foreach ($tags as $tag)
                $tags_array[] = $tag->tag;
        }

        $post_array = [
            'title' => $post->title,
            'datatime' => $post->datatime,
            'anons' => $post->anons,
            'text' => $post->text,
            'tags' => $tags_array,
            'url_image' => $post->image
        ];

        if ($comment_check) {
            $comments_array = [];
            $comments = Comments::findAll(['post_id' => $post->id]);

            if (!empty($comments)) {
                foreach ($comments as $comment) {
                    $comment_array = [];
                    $comment_array[] = [
                        'comment_id' => $comment->id,
                        'datatime' => $comment->datatime,
                        'author' => $comment->author,
                        'comment' => $comment->comment
                    ];

                    $comments_array[] = $comment_array;
                }
            }

            $comment_array['comments'] = $comments_array;
        }

        return $post_array;
    }

    public function getToken()
    {
        $token = Yii::$app->response->headers->get('Authorization');
        return !empty($token) && explode(' ', $token)[1] !== '-1' ? explode(' ', $token)[1] : null;
    }

    public function actionCreate()
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            $input = Yii::input();
            $post = new Posts();

            $post->load($input, '');
            $post->user_id = $user->id;

            date_default_timezone_set("Europe/Moscow");
            $post->datatime = date("H:i m.d.y");

            $post->save();

            if (count($post->errors) === 1) {
                if ($image = UploadedFile::getInstanceByName('image')) {
                    $type = explode($image->type, '/')[1];
                    if (($type === 'jpg' || $type === 'png' || $type === 'jpeg') && $image->size < 2 * 1024 * 1024) {
                        if (!empty($input['tags'])) {
                            foreach (explode(',', $input['tags']) as $item) {
                                $tag = new Tags();
                                $tag->post_id = $post->id;
                                $tag->tag = $item;
                                $tag->save();
                            }
                        }

                        $image_name = md5(uniqid(rand(), true)) . '.' . $type;
                        $image->saveAs('uploads/' . $image_name);
                        $post->image = 'http://localhost/yii2-basic__RestApi/uploads/' . $image_name;
                        $post->save();

                        $post_array = $this->getPost($post, false);
                        return $this->outputData(201, 'Successful creation', true, $post_array);
                    }
                }
            }

            return $this->outputData(400, 'Creating error', false, $post->errors);
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }

    public function actionUpdate($id)
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            if($post = Posts::findOne(['id' => $id, 'user_id' => $user->id])){
                $input = Yii::input();
                $post->load($input, '');

                if (!empty($input['tags'])) {
                    $tags__all = Posts::findAll(['post_id' => $post->id]);
                    foreach ($tags__all as $item)
                        $item->delete();

                    foreach (explode(',', $input['tags']) as $item) {
                        $tag = new Tags();
                        $tag->post_id = $post->id;
                        $tag->tag = $item;
                        $tag->save();
                    }
                }

                $post->save();

                if(count($post->errors)){
                    return $this->outputData(400, 'Editing error', false, $post->errors);
                }else{
                    $post_array = $this->getPost($post, false);
                    return $this->outputData(201, 'Successful creation', true, $post_array);
                }
            }else{
                return $this->outputData(404, 'Post not found', false, 'Post not found');
            }
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }
}