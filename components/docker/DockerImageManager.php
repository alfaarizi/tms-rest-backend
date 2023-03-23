<?php

namespace app\components\docker;

use Docker\API\Model\BuildInfo;
use Docker\API\Model\ImageSummary;
use Docker\Context\Context;
use Docker\Docker;
use Docker\Stream\CallbackStream;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\di\NotInstantiableException;

class DockerImageManager extends BaseObject
{
    private Docker $docker;

    /**
     * Initializes a Docker Client to the specified OS instance.
     *
     * @param string $os
     * @param array $config
     * @throws InvalidConfigException if the Docker client to the OS not configured.
     * @throws NotInstantiableException
     */
    public function __construct(string $os, array $config = [])
    {
        parent::__construct($config);
        $this->docker = Yii::$container->get(Docker::class, ['os' => $os]);
    }

    /**
     * Checks if an image have already been built.
     *
     * @param string|null $imageName the name of the image.
     * @return bool
     */
    public function alreadyBuilt(?string $imageName): bool
    {
        if (empty($imageName)) {
            return false;
        }

        /* @var ImageSummary[] $images */
        $images = $this->docker->imageList();
        foreach ($images as $image) {
            $tags = $image->getRepoTags();
            if (!is_array($tags)) {
                continue;
            }
            foreach ($tags as $tag) {
                if (strcmp($imageName, $tag) === 0) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Downloads a Docker image
     *
     * @param string $imageName the name of the image
     */
    public function pullImage(string $imageName)
    {
        /** @var CallbackStream $createStream */
        $createStream = $this->docker->imageCreate('', [
            'fromImage' => $imageName,
        ]);

        $createStream->onFrame(function ($createImageInfo) use (&$firstMessage): void {
            if (null === $firstMessage) {
                $firstMessage = $createImageInfo->getStatus();
            }
        });
        $createStream->wait();
    }

    /**
     * Removes a Docker image
     *
     * @param string $imageName the name of the image
     */
    public function removeImage(string $imageName)
    {
        $this->docker->imageDelete($imageName);
    }

    /**
     * Fetches the image information from an image
     *
     * @param string $imageName name of the image
     * @return \Docker\API\Model\Image
     */
    public function inspectImage(string $imageName): \Docker\API\Model\Image
    {
        /** @var \Docker\API\Model\Image $imageInfo */
        return $this->docker->imageInspect($imageName);
    }

    /**
     * Checks if the image have already been built for the task, if not, builds it.
     *
     * @param string $taskName the name of the image
     * @param string $path the path to the Dockerfile
     *
     * @return array an associative array containing the success and log of the build
     */
    public function buildImageForTask(string $taskName, string $path): array
    {
        $buildLog = "";
        $buildResult = [];
        $buildResult['success'] = true;
        $buildResult['error'] = '';
        if (!$this->alreadyBuilt($taskName)) {
            $context = new Context($path);
            $inputStream = $context->toStream();
            /** @var \Docker\Stream\CallbackStream $buildStream */
            $buildStream = $this->docker->imageBuild($inputStream, ['t' => $taskName, 'nocache' => true]);
            $buildStream->onFrame(function (BuildInfo $buildInfo) use ($buildLog) {
                // Log the build info
                $buildLog .= $buildInfo->getStream();
                // if there were errors, return error
                if ($buildInfo->getError()) {
                    $buildResult['success'] = false;
                    $buildResult['error'] = $buildInfo->getError();
                }
            });
            $buildStream->wait();
        }
        $buildResult['log'] = $buildLog;
        return $buildResult;
    }
}
