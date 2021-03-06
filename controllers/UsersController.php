<?php

namespace app\controllers;

use app\models\Posts;
use app\models\Users;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\Cors;
use yii\rest\ActiveController;

class UsersController extends ActiveController
{
    public $modelClass = 'app\models\Users';

    public function behaviors()
    {
        $behaviors = parent::behaviors(); // TODO: Change the autogenerated stub
        $behaviors['authentication'] = [
            'class' => \yii\filters\auth\CompositeAuth::className(),
            'authMethods' => [
                HttpBearerAuth::className(),
            ],
            'except' => ['auth', 'create', 'index', 'logout', 'update', 'search']
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
        unset($actions['auth']);
        unset($actions['create']);
        unset($actions['index']);
        unset($actions['update']);
        unset($actions['logout']);
        unset($actions['search']);
        return $actions;
    }

    public function verbs()
    {
        $verbs = parent::verbs(); // TODO: Change the autogenerated stub
        $verbs['update'] = ['POST'];
        return $verbs;
    }

    public function outputData($code, $text, $status, $message)
    {
        Yii::$app->response->setStatusCode($code, $text);
        if (!empty($message))
            return ['status' => $status, 'message' => $message];

        return ['status' => $status];
    }

    public function getUser($user, $post_check)
    {
        $user_array = [
            'id' => $user->id,
            'login' => $user->login,
            'surname' => $user->surname,
            'first_name' => $user->first_name,
            'phone' => $user->phone,
            'email' => $user->email
        ];

        if ($post_check) {
            $posts_id = [];
            $posts = Posts::findAll(['user_id' => $user->id]);

            if (!empty($posts)) {
                foreach ($posts as $post)
                    $posts_id[] = $post->id;
            }

            $user_array['posts_id'] = $posts_id;
        }

        return $user_array;
    }

    public function getToken()
    {
        $token = Yii::$app->request->headers->get('Authorization');
        return !empty($token) && explode(' ', $token)[1] !== '-1' ? explode(' ', $token)[1] : null;
    }

    public function actionCreate()
    {
        $input = Yii::input();
        $user = new Users();

        $user->load($input, '');
        $user->token = '-1';
        $user->password = md5($user->password);
        $user->save();

        if (count($user->errors)) {
            return $this->outputData(401, 'Unprocessable entity', false, $user->errors);
        } else {
            $user_array = $this->getUser($user, false);
            return $this->outputData(201, 'Created account', true, $user_array);
        }
    }

    public function actionAuth()
    {
        $error = [];
        $input = Yii::input();

        if (empty($input['login']) || empty($input['password'])) {
            empty($input['login']) ? $error['login'] = 'Path `login` is required.' : null;
            empty($input['password']) ? $error['password'] = 'Path `password` is required.' : null;

            return $this->outputData(401, ' Invalid authorization data', false, $error);
        } else {
            if ($user = Users::findOne(['login' => $input['login'], 'password' => md5($input['password'])])) {
                $user->token = Yii::$app->security->generateRandomString();
                $user->save();

                return $this->outputData(200, 'Successful authorization', true, ['token' => $user->token]);
            } else {
                return $this->outputData(401, 'Invalid authorization data', false, 'Incorrect login or password');
            }
        }
    }

    public function actionIndex()
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            $user_array = $this->getUser($user, true);
            return $this->outputData(200, 'OK', true, $user_array);
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }

    public function actionLogout()
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            $user->token = '-1';
            return $this->outputData(200, 'OK', true, null);
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }

    public function actionUpdate()
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            $input = Yii::input();
            $user->load($input, '');
            $user->save();

            if (count($user->errors)) {
                return $this->outputData(401, 'Unprocessable entity', false, $user->errors);
            } else {
                $user_array = $this->getUser($user, true);
                return $this->outputData(200, 'Update', true, $user_array);
            }
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }

    public function actionSearch($input)
    {
        if ($user = Users::findIdentityByAccessToken($this->getToken())) {
            $exploded = explode(' ', $input);
            $users = [];

            if (count($exploded) === 1) {
                $users = Users::find()->where(['like', 'first_name', $input[0]])->all();
                $users = array_merge($users, Users::find()->where(['like', 'surname', $input[0]])->all());
                $users = array_merge($users, Users::find()->where(['like', 'phone', $input[0]])->all());
            } else if (count($exploded) === 2) {
                $users = Users::find()->where(['like', 'first_name', $input[0]])->andWhere(['like', 'surname', $input[1]])->all();
                $users = array_merge($users, Users::find()->where(['like', 'surname', $input[0]])->andWhere(['like', 'phone', $input[1]])->all());
                $users = array_merge($users, Users::find()->where(['like', 'first_name', $input[0]])->andWhere(['like', 'phone', $input[1]])->all());
            } else if (count($exploded) === 2)
                $users = Users::find()->where(['like', 'first_name', $input[0]])
                    ->andWhere(['like', 'surname', $input[1]])->andWhere(['like', 'phone', $input[2]])->all();

            $users_array = [];
            if (!empty($users)) {
                foreach ($users as $user)
                    $users_array[] = $this->getUser($user, true);
            }

            return $this->outputData(200, 'Found users', true, $users_array);
        } else {
            return $this->outputData(401, 'Unauthorized', false, 'Unauthorized');
        }
    }
}
