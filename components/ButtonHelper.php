<?php

namespace app\components;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;
use kartik\popover\PopoverX;
use kartik\dropdown\DropdownX;

/**
 * This class provides reuseable buttons for the TMS.
 */
class ButtonHelper
{
    /**
     * ToDo:
     *   -Implement function to remove code duplication.
     */
    public static function modalProviderButton($title, $icon, $action, $id)
    {
    }

    /**
     * Creates a delete button withe the given action and id.
     * @param string $action what will be called on click.
     * @param int $id is the id of the entity.
     */
    public static function deleteButton($action, $id)
    {
        $url = Url::to([$action, 'id' => $id]);
        return Html::beginTag('div', ['class' => 'text-right dropdown'])
            . Html::button(
                '<span class="glyphicon glyphicon-trash text-danger"></span></button>',
                ['type' => 'button', 'class' => 'btn btn-link btn-md', 'data-toggle' => 'dropdown']
            )
            . DropdownX::widget([
                'options' => ['class' => 'pull-right'],
                'items' => [
                    [
                        'label' => Yii::t('app', 'Proceed'),
                        'url' => $url
                    ]
                ]
            ])
            . Html::endTag('div');
    }
}
