<?php

use app\components\docker\DockerImageManager;
use yii\base\InvalidConfigException;
use yii\db\Migration;
use yii\db\Query;
use yii\di\NotInstantiableException;

/**
 * Update existing tasks to avoid redundancy in the used images.
 * Pull new images.
 */
class m230324_001216_use_official_docker_images extends Migration
{
    private $imageMap = [
        [
            'from' => 'mcserep/elte:ubuntu-2004',
            'to' => 'tmselte/evaluator:gcc-ubuntu-20.04',
            'os' => 'linux',
        ],
        [
            'from' => 'mcserep/elte:ubuntu-2004-qt5',
            'to' => 'tmselte/evaluator:qt5-ubuntu-20.04',
            'os' => 'linux',
        ],
        [
            'from' => 'mcserep/elte:dotnet-60',
            'to' => 'tmselte/evaluator:dotnet-6.0',
            'os' => 'linux',
        ],
        [
            'from' => 'mcserep/elte:dotnet-60',
            'to' => 'tmselte/evaluator:dotnet-6.0',
            'os' => 'windows',
        ],
        [
            'from' => 'mcserep/elte:dotnet-60-maui',
            'to' => 'tmselte/evaluator:maui-6.0-windows',
            'os' => 'windows',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        foreach ($this->imageMap as $item) {
            $fromImage = $item['from'];
            $toImage = $item['to'];
            $os = $item['os'];

            $count = (new Query())
                ->select('id')
                ->from('{{%tasks}}')
                ->where(['imageName' => $fromImage])
                ->count();

            if ($count == 0) {
                continue;
            }

            $this->update('{{%tasks}}', ['imageName' => $toImage], ['imageName' => $fromImage, 'testOS' => $os]);

            if (!\Yii::$app->params['evaluator']['enabled'] || empty(\Yii::$app->params['evaluator'][$os])) {
                continue;
            }

            try {
                echo "Pulling image: $toImage" . PHP_EOL;
                $dockerImageManager = \Yii::$container->get(DockerImageManager::class, ['os' => $os]);
                $dockerImageManager->pullImage($toImage);
                echo "Pulled image: $toImage" . PHP_EOL;
            } catch (NotInstantiableException | InvalidConfigException $e) {
                echo "Unable to get DockerImageManager from the DI container: {$e->getMessage()}" . PHP_EOL;
                return false;
            } catch (\Exception $e) {
                echo "Unexpected error, failed to pull docker image: {$e->getMessage()}" . PHP_EOL;
                return false;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        foreach ($this->imageMap as $item) {
            $fromImage = $item['to'];
            $toImage = $item['from'];
            $os = $item['os'];

            $count = (new Query())
                ->select('id')
                ->from('{{%tasks}}')
                ->where(['imageName' => $fromImage])
                ->count();

            if ($count == 0) {
                continue;
            }

            $this->update('{{%tasks}}', ['imageName' => $toImage], ['imageName' => $fromImage, 'testOS' => $os]);

            if (!\Yii::$app->params['evaluator']['enabled'] || empty(\Yii::$app->params['evaluator'][$os])) {
                continue;
            }

            try {
                echo "Pulling image: $toImage" . PHP_EOL;
                $dockerImageManager = \Yii::$container->get(DockerImageManager::class, ['os' => $os]);
                $dockerImageManager->pullImage($toImage);
                echo "Pulled image: $toImage" . PHP_EOL;
            } catch (NotInstantiableException | InvalidConfigException $e) {
                echo "Unable to get DockerImageManager from the DI container: {$e->getMessage()}" . PHP_EOL;
                return false;
            } catch (\Exception $e) {
                echo "Unexpected error, failed to pull docker image: {$e->getMessage()}" . PHP_EOL;
                return false;
            }
        }
    }
}
