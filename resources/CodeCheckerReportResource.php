<?php

namespace app\resources;

use app\models\CodeCheckerReport;

class CodeCheckerReportResource extends CodeCheckerReport
{
    public function fields(): array
    {
        return [
            'id',
            'resultID',
            'reportHash',
            'filePath',
            'line',
            'column',
            'checkerName',
            'analyzerName',
            'severity',
            'translatedSeverity',
            'category',
            'message',
            'viewerLink',
        ];
    }

    public function extraFields(): array
    {
        return [];
    }
}
