<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAList;
use app\components\openapi\generators\OAItems;
use app\components\openapi\generators\OAProperty;
use app\components\openapi\IOpenApiFieldTypes;
use app\models\Model;
use app\models\Task;
use Yii;

class SetupWebAppExecutionResource extends Model implements IOpenApiFieldTypes
{
    public $studentFileID;
    public $runInterval;

    public function rules()
    {
        return [
            [['studentFileID'], 'required'],
            [['studentFileID'], 'integer'],
            [['runInterval'],
                'integer',
                'min' => 10,
                'max' => Yii::$app->params['evaluator']['webApp']['maxWebAppRunTime'],
                'tooBig' => Yii::t('app', "Web app execution time must be in range: {min} - {max} min.",
                                    [
                                        'min' => 10,
                                        'max' => Yii::$app->params['evaluator']['webApp']['maxWebAppRunTime'],
                                    ]
                ),
                'tooSmall' => Yii::t('app', "Web app execution time must be in range: {min} - {max} min.",
                                   [
                                       'min' => 10,
                                       'max' => Yii::$app->params['evaluator']['webApp']['maxWebAppRunTime'],
                                   ]
                ),
            ],
            [['runInterval'], 'default', 'value' => Yii::$app->params['evaluator']['webApp']['maxWebAppRunTime']],
        ];
    }

    public function fieldTypes(): array
    {
        return [
            'studentFileID' => new OAProperty(['type' => 'integer']),
            'runInterval' => new OAProperty(['type' => 'integer']),
        ];
    }

}
