<?php

namespace Fast\SisdikBundle\Util;
/**
 * Utiliti pembentuk ukuran file
 */
class FileSizeFormatter
{
    public static function formatBytes($size, $type) {
        switch ($type) {
            case "KB":
                $size = $size * .0009765625;
                break;
            case "MB":
                $size = ($size * .0009765625) * .0009765625;
                break;
            case "GB":
                $size = (($size * .0009765625) * .0009765625) * .0009765625;
                break;
        }
        if ($size <= 0) {
            return $size = 'unknown';
        } else {
            return round($size, 2) . ' ' . $type;
        }
    }
}
