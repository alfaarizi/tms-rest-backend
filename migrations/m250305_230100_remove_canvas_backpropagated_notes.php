<?php

use yii\db\Migration;

/**
 * Removes Auto tester & Static analysis Canvas comments which were back-propagated into TMS.
 */
class m250305_230100_remove_canvas_backpropagated_notes extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        foreach (array_keys(Yii::$app->params['supportedLocale']) as $lang) {
            Yii::$app->language = $lang;
            $msg1 = Yii::t('app', 'TMS automatic tester result: ');
            $msg2 = Yii::t('app', 'TMS static code analyzer result: ');
            $this->update(
                '{{%submissions}}',
                ['notes' => ''],
                ['like', 'notes', $msg1 . '%', false] // the fourth parameter "false" disables automatic wildcards
            );
            $this->update(
                '{{%submissions}}',
                ['notes' => ''],
                ['like', 'notes', $msg2 . '%', false]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This migration updates incorrect DB entries, which can't be and shouldn't be reverted.
        return true;
    }
}
