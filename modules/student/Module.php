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
            "<module:{$this->id}>/<controller:(task-files|submissions)>/<id>/download" => '<module>/<controller>/download',
            "<module:{$this->id}>/<controller:(submissions)>/<id>/download-report" => '<module>/<controller>/download-report',

            "GET <module:{$this->id}>/<controller:(submissions)>/<id>/auto-tester-results" => '<module>/<controller>/auto-tester-results',

            "GET <module:{$this->id}>/<controller:(exam-test-instances)>/<id>/results" => '<module>/<controller>/results',
            "POST <module:{$this->id}>/<controller:(exam-test-instances)>/<id>/start-write" => '<module>/<controller>/start-write',
            "POST <module:{$this->id}>/<controller:(exam-test-instances)>/<id>/finish-write" => '<module>/<controller>/finish-write',

            "POST <module:{$this->id}>/<controller:(tasks)>/<id>/<action>" => '<module>/<controller>/<action>'
        ], false);
    }
}
