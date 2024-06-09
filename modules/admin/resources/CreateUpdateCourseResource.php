<?php

namespace app\modules\admin\resources;

use app\models\Model;

class CreateUpdateCourseResource extends Model
{
    public string $name;
    public array $codes;

    public function rules()
    {
        return [
            [['name', 'codes'], 'required'],
            ['name', 'string'],
        ];
    }
}
