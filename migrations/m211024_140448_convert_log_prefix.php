<?php

use app\models\User;
use yii\db\Query;
use yii\db\Migration;

/**
 * Converts log prefixes from [ip][userID][sessionID] format to the new [ip][username (neptun)] format
 */
class m211024_140448_convert_log_prefix extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // This is a workaround for MySQL batch query limitations
        // Docs: https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder
        Yii::$app->db->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        // Build a query to get all log messages
        $query = (new Query())
            ->select(['id', 'prefix'])
            ->from('{{%log}}');

        // Iterate through all messages, $query->each() uses batch query
        foreach ($query->each() as $row) {
            // Get all values between []
            preg_match_all('#\[(.*?)]#', $row['prefix'], $match);

            // Only modify messages with the old prefix format
            if (count($match[0]) === 3) {
                $ip = $match[1][0];
                $userID = $match[1][1];

                $identity = User::findOne(['id' => $userID]);
                $userString = !is_null($identity) ? "$identity->name ($identity->neptun)" : "-";

                $newPrefix = "[$ip][$userString]";

                $this->update('{{%log}}', ['prefix' => $newPrefix], ['id' => $row['id']]);
            }
        }

        Yii::$app->db->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // This is a workaround for MySQL batch query limitations
        // Docs: https://www.yiiframework.com/doc/guide/2.0/en/db-query-builder
        Yii::$app->db->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        // Build a query to get all log messages
        $query = (new Query())
            ->select(['id', 'prefix'])
            ->from('{{%log}}');

        // Iterate through all messages, $query->each() uses batch query
        foreach ($query->each() as $row) {
            // Get all values between []
            preg_match_all('#\[(.*?)]#', $row['prefix'], $match);

            // Only modify messages with the new prefix format
            if (count($match[0]) === 2) {
                $ip = $match[1][0];
                $userString = $match[1][1];

                preg_match('#\((.*?)\)#', $userString, $neptunMatch);

                $user = count($neptunMatch) == 2 ? User::findOne(['neptun' => $neptunMatch[1]]) : null;
                $userID = $user ? $user->id : "-";
                $newPrefix = "[$ip][$userID][-]";
                $this->update('{{%log}}', ['prefix' => $newPrefix], ['id' => $row['id']]);
            }
        }

        Yii::$app->db->pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

        return true;
    }
}
