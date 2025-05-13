<?php

namespace app\mail\layouts;

/**
* This class contains methods to create standardized HTML email components
*/
class MailHtml
{
    private const FONT_FAMILY   = "'Helvetica Neue', Helvetica, Arial, sans-serif";
    private const FONT_SIZE     = "15px";
    private const LINE_HEIGHT   = "23px";
    private const TEXT_COLOR    = "#333333";
    private const MUTED_COLOR   = "#8C8C8C";
    private const BORDER_COLOR  = "#ededed";

    /**
     * Creates a styled paragraph component
     * @param string $text The paragraph content
     * @param array $options Styling options
     * @return string String HTML for the paragraph
     */
    public static function p(string $text, array $options = []): string
    {
        $options = array_merge([
           'marginTop' => '8px',
           'marginBottom' => '8px',
           'textAlign' => 'center',
        ], $options);

        $fontFamily = self::FONT_FAMILY;
        $fontSize = self::FONT_SIZE;
        $lineHeight = self::LINE_HEIGHT;
        $textColor = self::TEXT_COLOR;

        return <<<HTML
        <div style="margin: {$options['marginTop']} 0 {$options['marginBottom']} 0; background-color: #ffffff; width: 100%;">
            <p style="
                margin: 0;
                font-family: $fontFamily;
                font-size: $fontSize;
                line-height: $lineHeight;
                text-align: {$options['textAlign']};
                color: $textColor;"
            >
                $text
            </p>
        </div>
        HTML;
    }

    /**
     * Creates a styled table component
     * @param array<array{th?: string, td: string}> $data Table row data
     * @param array $options Styling options
     * @return string String HTML for the table
     */
    public static function table(array $data, array $options = []): string
    {
        $options = array_merge([
            'marginTop' => '8px',
            'marginBottom' => '8px',
            'textAlign' => 'center',
            'headerText' => '',
            'headerBackgroundColor' => '#ffffff',
            'headerColor' => '#8C8C8C',
        ], $options);

        $fontFamily = self::FONT_FAMILY;
        $fontSize = self::FONT_SIZE;
        $textColor = self::TEXT_COLOR;
        $mutedColor = self::MUTED_COLOR;
        $borderColor = self::BORDER_COLOR;

        $html = <<<HTML
        <table cellpadding="0" cellspacing="0" width="100%" style="
            margin: {$options['marginTop']} 0 {$options['marginBottom']} 0;
            border-collapse: collapse;
            border: 1px solid $borderColor;"
        >
            <tbody>
        HTML;

        // Add header if provided
        if (!empty($options['headerText'])) {
            $html .= <<<HTML
            <tr>
                <td colspan="2" style="
                    padding: 6px 16px;
                    border-bottom: 1px solid $borderColor;
                    background-color: {$options['headerBackgroundColor']};
                    vertical-align: middle"
                >
                    <p style="
                        margin: 0;
                        font-family: $fontFamily;
                        font-size: $fontSize;
                        font-weight: 300;
                        text-align: center;
                        color: {$options['headerColor']};"
                    >
                        {$options['headerText']}
                    </p>
                </td>
            </tr>
            HTML;
        }

        // Check if any row has a header
        $containsTh = false;
        foreach ($data as $row) {
            if (array_key_exists('th', $row)) {
                $containsTh = true;
                break;
            }
        }

        // Generate rows
        $rowCount = 0;
        foreach ($data as $row) {
            if (array_key_exists('th', $row) && empty($row['th'])) {
                continue;
            }
            // Add divider after first row
            if ($rowCount > 0) {
                $html .= <<<HTML
                <tr>
                    <td style="text-align: center;">
                        <div style="
                            margin: 0 auto;
                            width: 95%;
                            height: 1px;
                            box-shadow: 0 1px 0 0 $borderColor;"
                        >
                        </div>
                    </td>
                </tr>
                HTML;
            }
            // Generate row HTML
            $thElement = '';
            if ($containsTh) {
                $thElement = <<<HTML
                <th scope="row" style="
                    width: 25%;
                    padding: 14px;
                    font-family: $fontFamily;
                    font-size: $fontSize;
                    font-weight: 300;
                    color: $mutedColor;
                    text-align: left;"
                >
                    {$row['th']}
                </th>
                HTML;
            }
            $textAlignProp = $containsTh ? '' : "text-align: {$options['textAlign']};";
            $html .= <<<HTML
            <tr>
                $thElement
                <td style="
                    width: 75%;
                    padding: 14px;
                    font-family: $fontFamily;
                    font-size: $fontSize;
                    color: $textColor;
                    $textAlignProp"
                >
                    {$row['td']}
                </td>
            </tr>
            HTML;
            $rowCount++;
        }

        $html .= <<<HTML
            </tbody>
        </table>
        HTML;

        return $html;
    }
}
