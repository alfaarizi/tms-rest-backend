<?php

namespace app\commands;

use app\components\openapi\ConstantHelpers;
use app\components\openapi\SchemaGenerator;
use OpenApi\Generator;
use Yii;
use yii\console\Exception;
use yii\console\ExitCode;

/**
 * Provides a command-line interface for generating Open API documentation
 */
class OpenApiController extends BaseController
{
    /**
     * Generates Open API documentation then prints it to stdout
     * @param string $format output format (json|yaml)
     * @return int
     * @throws \yii\base\ErrorException
     * @throws Exception
     */
    public function actionGenerateDocs(string $format)
    {
        // Set constants
        ConstantHelpers::setApiInfo();
        ConstantHelpers::setServerInfo();

        // Generate schemas
        $schemaGenerator = Yii::$app->swagger;
        $schemaGenerator->clearOutputDir();
        $schemaGenerator->generateSchemas();

        // Generate OpenAPI documentation
        $scanDirs = array_map(function ($path) {
            return Yii::getAlias($path);
        }, ConstantHelpers::SCAN_DIRS);
        $openApi = Generator::scan($scanDirs);
        if ($format === 'json') {
            $result = $openApi->toJson();
        } else if ($format === 'yaml') {
            $result = $openApi->toYaml();
        } else {
            throw new Exception("Unsupported format: $format");
        }

        $this->stdout($result);

        return ExitCode::OK;
    }
}
