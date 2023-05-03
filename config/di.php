<?php

use Docker\Docker;
use Docker\DockerClientFactory;
use yii\di\Container;

return [
    'definitions' => [
        \Docker\Docker::class => fn (Container $container, array $params, array $config)
            => Docker::create(
                DockerClientFactory::create(
                    [
                        'remote_socket' => Yii::$app->params['evaluator'][$params['os']]
                    ]
                )
            ),
        \app\components\CanvasIntegration::class => \app\components\CanvasIntegration::class,
        \app\components\SubmissionRunner::class => \app\components\SubmissionRunner::class,
        \app\components\codechecker\AnalyzerRunner::class => [\app\components\codechecker\AnalyzerRunnerFactory::class, 'createForStudentFile'],
        \app\components\codechecker\CodeCheckerResultPersistence::class => \app\components\codechecker\CodeCheckerResultPersistence::class,
        \app\components\codechecker\CodeCheckerResultNotifier::class => \app\components\codechecker\CodeCheckerResultNotifier::class,
        \app\components\docker\DockerImageManager::class => \app\components\docker\DockerImageManager::class,
        \app\components\plagiarism\AbstractPlagiarismFinder::class => [\app\components\plagiarism\PlagiarismFinderFactory::class, 'createForPlagiarism'],
        \app\components\plagiarism\JPlagPlagiarismFinder::class => \app\components\plagiarism\JPlagPlagiarismFinder::class,
        \app\components\plagiarism\Moss::class => fn () => new \app\components\plagiarism\Moss(Yii::$app->params['mossId']),
        \app\components\plagiarism\MossDownloader::class => \app\components\plagiarism\MossDownloader::class,
        \app\components\plagiarism\MossPlagiarismFinder::class => \app\components\plagiarism\MossPlagiarismFinder::class,
    ],
];
