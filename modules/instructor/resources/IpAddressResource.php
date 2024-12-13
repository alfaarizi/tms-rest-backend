<?php

namespace app\modules\instructor\resources;

use app\components\openapi\generators\OAProperty;
use app\models\IpAddress;
use yii\helpers\ArrayHelper;

class IpAddressResource extends \app\models\IpAddress
{
    public function fields()
    {
        return [
            'id',
            'activity',
            'translatedActivity',
            'logTime',
            'ipAddress',
        ];
    }

    public function extraFields()
    {
        return ['submission'];
    }

    public function fieldTypes(): array
    {
        return ArrayHelper::merge(
            parent::fieldTypes(),
            [
                'submission' => new OAProperty(
                    [
                        'ref' => '#/components/schemas/Instructor_SubmissionResource_Read'
                    ]
                )
            ]
        );
    }

    public function __construct(IpAddress $ipAddress = null)
    {
        if ($ipAddress == null) {
            parent::__construct();
        } else {
            $this->id = $ipAddress->id;
            $this->submissionID = $ipAddress->submissionID;
            $this->activity = $ipAddress->activity;
            $this->logTime = $ipAddress->logTime;
            $this->ipAddress = $ipAddress->ipAddress;
        }
    }

    public function getSubmission(): \yii\db\ActiveQuery
    {
        return $this->hasOne(SubmissionResource::class, ['id' => 'submissionID']);
    }
}
