<?php

class ConfigHelper
{
    public static function checkPrivilege($name)
    {
        return true;
    }

    public static function getConfig($name, $default = null)
    {
        return $default;
    }

    public static function checkConfig($name, $default = false)
    {
        return $default;
    }
}
