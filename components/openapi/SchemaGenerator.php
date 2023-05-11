<?php

namespace app\components\openapi;

use app\components\openapi\generators\OASchema;
use Throwable;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\Model;
use Yii;
use yii\helpers\Console;
use yii\helpers\FileHelper;

/**
 * Generates OA\Schema files for zircote/swagger-php for model and resource classes
 */
class SchemaGenerator extends Component
{
    public string $outputDir;
    public array $namespaces;

    public function __construct(string $outputDir = '', array $namespaces = [], $config = [])
    {
        $this->outputDir = $outputDir;
        $this->namespaces = $namespaces;
        parent::__construct($config);
    }

    public function init()
    {
        parent::init();

        if (empty(strlen($this->outputDir))) {
            throw new InvalidConfigException('SchemaGenerator::outputDir cannot be empty.');
        }

        if (strlen($this->outputDir) > 0 && $this->outputDir[0] == '@') {
            $this->outputDir = Yii::getAlias($this->outputDir);
        }
    }

    /**
     * Deletes the content of the output directory
     * @throws \yii\base\ErrorException
     */
    public function clearOutputDir(): void
    {
        if (file_exists($this->outputDir)) {
            FileHelper::removeDirectory($this->outputDir);
            FileHelper::createDirectory($this->outputDir, 0755, true);
        }
    }

    /**
     * Reads config from Yii params and generates schemas
     */
    public function generateSchemas()
    {
        foreach (array_keys($this->namespaces) as $prefix) {
            $this->generateForNamespace($prefix, $this->namespaces[$prefix]);
        }
    }

    /**
     * Generates annotated php classes from the classes in the given namespace
     * @param string $prefix Adds a prefix to the generated classnames. Generated classes from the same module should have the same prefix
     * @param string $namespace The namespace where the classes are placed
     */
    private function generateForNamespace(string $prefix, string $namespace): void
    {
        $alias = '@' . str_replace(['\\'], ['/'], "$namespace");
        $files = glob(Yii::getAlias($alias) . "/*.php");
        foreach ($files as $file) {
            $this->generateForModel($prefix, $namespace, basename($file, '.php'));
        }
    }

    /**
     * Generates OA\Schema from the given model class
     * @param string $prefix Adds a prefix to the generated classnames.
     * @param string $namespace The namespace where the classes are placed
     * @param string $className Short class name
     */
    private function generateForModel(string $prefix, string $namespace, string $className): void
    {
        $class = $namespace . '\\' . $className;
        if (!class_exists($class)) {
            return;
        }

        try {
            $model = new $class();
        } catch (Throwable $e) {
            if ($this->fromConsole()) {
                Console::stderr("$class skipped: cannot create a new instance from this class" . PHP_EOL);
            }
            return;
        }
        if (!($model instanceof Model)) {
            if ($this->fromConsole()) {
                Console::stderr("$class skipped: it does not extends Model" . PHP_EOL);
            }
            return;
        }

        if (!($model instanceof IOpenApiFieldTypes)) {
            if ($this->fromConsole()) {
                Console::stderr("$class skipped: it does not implement IOpenApiFieldTypes" . PHP_EOL);
            }
            return;
        }

        if (!file_exists($this->outputDir)) {
            FileHelper::createDirectory($this->outputDir, 0755, true);
        }

        // Generate Read schema
        $out = new OASchema(
            "{$prefix}_{$className}_Read",
            $model->fields(),
            $model->fieldTypes(),
            $model->extraFields(),
            [],
        );
        file_put_contents($this->outputDir . "{$prefix}_{$className}_Read.php", $out->getCode());

        // Generate schemas for each scenario
        foreach (array_keys($model->scenarios()) as $scenario) {
            $model->scenario = $scenario;
            $name = "{$prefix}_{$className}_" . "Scenario" .  ucfirst($scenario);
            $out = new OASchema(
                $name,
                $model->safeAttributes(),
                $model->fieldTypes(),
                [],
                $model->activeValidators
            );
            file_put_contents($this->outputDir . "{$name}.php", $out->getCode());
        }
    }

    /**
     * Check if a function is called from a console command
     * @return bool
     */
    private function fromConsole(): bool
    {
        return Yii::$app instanceof \yii\console\Application;
    }
}
