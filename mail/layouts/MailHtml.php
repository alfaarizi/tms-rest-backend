<?php

namespace app\mail\layouts;

/*
* This class contains methods to create components of mail content
*/
class MailHtml
{
    public static function p(
        string $text,
        array $options = []
    ): string {
        $defaultOptions = [
            'marginTop' => '8px',
            'marginBottom' => '8px',
        ];

        $options = array_merge($defaultOptions, $options);

        return "
            <div
                style=\"
                margin: {$options['marginTop']} 0 {$options['marginBottom']} 0;
                background-color: #ffffff;
                width: 100%;\"
            >
                <p
                    style=\"
                    margin: 0;
                    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                    font-size: 15px;
                    line-height: 23px;
                    text-align: center;
                    color: #333333;\"
                >
                    {$text}
                </p>
            </div>
        ";
    }

    public static function table(
        array $rows,
        array $cols = [],
        array $options = []
    ): string {
        $defaultOptions = [
            'marginTop' => '8px',
            'marginBottom' => '8px',
            'textAlign' => 'center',
            'headerText' => '',
            'headerBackgroundColor' => '#ffffff',
            'headerColor' => '#8C8C8C',
        ];

        $options = array_merge($defaultOptions, $options);

        $table = "
            <table
                cellpadding=\"0\"
                cellspacing=\"0\"
                width=\"100%\"
                style=\"
                margin: {$options['marginTop']} 0 {$options['marginBottom']} 0;
                border-collapse: collapse;
                border: 1px solid #ededed;\"
            >
                <tbody>
        ";

        // Header
        if ($options['headerText'] !== '') {
            $table .= "
                <tr>
                    <td
                        colspan=\"2\"
                        style=\"
                        padding: 6px 16px;
                        border-bottom: 1px solid #ededed;
                        background-color: {$options['headerBackgroundColor']};
                        vertical-align: middle\"
                    >
                        <p
                            style=\"
                            margin: 0;
                            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                            font-size: 15px;
                            font-weight: 300;
                            text-align: center;
                            color: {$options['headerColor']};\"
                        >
                            {$options['headerText']}
                        </p>
                    </td>
                </tr>
            ";
        }

        $hasCols = count($cols) > 0;
        for ($i = 0; $i < count($rows); $i++) {
            // Divider
            if ($i > 0) {
                $table .= "
                    <tr>
                        <td style=\"text-align: center;\">
                            <div
                                style=\"
                                margin: 0 auto;
                                width: 95%;
                                height: 1px;
                                box-shadow: 0 1px 0 0 #ededed;\"
                            >
                            </div>
                        </td>
                    </tr>
                ";
            }

            if ($hasCols) {
                $col = $cols[$i] ?? '';
                $table .= "
                    <tr>
                        <th
                            scope=\"row\"
                            style=\"
                            width: 25%;
                            padding: 14px;
                            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                            font-size: 15px;
                            font-weight: 300;
                            color: #8C8C8C;
                            text-align: left;\"
                        >
                            {$col}
                        </th>
                        <td
                            style=\"
                            width: 75%;
                            padding: 14px;
                            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                            font-size: 15px;
                            color: #333333;\"
                        >
                            $rows[$i]
                        </td>
                    </tr>
                ";
            } else {
                $table .= "
                    <tr>
                        <td
                            colspan=\"2\"
                            style=\";
                            padding: 14px;
                            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                            font-size: 15px;
                            color: #333333;
                            text-align: {$options['textAlign']};\"
                        >
                            $rows[$i]
                        </td>
                    </tr>
                ";
            }
        }

        $table .= "
            </tbody>
        </table>
        ";

        return $table;
    }
}
