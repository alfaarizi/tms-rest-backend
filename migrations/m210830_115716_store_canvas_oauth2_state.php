<?php

use yii\db\Migration;

/**
 * Class m210604_094916_store_canvas_oauth2_state
 */
class m210830_115716_store_canvas_oauth2_state extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('{{%access_tokens}}', 'canvasOAuth2State', $this->string());
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('{{%access_tokens}}', 'canvasOAuth2State');
    }
}
