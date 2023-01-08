<?php

namespace app\tests\unit;

use app\components\plagiarism\JPlagPlagiarismFinder;

class TestableJPlagPlagiarismFinder extends JPlagPlagiarismFinder
{
    public string $command;

    protected function findPlagiarisms(): void
    {
        $this->command = $this->getCommand();
        // Skip actually running JPlag
    }
}
