<?php

namespace app\modules\student;

use yii\base\BootstrapInterface;

/**
 * student module definition class
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\student\controllers';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
    }

    public function bootstrap($app)
    {
        $app->getUrlManager()->addRules([
            "<module:{$this->id}>/<controller:(instructor-files|student-files)>/<id>/download" => '<module>/<controller>/download',

            "GET <module:{$this->id}>/<controller:(student-files)>/<id>/auto-tester-results" => '<module>/<controller>/auto-tester-results',

            "GET <module:{$this->id}>/<controller:(exam-test-instances)>/<id>/results" => '<module>/<controller>/results',
            "POST <module:{$this->id}>/<controller:(exam-test-instances)>/<id>/start-write" => '<module>/<controller>/start-write',
            "POST <module:{$this->id}>/<controller:(exam-test-instances)>/<id>/finish-write" => '<module>/<controller>/finish-write',
        ], false);
    }
}
