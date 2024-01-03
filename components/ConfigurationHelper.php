<?php

namespace app\components;

/**
 * A helper class for configuration parsing.
 */
class ConfigurationHelper
{
    /**
     * Gets the temp folder path by checking whether it is configured as an absolute or relative path.
     *
     * Defaults to `sys_get_temp_dir() . '/tms'` if not configured.
     *
     * @param string|null $path The path in the configuration file
     * @return string The corrected temp folder path
     */
    public static function checkTempPath(?string $path): string
    {
        $path = trim($path);
        if (!empty($path)) {
            if (
                (PHP_OS_FAMILY === 'Windows' && preg_match('/^[a-zA-Z]:\\\\/', $path)) ||
                (PHP_OS_FAMILY !== 'Windows' && $path[0] === '/')
            ) {
                return $path;
            }
            return '@runtime/' . $path;
        } else {
            return sys_get_temp_dir() . '/tms';
        }
    }
}
