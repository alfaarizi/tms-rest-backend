<?php

namespace app\tests\unit;

use app\models\Group;
use app\tests\unit\fixtures\GroupFixture;

class GroupTest extends \Codeception\Test\Unit
{
    public function _fixtures()
    {
        return [
            'groups' => [
                'class' => GroupFixture::class
            ],
        ];
    }

    public function testCanvasURLIsNotCanvasGroup()
    {
        $group = Group::findOne(2000);
        $this->assertNull($group->canvasUrl);
    }

    public function testCanvasURLIsCanvasGroup()
    {
        $group = Group::findOne(2005);
        $this->assertEquals('https://canvas.example.com//courses/1', $group->canvasUrl);
    }
}
