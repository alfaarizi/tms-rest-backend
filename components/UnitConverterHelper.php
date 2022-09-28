<?php

namespace app\components;

class UnitConverterHelper
{
    /**
     * Converts filesize from the php.ini format to bytes
     * @todo replace with ini_parse_quantity($size), if PHP 8.2 will be the required version
     * @param string $sizeStr
     * @return int
     */
    public static function phpFilesizeToBytes(string $sizeStr): int
    {
        $size = (int)$sizeStr;
        switch (strtoupper(substr($sizeStr, -1))) {
            case "K":
                return $size * 1024;
            case "M":
                return $size * 1024 * 1024;
            case "G":
                return $size * 1024 * 1024 * 1024;
            default:
                return $size;
        }
    }
}
