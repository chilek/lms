<?php

if (!class_exists('Utils')) {
    class Utils
    {
        public static function removeHtml($value)
        {
            return strip_tags($value);
        }

        public static function wordWrapToArray($value)
        {
            return [$value];
        }
    }
}
