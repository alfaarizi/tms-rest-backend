<?php

namespace app\tests\unit;

use app\models\PlagiarismBasefile;
use app\tests\unit\fixtures\CourseFixture;
use app\tests\unit\fixtures\GroupFixture;
use app\tests\unit\fixtures\UserFixture;
use UnitTester;

class PlagiarsimBasefileTest extends \Codeception\Test\Unit
{
    protected UnitTester $tester;

    public function _fixtures()
    {
        return [
            'courses' => [
                'class' => CourseFixture::class,
            ],
            'groups' => [
                'class' => GroupFixture::class,
            ],
            'users' => [
                'class' => UserFixture::class,
            ],
        ];
    }

    private function getCorrectModel(): PlagiarismBasefile
    {
        $baseFile = new PlagiarismBasefile();
        $baseFile->name = 'main.c';
        $baseFile->uploaderID = $this->tester->grabFixture('users', 'teacher1')->id;
        $baseFile->courseID = $this->tester->grabFixture('courses', 'course3')->id;
        return $baseFile;
    }

    public function testValidateWithoutParams()
    {
        $baseFile = new PlagiarismBasefile();
        $this->assertFalse($baseFile->validate(), 'PlagiarismBasefile created without parameters should not be valid.');
    }

    public function testValidateWithInvalidIds()
    {
        $idParams = ['uploaderID', 'courseID'];
        foreach ($idParams as $idParam) {
            $model = $this->getCorrectModel();
            $model->$idParam = -1;
            $this->assertFalse($model->validate(), "PlagiarismBasefile created with invalid $idParam should not be valid.");
        }
    }

    public function testValidateCorrectModel()
    {
        $baseFile = $this->getCorrectModel();
        $this->assertTrue($baseFile->validate(), 'PlagiarismBasefile created with correct parameters should be valid.');
    }

    public function testGetCourse()
    {
        $baseFile = $this->getCorrectModel();
        $this->assertEquals([$this->tester->grabFixture('courses', 'course3')], $baseFile->getCourse()->all());
    }

    public function testGetGroups()
    {
        $baseFile = $this->getCorrectModel();
        $expectedGroups = [
            $this->tester->grabFixture('groups', 'group2'),
            $this->tester->grabFixture('groups', 'group11'),
        ];
        $this->assertEquals($expectedGroups, $baseFile->getGroups()->all());
    }

    public function testGetUser()
    {
        $baseFile = $this->getCorrectModel();
        $this->assertEquals([$this->tester->grabFixture('users', 'teacher1')], $baseFile->getUser()->all());
    }

    public function testGetFilename()
    {
        $baseFile = $this->getCorrectModel();
        $baseFile->id = 1024;
        $this->assertEquals(
            \Yii::$app->basePath . '/' . \Yii::$app->params['data_dir'] . '/uploadedfiles/basefiles/1024',
            $baseFile->getPath()
        );
    }
}
