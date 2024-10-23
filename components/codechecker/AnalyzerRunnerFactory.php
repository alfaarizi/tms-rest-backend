<?php

namespace app\components\codechecker;

use app\models\Submission;
use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\di\Container;

class AnalyzerRunnerFactory extends BaseObject
{
    /**
     * Creates the suitable analyzer runner for the given student file.
     * Intended to be used in the Yii2 DI container:
     * https://www.yiiframework.com/doc/guide/2.0/en/concept-di-container#php-callable-injection
     * @param Container $container
     * @param array{submission: Submission} $params
     * @param array $config
     * @return AnalyzerRunner
     * @throws InvalidConfigException Thrown if the provided analyzer for the given student file
     * is not supported by the current TMS instance.
     */
    public static function createForSubmission(Container $container, array $params, array $config): AnalyzerRunner
    {
        $submission = $params['submission'];
        $toolName = $submission->task->staticCodeAnalyzerTool;
        if ($toolName === 'codechecker') {
            return new CodeCheckerRunner($submission);
        } else if (array_key_exists($toolName, Yii::$app->params['evaluator']['supportedStaticAnalyzerTools'])) {
            return new ReportConverterRunner($submission);
        } else {
            throw new InvalidConfigException("Analyzer tool $toolName is not supported");
        }
    }
}
