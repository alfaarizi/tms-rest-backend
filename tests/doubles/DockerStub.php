<?php

namespace app\tests\doubles;

use Docker\API\Model\ContainersCreatePostBody;
use Docker\API\Model\ContainersCreatePostResponse201;
use Docker\API\Model\ContainersIdJsonGetResponse200;
use Docker\API\Model\ExecIdJsonGetResponse200;
use Docker\API\Model\IdResponse;
use Docker\API\Model\SystemInfo;
use Docker\Docker;
use Docker\Stream\DockerRawStream;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\Response;
use Jane\OpenApiRuntime\Client\Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Stub to get around docker API calls.
 * If you need complex mocking define a mock (eg: PHPUnit Mocks) and inject in the test set up with id 'Docker\Docker'
 */
class DockerStub extends Docker
{
    public $os;

    public function __construct($os)
    {
        $this->os = $os;
    }

    public int $createCount = 0;
    public ContainersCreatePostBody $createPostBody;
    public array $createQueryParams;
    public function containerCreate(ContainersCreatePostBody $body, array $queryParameters = [], string $fetch = Client::FETCH_OBJECT)
    {
        $this->createCount++;
        $this->createPostBody = $body;
        $this->createQueryParams = $queryParameters;

        $ret = new ContainersCreatePostResponse201();
        $ret->setId($queryParameters['name']);
        return $ret;
    }

    public int $stopCount = 0;
    public string $stopId;
    public array $stopQueryParams;
    public function containerStop(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        $this->stopCount++;
        $this->stopId = $id;
        $this->stopQueryParams = $queryParameters;
        return null;
    }

    public int $deleteCount = 0;
    public string $deleteId;
    public array $deleteQueryParams;
    public function containerDelete(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        $this->deleteCount++;
        $this->deleteId = $id;
        $this->deleteQueryParams = $queryParameters;
        return null;
    }

    public int $killCount = 0;
    public string $killId;
    public array $killQueryParams;
    public function containerKill(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        $this->killCount++;
        $this->killId = $id;
        $this->killQueryParams = $queryParameters;
        return null;
    }

    public int $startCount = 0;
    public string $startId;
    public array $startQueryParams;
    public function containerStart(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        $this->startCount++;
        $this->startId = $id;
        $this->startQueryParams = $queryParameters;
        return null;
    }

    public int $inspectCount = 0;
    public string $inspectId;
    public array $inspectQueryParams;
    public function containerInspect(string $id, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        $this->inspectCount++;
        $this->inspectId = $id;
        $this->inspectQueryParams = $queryParameters;

        $body = [];
        $body['Id'] = $id;
        return new Response(200, [], json_encode($body));
    }

    public int $execCount = 0;
    public string $execId;
    public \Docker\API\Model\ContainersIdExecPostBody $execConfig;
    public function containerExec(string $id, \Docker\API\Model\ContainersIdExecPostBody $execConfig, string $fetch = self::FETCH_OBJECT)
    {
        $this->execCount++;
        $this->execId = $id;
        $this->execConfig = $execConfig;

        $ret = new IdResponse();
        $ret->setId(1);
        return $ret;
    }

    public int $execStartCount = 0;
    public string $execStartId;
    public \Docker\API\Model\ExecIdStartPostBody $execStartConfig;
    public function execStart(string $id, \Docker\API\Model\ExecIdStartPostBody $execStartConfig, string $fetch = self::FETCH_OBJECT)
    {
        $this->execStartCount++;
        $this->execStartId = $id;
        $this->execStartConfig = $execStartConfig;

        //pump stream returns false --> eof immediately
        $pumpStream = new PumpStream(function () {
            return false;
        }, array());
        return new DockerRawStream($pumpStream);
    }

    public int $execInspectCount = 0;
    public string $execInspectId;
    public function execInspect(string $id, string $fetch = self::FETCH_OBJECT)
    {
        $this->execInspectCount++;
        $this->execInspectId = $id;

        $ret = new ExecIdJsonGetResponse200();
        $ret->setExitCode(0);
        return $ret;
    }

    public int $putCount = 0;
    public string $putId;
    public string $putInputStream;
    public array $putQueryParam;
    public function putContainerArchive(string $id, string $inputStream, array $queryParameters = [], string $fetch = self::FETCH_OBJECT)
    {
        $this->putCount++;
        $this->putId = $id;
        $this->putInputStream = $inputStream;
        $this->putQueryParam = $queryParameters;
        return null;
    }

    public int $infoCount = 0;
    public function systemInfo(string $fetch = self::FETCH_OBJECT)
    {
        $this->infoCount++;
        $model = new SystemInfo();
        $model->setOSType($this->os);
        return $model;
    }
}
