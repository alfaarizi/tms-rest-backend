<?php

use yii\db\Migration;
use yii\db\Query;

/**
 * Fixes non-ascii characters in the file names of existing CodeChecker HTML reports,
 * as they were removed while extracting the tar file that was downloaded from Docker container
 */
class m230403_171248_fix_codechecker_html_filenames extends Migration
{
    private const BATCH_SIZE = 1000;

    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $query = (new Query())
            ->select(['plistFileName', 'resultID'])
            ->from('{{%codechecker_reports}}')
            ->distinct();

        $count = $query->count();

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            $reports = $query->limit(self::BATCH_SIZE)->all();

            foreach ($reports as $report) {
                $dir = Yii::getAlias("@appdata/codechecker_html_reports/") . $report['resultID'];
                $correctName = "{$report['plistFileName']}.html";
                $incorrectName = preg_replace('/[^[:print:]]/', '', $correctName);
                if ($correctName !== $incorrectName && is_file("$dir/$incorrectName")) {
                    rename("$dir/$incorrectName", "$dir/$correctName");
                    echo "[Result #{$report['resultID']}] Renamed $incorrectName to $correctName" . PHP_EOL;
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
        $query = (new Query())
            ->select(['plistFileName', 'resultID'])
            ->from('{{%codechecker_reports}}')
            ->distinct();

        $count = $query->count();

        for ($i = 0; $i <= $count; $i += self::BATCH_SIZE) {
            $reports = $query->limit(self::BATCH_SIZE)->all();

            foreach ($reports as $report) {
                $dir = Yii::getAlias("@appdata/codechecker_html_reports/") . $report['resultID'];
                $correctName = "{$report['plistFileName']}.html";
                $incorrectName = preg_replace('/[^[:print:]]/', '', $correctName);
                if ($correctName !== $incorrectName && is_file("$dir/$correctName")) {
                    rename("$dir/$correctName", "$dir/$incorrectName");
                    echo "[Result #{$report['resultID']}] Renamed $correctName to $incorrectName" . PHP_EOL;
                }
            }
        }
        return true;
    }
}
