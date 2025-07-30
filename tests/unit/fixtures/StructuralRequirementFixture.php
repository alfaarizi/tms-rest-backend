<?php

namespace app\tests\unit\fixtures;

use yii\test\ActiveFixture;

class StructuralRequirementFixture extends ActiveFixture
{
    public $modelClass = 'app\models\StructuralRequirement';
    public $dataFile =  __DIR__ . '/../../_data/structuralrequirements.php';
    public $depends = [
        'app\tests\unit\fixtures\TaskFixture',
        'app\tests\unit\fixtures\UserFixture'
    ];
}
