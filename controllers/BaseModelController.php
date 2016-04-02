<?php

namespace blumster\controllers;

use blumster\helpers\ModelUrl;

use Yii;

use yii\web\Controller;

/**
 * Base class for model based controllers.
 *
 * @author Blumster <blumster.yii2@gmail.com>
 */
abstract class BaseModelController extends Controller
{
    const LOAD_SKIP          = 0;
    const LOAD_OPTIONAL      = 1;

    const RELATION_WITH      = 0;
    const RELATION_JOIN_WITH = 1;

    const ERROR_NO_PARAMETER = 0;
    const ERROR_NOT_FOUND    = 1;

    /**
     * @var mixed
     */
    protected $modelId = null;

    /**
     * @var \yii\db\ActiveRecord|null
     */
    protected $model = null;

    /**
     * @var string
     */
    protected $idColumnName = 'id';

    /**
     * @var string
     */
    protected $paramName = 'id';

    /**
     * @inheritdoc
     */
    public function runAction($id, $params = [])
    {
        if (static::skipModelLoad($id) !== self::LOAD_SKIP && isset($params[$this->paramName])) {
            $this->modelId = $params[$this->paramName];
        }

        return parent::runAction($id, $params);
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        $parent = parent::beforeAction($action);
        if (!$parent) {
            return false;
        }

        $loadCase = static::skipModelLoad($action->id);
        if ($loadCase === self::LOAD_SKIP) {
            return true;
        }

        if (is_null($this->modelId)) {
            return $loadCase === self::LOAD_OPTIONAL || $this->handleError(self::ERROR_NO_PARAMETER);
        }

        $query = $this->modelLoadQuery($action->id)->andWhere([ $this->idColumnName => $this->modelId ]);

        $requiredWiths = static::requiredWiths();

        if (isset($requiredWiths[$action->id])) {
            foreach ($requiredWiths[$action->id] as $joinWith => $type) {
                if ($type === self::RELATION_JOIN_WITH) {
                    $query->joinWith($joinWith);
                } elseif ($type === self::RELATION_WITH) {
                    $query->with($joinWith);
                }
            }
        }

        $this->model = $query->one();
        if (is_null($this->model)) {
            return $this->handleError(self::ERROR_NOT_FOUND);
        }

        ModelUrl::setUp($this->paramName, $this->modelId);

        return $parent;
    }

    /**
     * Returns the necessary with parameters for each action.
     *
     * @return array
     */
    protected static function requiredWiths()
    {
        return [];
    }

    /**
     * Returns an array of actions, that doesn't need the model loaded.
     *
     * @return array
     */
    protected static function modelLoadExceptions()
    {
        return [];
    }

    /**
     * Checks, if the supplied action needs the model to be loaded.
     *
     * @param string $action the action's id
     * @return boolean|integer false, if the model should be loaded, CASE_SKIP, if it should be skipped and CASE_OPTIONAL, if loading the model should be optional
     */
    protected static function skipModelLoad($action)
    {
        $exceptions = static::modelLoadExceptions();

        if (array_key_exists($action, $exceptions)) {
            return $exceptions[$action];
        }

        return false;
    }

    /**
     * Handles a load error.
     *
     * @param integer $error the error's id
     * @return mixed string|\yii\web\Response
     */
    protected function handleError($error)
    {
        Yii::trace('BaseModelController - handleError: ' . $error);

        return false;
    }

    /**
     * Returns the ActiveQuery fore finding the model in the database.
     *
     * @param string $action the action's id
     * @return \yii\db\ActiveQuery
     */
    protected abstract function modelLoadQuery($action);
}
