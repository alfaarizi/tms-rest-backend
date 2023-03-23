<?php

namespace app\exceptions;

class CodeCheckerRunnerException extends \Exception
{
    public const BEFORE_RUN_FAILURE = 0;
    public const PREPARE_FAILURE = 1;
    public const ANALYZE_FAILURE = 2;
    public const PARSE_FAILURE = 3;

    private ?int $exitCode;
    private ?string $stdout;
    private ?string $stderr;

    public function __construct($message = "", $code = 0, array $execResult = null, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        if (!empty($execResult)) {
            $this->exitCode = $execResult["exitCode"];
            $this->stdout = $execResult["stdout"];
            $this->stderr = $execResult["stderr"];
        }
    }

    /**
     * @return int|null
     */
    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    /**
     * @return string|null
     */
    public function getStdout(): ?string
    {
        return $this->stdout;
    }

    /**
     * @return string|null
     */
    public function getStderr(): ?string
    {
        return $this->stderr;
    }
}
