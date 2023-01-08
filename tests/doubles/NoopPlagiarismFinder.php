<?php

namespace app\tests\doubles;

use app\components\plagiarism\AbstractPlagiarismFinder;
use app\exceptions\PlagiarismServiceException;

class NoopPlagiarismFinder extends AbstractPlagiarismFinder
{
    /** Whether to report the plagiarism finder to be enabled */
    public static bool $enabled = true;
    /** Whether to throw an exception during the plagiarism check */
    public static bool $fails = false;

    public static function isEnabled(): bool
    {
        return NoopPlagiarismFinder::$enabled;
    }

    protected function getExtensionLanguages(string $ext): ?array
    {
        return null;
    }

    protected function preProcess(): void
    {
        // noop
    }

    protected function findPlagiarisms(): void
    {
        if (NoopPlagiarismFinder::$fails) {
            throw new PlagiarismServiceException('You rang?');
        }
    }

    protected function postProcess(): void
    {
        // noop
    }
}
