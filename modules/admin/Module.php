<?php

namespace app\modules\admin;

use yii\base\BootstrapInterface;

/**
 * admin module definition class
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\admin\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        // custom initialization code goes here
    }

    public function bootstrap($app)
    {
        $app->getUrlManager()->addRules([
            // admin/lecturers
            "GET <module:{$this->id}>/<controller:courses>/<courseID>/lecturers" => '<module>/<controller>/list-lecturers',
            "POST <module:{$this->id}>/<controller:courses>/<courseID>/lecturers" => '<module>/<controller>/add-lecturers',
            "DELETE <module:{$this->id}>/<controller:courses>/<courseID>/lecturers/<userID>" => '<module>/<controller>/delete-lecturer'
        ], false);
    }
}
