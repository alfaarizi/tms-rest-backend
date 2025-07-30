<?php

namespace app\tests\unit;

use app\components\StructuralRequirementChecker;
use app\models\StructuralRequirement;
use Codeception\Test\Unit;

class StructuralRequirementCheckerTest extends Unit
{
    public function testValidatePaths()
    {
        $structuralRequirements = [
            new StructuralRequirement([
                'regexExpression' => '.*\.txt',
                'type' => StructuralRequirement::SUBMISSION_INCLUDES
            ]),
            new StructuralRequirement([
                'regexExpression' => '.*\.exe',
                'type' => StructuralRequirement::SUBMISSION_EXCLUDES
            ]),
        ];

        $paths = [
            'file2.exe',
            'file3.jpg',
        ];

        $result = StructuralRequirementChecker::validatePaths($structuralRequirements, $paths);

        $this->assertNotEmpty($result['includeErrors']);
        $this->assertNotEmpty($result['excludeErrors']);
    }

    public function testValidatePathsNoError()
    {
        $structuralRequirements = [
            new StructuralRequirement([
                'regexExpression' => '.*\.txt',
                'type' => StructuralRequirement::SUBMISSION_INCLUDES
            ]),
            new StructuralRequirement([
                'regexExpression' => '.*\.exe',
                'type' => StructuralRequirement::SUBMISSION_EXCLUDES
            ]),
        ];

        $paths = [
            'file1.java',
            'file2.txt'
        ];

        $result = StructuralRequirementChecker::validatePaths($structuralRequirements, $paths);

        $this->assertEmpty($result['includeErrors']);
        $this->assertEmpty($result['excludeErrors']);
    }
}
