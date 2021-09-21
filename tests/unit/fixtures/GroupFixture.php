<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class GroupFixture extends ActiveFixture
{
    public $modelClass = 'app\models\Group';
    public $dataFile =  __DIR__ . '/../../_data/groups.php';
    public $depends = [
        'app\tests\unit\fixtures\SemesterFixture',
        'app\tests\unit\fixtures\CourseFixture',
        'app\tests\unit\fixtures\UserFixture',
        'app\tests\unit\fixtures\InstructorGroupFixture',
        'app\tests\unit\fixtures\TaskFixture',
    ];
}
