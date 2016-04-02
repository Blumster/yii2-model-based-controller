<?php

namespace blumster\helpers;

use yii\helpers\Url;

/**
 * Helper class to create URLs, with automatically adding the model id to the parameters.
 *
 * @author Blumster <blumster.yii2@gmail.com>
 */
class ModelUrl extends Url
{
    /**
     * @var mixed|null
     */
    private static $modelId = null;

    /**
     * @var string|null
     */
    private static $parameterName = null;

    /**
     * @inheritdoc
     */
    public static function to($url = '', $scheme = false)
    {
        if (is_array($url) && !isset($url[static::$parameterName]) && !is_null(static::$modelId)) {
            $url[static::$parameterName] = static::$modelId;
        }

        return parent::to($url, $scheme);
    }

    /**
     * Sets up the parameter name and the model id.
     *
     * @param string $parameterName the name of the url parameter
     * @param mixed $modelId the model's id
     */
    public static function setUp($parameterName, $modelId)
    {
        static::$parameterName = $parameterName;
        static::$modelId = $modelId;
    }
}
