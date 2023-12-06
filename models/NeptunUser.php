<?php

namespace app\models;

use Yii;
use app\models\UserIdentity;

/**
 * This class setup the user parameters after login.
 * Also provider properties about the user.
 */
class NeptunUser extends \yii\web\User
{
    public $locale;
    public $controller;
    public $semester;

    /**
     * Setup the user cookies after the login.
     * @param User $identity
     * @param bool $cookieBased
     * @param int $duration
     */
    protected function afterLogin($identity, $cookieBased, $duration)
    {
        $cookieModel = new CookieModel();

        if ($this->can('faculty')) {
            $cookieModel->set('controller', 'instructor');
        } else {
            $cookieModel->set('controller', 'student');
        }

        $cookieModel->set('locale', $identity->locale);

        parent::afterLogin($identity, $cookieBased, $duration);
    }
}
