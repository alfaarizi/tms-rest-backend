<?php

namespace app\models;

/**
 * his is the model class for table "logs".
 * @property integer $id
 * @property integer $level
 * @property string $category
 * @property double $log_time
 * @property string $prefix
 * @property string $message
 */
class Log extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%log}}';
    }
}
