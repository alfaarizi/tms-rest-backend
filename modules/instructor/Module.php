<?php

namespace app\modules\instructor;

use yii\base\BootstrapInterface;

/**
 * instructor module definition class
 */
class Module extends \yii\base\Module implements BootstrapInterface
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\instructor\controllers';

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
            // instructor/groups
            "<module:{$this->id}>/<controller:groups>/<id>/duplicate" => '<module>/<controller>/duplicate',
            "GET <module:{$this->id}>/<controller:groups>/<groupID>/instructors" => '<module>/<controller>/list-instructors',
            "POST <module:{$this->id}>/<controller:groups>/<groupID>/instructors" => '<module>/<controller>/add-instructors',
            "DELETE <module:{$this->id}>/<controller:groups>/<groupID>/instructors/<userID>" => '<module>/<controller>/delete-instructor',
            "GET <module:{$this->id}>/<controller:groups>/<groupID>/students" => '<module>/<controller>/list-students',
            "DELETE <module:{$this->id}>/<controller:groups>/<groupID>/students/<userID>" => '<module>/<controller>/delete-student',
            "PUT <module:{$this->id}>/<controller:groups>/<groupID>/students/<userID>/notes" => '<module>/<controller>/add-student-notes',
            "GET <module:{$this->id}>/<controller:groups>/<groupID>/students/<userID>/notes" => '<module>/<controller>/student-notes',
            "POST <module:{$this->id}>/<controller:groups>/<groupID>/students" => '<module>/<controller>/add-students',
            "GET <module:{$this->id}>/<controller:groups>/<groupID>/stats" => '<module>/<controller>/group-stats',
            "GET <module:{$this->id}>/<controller:groups>/<groupID>/students/<studentID>/stats" => '<module>/<controller>/student-stats',

            // instructor/tasks
            "PATCH <module:{$this->id}>/<controller:tasks>/<id>/toggle-auto-tester" => '<module>/<controller>/toggle-auto-tester',
            "POST <module:{$this->id}>/<controller:tasks>/<id>/setup-auto-tester" => '<module>/<controller>/setup-auto-tester',
            "GET <module:{$this->id}>/<controller:tasks>/<id>/tester-form-data" => '<module>/<controller>/tester-form-data',
            "PATCH <module:{$this->id}>/<controller:tasks>/<id>/update-docker-image" => '<module>/<controller>/update-docker-image',
            "POST <module:{$this->id}>/<controller:tasks>/<id>/setup-code-compass-parser" => '<module>/<controller>/setup-code-compass-parser',

            // instructor/tasks/{id}/evaluator
            "<module:{$this->id}>/tasks/<id>/<controller:evaluator>/<action>" => '<module>/<controller>/<action>',

            // instructor/plagiarism
            "POST <module:{$this->id}>/<controller:plagiarism>/<id>/run" => '<module>/<controller>/run',

            // instructor/plagiarism-basefile
            "GET <module:{$this->id}>/<controller:plagiarism-basefile>/<id>/download" => '<module>/<controller>/download',

            // instructor/student-files
            "GET <module:{$this->id}>/<controller:student-files>/<id>/download" => '<module>/<controller>/download',
            "POST <module:{$this->id}>/<controller:student-files>/<id>/start-code-compass" => '<module>/<controller>/start-code-compass',
            "POST <module:{$this->id}>/<controller:student-files>/<id>/stop-code-compass" => '<module>/<controller>/stop-code-compass',
            "GET <module:{$this->id}>/<controller:student-files>/<id>/download-report" => '<module>/<controller>/download-report',
            "GET <module:{$this->id}>/<controller:instructor-files>/<id>/download" => '<module>/<controller>/download',
            "GET <module:{$this->id}>/<controller:student-files>/<id>/auto-tester-results" => '<module>/<controller>/auto-tester-results',

            // instructor/exam-question-sets
            "<module:{$this->id}>/<controller:exam-question-sets>/<id>/duplicate" => '<module>/<controller>/duplicate',
            "GET <module:{$this->id}>/<controller:exam-question-sets>/<id>/images" => '<module>/<controller>/list-images',
            "POST <module:{$this->id}>/<controller:exam-question-sets>/<id>/images" => '<module>/<controller>/upload-images',
            "DELETE <module:{$this->id}>/<controller:exam-question-sets>/<id>/images/<filename>" => '<module>/<controller>/remove-image',

            // instructor/exam-tests
            "<module:{$this->id}>/<controller:exam-tests>/<id>/duplicate" => '<module>/<controller>/duplicate',
            "<module:{$this->id}>/<controller:exam-tests>/<id>/finalize" => '<module>/<controller>/finalize',

            // /instructor/web-app-execution
            "<module:{$this->id}>/<controller:web-app-execution>/<id>/download-run-log" => '<module>/<controller>/download-run-log',
        ], false);
    }
}
