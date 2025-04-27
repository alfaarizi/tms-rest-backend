<?php

namespace app\tests\unit;

use app\components\StructuralRequirementChecker;
use app\models\StructuralRequirements;
use Codeception\Test\Unit;

class StructuralRequirementCheckerTest extends Unit
{
    public function testValidatePaths()
    {
        $structuralRequirements = [
            new StructuralRequirements([
                'regexExpression' => '.*\.txt',
                'type' => StructuralRequirements::SUBMISSION_INCLUDES
            ]),
            new StructuralRequirements([
                'regexExpression' => '.*\.exe',
                'type' => StructuralRequirements::SUBMISSION_EXCLUDES
            ]),
        ];

        $paths = [
            'file2.exe',
            'file3.jpg',
        ];

        $result = StructuralRequirementChecker::validatePaths($structuralRequirements, $paths);

        $this->assertEquals(['file2.exe'], $result['failedExcludedPaths']);
        $this->assertEquals(['.*\.txt'], $result['failedIncludedRequirements']);
    }

    public function testValidatePathsNoError()
    {
        $structuralRequirements = [
            new StructuralRequirements([
                'regexExpression' => '.*\.txt',
                'type' => StructuralRequirements::SUBMISSION_INCLUDES
            ]),
            new StructuralRequirements([
                'regexExpression' => '.*\.exe',
                'type' => StructuralRequirements::SUBMISSION_EXCLUDES
            ]),
        ];

        $paths = [
            'file1.java',
            'file2.txt'
        ];

        $result = StructuralRequirementChecker::validatePaths($structuralRequirements, $paths);

        $this->assertEquals([], $result['failedExcludedPaths']);
        $this->assertEquals([], $result['failedIncludedRequirements']);
    }
}
