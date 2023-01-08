<?php

namespace app\components\plagiarism;

use app\models\JPlagPlagiarism;
use app\models\MossPlagiarism;
use app\models\Plagiarism;
use yii\base\InvalidConfigException;
use yii\di\Container;

class PlagiarismFinderFactory
{
    /**
     * Creates the suitable plagiarism finder for the given plagiarism check.
     * Intended to be used in the Yii2 DI container:
     * https://www.yiiframework.com/doc/guide/2.0/en/concept-di-container#php-callable-injection
     * @throws InvalidConfigException
     */
    public static function createForPlagiarism(Container $container, array $params): AbstractPlagiarismFinder
    {
        /** @var Plagiarism */
        $plagiarism = $params[0];
        switch ($plagiarism->type) {
            case MossPlagiarism::ID:
                return $container->get(MossPlagiarismFinder::class, [$plagiarism]);
            case JPlagPlagiarism::ID:
                return $container->get(JPlagPlagiarismFinder::class, [$plagiarism]);
            default:
                throw new InvalidConfigException("Plagiarism service {$plagiarism->type} has no configured finder!");
        }
    }
}
