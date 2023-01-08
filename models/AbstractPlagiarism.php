<?php

namespace app\models;

/**
 * This is the abstract model class for service-specific plagiarism data.
 *
 * @property-read string $type
 */
abstract class AbstractPlagiarism extends \yii\db\ActiveRecord
{
    abstract public function getType(): string;
}
