<?php

namespace app\tests\unit;

use app\components\plagiarism\Moss;
use app\components\plagiarism\MossDownloader;
use app\components\plagiarism\MossPlagiarismFinder;

class TestableMossPlagiarismFinder extends MossPlagiarismFinder
{
    private \Codeception\Test\Unit $stub;
    /** @var string[] */
    private array $byWildcardArgs;
    /** @var string[] */
    private array $baseFileArgs;

    public function __construct(int $plagiarismId, \Codeception\Test\Unit $stub, array &$byWildcardArgs, array &$baseFileArgs)
    {
        $this->stub = $stub;
        parent::__construct($plagiarismId);
        $this->byWildcardArgs =& $byWildcardArgs;
        $this->baseFileArgs =& $baseFileArgs;
    }

    protected function getMoss(): Moss
    {
        return $this->stub->makeEmpty(Moss::class, [
            'getAllowedExtensions' => ['c'],
            'getExtensionLanguages' => ['c'],
            'getLanguageExtensions' => ['c'],
            'addByWildcard' => function ($path) {
                $this->byWildcardArgs[] = $path;
            },
            'addBaseFile' => function ($file) {
                $this->baseFileArgs[] = $file;
            },
        ]);
    }

    protected function getMossDownloader(): MossDownloader
    {
        return $this->stub->makeEmpty(MossDownloader::class);
    }
}
