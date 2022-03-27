<?php

namespace app\components\plagiarism;

abstract class AbstractPlagiarismFinder
{
    /** Numeric ID of the plagiarism check to execute */
    protected int $plagiarismId;

    /**
     * Constructor.
     * @param int $plagiarismId Numeric ID of the plagiarism check to execute
     */
    public function __construct(int $plagiarismId)
    {
        $this->plagiarismId = $plagiarismId;
    }

    protected function preProcess()
    {
        $this->setupTemporaryFiles();
    }

    abstract protected function findPlagiarisms();

    protected function postProcess()
    {
        $this->deleteTemporaryFiles();
    }

    abstract protected function setupTemporaryFiles();

    abstract protected function deleteTemporaryFiles();

    final public function start()
    {
        $this->preProcess();
        $this->findPlagiarisms();
        $this->postProcess();
    }
}
