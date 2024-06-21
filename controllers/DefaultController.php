<?php

namespace jcabanillas\comments\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\widgets\ActiveForm;
use jcabanillas\comments\events\CommentEvent;
use jcabanillas\comments\models\CommentModel;
use jcabanillas\comments\traits\ModuleTrait;
use jcabanillas\editable\EditableAction;

/**
 * Class DefaultController
 *
 * @package jcabanillas\comments\controllers
 */
class DefaultController extends Controller
{
    use ModuleTrait;

    /**
     * Event is triggered before creating a new comment.
     * Triggered with jcabanillas\comments\events\CommentEvent
     */
    const EVENT_BEFORE_CREATE = 'beforeCreate';

    /**
     * Event is triggered after creating a new comment.
     * Triggered with jcabanillas\comments\events\CommentEvent
     */
    const EVENT_AFTER_CREATE = 'afterCreate';

    /**
     * Event is triggered before deleting the comment.
     * Triggered with jcabanillas\comments\events\CommentEvent
     */
    const EVENT_BEFORE_DELETE = 'beforeDelete';

    /**
     * Event is triggered after deleting the comment.
     * Triggered with jcabanillas\comments\events\CommentEvent
     */
    const EVENT_AFTER_DELETE = 'afterDelete';

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'quick-edit' => [
                'class' => EditableAction::class,
                'modelClass' => CommentModel::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['quick-edit', 'delete'],
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'create' => ['post'],
                    'delete' => ['post', 'delete'],
                ],
            ],
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'only' => ['create'],
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Create a comment.
     *
     * @param $entity string encrypt entity
     *
     * @return array
     */
    public function actionCreate($entity)
    {
        /* @var $commentModel CommentModel */
        $commentModel = Yii::createObject($this->getModule()->commentModelClass);
        $event = Yii::createObject(['class' => CommentEvent::class, 'commentModel' => $commentModel]);
        $commentModel->setAttributes($this->getCommentAttributesFromEntity($entity));
        $this->trigger(self::EVENT_BEFORE_CREATE, $event);
        if ($commentModel->load(Yii::$app->request->post()) && $commentModel->saveComment()) {
            $this->trigger(self::EVENT_AFTER_CREATE, $event);

            return ['status' => 'success'];
        }

        return [
            'status' => 'error',
            'errors' => ActiveForm::validate($commentModel),
        ];
    }

    /**
     * Delete comment.
     *
     * @param int $id Comment ID
     *
     * @return string Comment text
     */
    public function actionDelete($id)
    {
        $commentModel = $this->findModel($id);
        $commentModel->setScenario(CommentModel::SCENARIO_MODERATION);
        $event = Yii::createObject(['class' => CommentEvent::class, 'commentModel' => $commentModel]);
        $this->trigger(self::EVENT_BEFORE_DELETE, $event);

        if ($commentModel->markRejected()) {
            $this->trigger(self::EVENT_AFTER_DELETE, $event);

            return Yii::t('jcabanillas.comments', 'Comment has been deleted.');
        } else {
            Yii::$app->response->setStatusCode(500);

            return Yii::t('jcabanillas.comments', 'Comment has not been deleted. Please try again!');
        }
    }

    /**
     * Find model by ID.
     *
     * @param int|array $id Comment ID
     *
     * @return CommentModel
     *
     * @throws NotFoundHttpException
     */
    protected function findModel($id)
    {
        $commentModel = $this->getModule()->commentModelClass;
        if (null !== ($model = $commentModel::findOne($id))) {
            return $model;
        } else {
            throw new NotFoundHttpException(Yii::t('jcabanillas.comments', 'The requested page does not exist.'));
        }
    }

    /**
     * Get list of attributes from encrypted entity
     *
     * @param $entity string encrypted entity
     *
     * @return array|mixed
     *
     * @throws BadRequestHttpException
     */
    protected function getCommentAttributesFromEntity($entity)
    {
        $decryptEntity = Yii::$app->getSecurity()->decryptByKey(mb_convert_encoding($entity, 'ISO-8859-1', 'UTF-8'), $this->getModule()->id);
        if (false !== $decryptEntity) {
            return Json::decode($decryptEntity);
        }

        throw new BadRequestHttpException(Yii::t('jcabanillas.comments', 'Oops, something went wrong. Please try again later.'));
    }
}
