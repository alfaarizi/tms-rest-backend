<?php

namespace app\models;

use yii\web\Cookie;

/**
 * Simple cookie manager class.
 */
class CookieModel
{
    /**
     * Reads a cookie for the user, or returns the default value if no cookie defined.
     */
    public function get($cookie, $defaultValue)
    {
        return isset(\Yii::$app->request->cookies[$cookie]) ?
            \Yii::$app->request->cookies[$cookie]->value : $defaultValue;
    }

    /**
     *  Creates a new cookie or overwrites an existing one.
     */
    public function set($cookie, $value)
    {
        $cookie = new Cookie([
            'name' => $cookie,
            'value' => $value,
            //'expire' => time() + 86400 * 365,
        ]);
        \Yii::$app->response->cookies->add($cookie);
    }

    /**
     * Checks the existence of a cookie.
     */
    public function check($cookie)
    {
        return isset(\Yii::$app->request->cookies[$cookie]);
    }
}
