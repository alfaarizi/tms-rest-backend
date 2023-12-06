<?php

namespace unit;

use app\models\CodeCompassInstance;
use app\models\StudentFile;
use app\tests\unit\fixtures\CodeCompassInstanceFixture;
use Codeception\Test\Unit;

class CodeCompassInstancesTest extends Unit
{
    public function _fixtures(): array
    {
        return [
            'codecompassinstances' => [
                'class' => CodeCompassInstanceFixture::class
            ]
        ];
    }

    public function testValidateWithoutParams()
    {
        $instance = new CodeCompassInstance();
        $this->assertFalse(
            $instance->validate(),
            "CodeCompass instance created without parameters should not be valid!"
        );
    }

    public function testValidateWithNotExistingStudentFile()
    {
        $instance = new CodeCompassInstance();
        $instance->studentFileId = 0;
        $instance->status = CodeCompassInstance::STATUS_RUNNING;
        $instance->instanceStarterUserId = 1;
        $this->assertFalse(
            $instance->validate(),
            "CodeCompass instance created with not existing student file should not be valid!"
        );
    }

    public function testValidateCorrectModel()
    {
        $instance = new CodeCompassInstance();
        $instance->studentFileId = 1;
        $instance->status = CodeCompassInstance::STATUS_RUNNING;
        $instance->instanceStarterUserId = 1;
        $this->assertTrue(
            $instance->validate(),
            "CodeCompass instance created without parameters should be valid!"
        );
    }

    public function testGetStudentFile()
    {
        $instance = CodeCompassInstance::findOne(['id' => 1]);
        /** @var null|StudentFile $studentFile */
        $studentFile = $instance->getStudentFile()->one();
        $this->assertNotNull($studentFile, 'The student file should not be null!');
        $this->assertEquals(1, $studentFile->id);
    }

    public function testFindRunningForStudentFileIdWithRunningInstance()
    {
        $instance = CodeCompassInstance::find()->findRunningForStudentFileId('1')->one();
        $this->assertNotNull($instance);
        $this->assertEquals(1, $instance->id);
    }

    public function testFindRunningForStudentFileIdWithStartingInstance()
    {
        $instance = CodeCompassInstance::find()->findRunningForStudentFileId('2')->one();

        $this->assertNull($instance);
    }

    public function testFindRunningForStudentFileIdWithNotExistingInstance()
    {
        $instance = CodeCompassInstance::find()->findRunningForStudentFileId('0')->one();

        $this->assertNull($instance);
    }

    public function testGetLongestWaitingCodeCompassInstance()
    {
        $instance = CodeCompassInstance::find()->listWaitingOrderedByCreationTime()->one();
        $this->assertEquals(5, $instance->id);

        CodeCompassInstance::deleteAll(['status' => CodeCompassInstance::STATUS_WAITING]);

        $instance = CodeCompassInstance::find()->listWaitingOrderedByCreationTime()->one();
        $this->assertNull($instance);
    }
}
