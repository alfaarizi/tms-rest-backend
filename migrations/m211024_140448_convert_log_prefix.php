<?php

use yii\db\Query;
use yii\db\Migration;

/**
 * Converts log prefixes from [ip][userID][sessionID] format to the new [ip][username (neptun)] format
 */
class m211024_140448_convert_log_prefix extends Migration
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Count all messages
        $count = (new Query())
            ->select(['id'])
            ->from('{{%log}}')
            ->count();

        // Build a query to get log messages
        $query = (new Query())
            ->select(['id', 'prefix'])
            ->from('{{%log}}')
            ->orderBy(['id' => SORT_ASC]);

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            // Get the current batch
            $rows = $query
                ->offset($i)
                ->limit(self::BATCH_SIZE)
                ->all();

            // Iterate through all messages, $query->each() uses batch query
            foreach ($rows as $row) {
                // Get all values between []
                preg_match_all('#\[(.*?)]#', $row['prefix'], $match);

                // Only modify messages with the old prefix format
                if (count($match[0]) === 3) {
                    $ip = $match[1][0];
                    $userID = $match[1][1];

                    $identity = (new Query())
                        ->select(['name', 'neptun'])
                        ->from('{{%users}}')
                        ->where(['id' => $userID])
                        ->one();

                    if ($identity !== false) {
                        $name = $identity['name'];
                        $neptun = $identity['neptun'];
                        $userString = "$name ($neptun)";
                    } else {
                        $userString = "-";
                    }

                    $newPrefix = "[$ip][$userString]";

                    $this->update('{{%log}}', ['prefix' => $newPrefix], ['id' => $row['id']]);
                }
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Count all messages
        $count = (new Query())
            ->select(['id'])
            ->from('{{%log}}')
            ->count();

        // Build a query to get log messages
        $query = (new Query())
            ->select(['id', 'prefix'])
            ->from('{{%log}}')
            ->orderBy(['id' => SORT_ASC]);

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            // Get the current batch
            $rows = $query
                ->offset($i)
                ->limit(self::BATCH_SIZE)
                ->all();

            // Iterate through all messages, $query->each() uses batch query
            foreach ($rows as $row) {
                // Get all values between []
                preg_match_all('#\[(.*?)]#', $row['prefix'], $match);

                // Only modify messages with the new prefix format
                if (count($match[0]) === 2) {
                    $ip = $match[1][0];
                    $userString = $match[1][1];

                    preg_match('#\((.*?)\)#', $userString, $neptunMatch);

                    $user = count($neptunMatch) == 2 ?
                        (new Query())
                            ->select('id')
                            ->from('{{%users}}')
                            ->where(['neptun' => $neptunMatch[1]])
                            ->one()
                        : null;
                    $userID = $user ? $user['id'] : "-";
                    $newPrefix = "[$ip][$userID][-]";
                    $this->update('{{%log}}', ['prefix' => $newPrefix], ['id' => $row['id']]);
                }
            }
        }

        return true;
    }
}
