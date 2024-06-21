<?php

namespace jcabanillas\comments\events;

use yii\base\Event;
use jcabanillas\comments\models\CommentModel;

/**
 * Class CommentEvent
 *
 * @package jcabanillas\comments\events
 */
class CommentEvent extends Event
{
    /**
     * @var CommentModel
     */
    private $_commentModel;

    /**
     * @return CommentModel
     */
    public function getCommentModel()
    {
        return $this->_commentModel;
    }

    /**
     * @param CommentModel $commentModel
     */
    public function setCommentModel(CommentModel $commentModel)
    {
        $this->_commentModel = $commentModel;
    }
}
