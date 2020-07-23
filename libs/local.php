<?php

declare(strict_types=1);

trait SyslogLocalLib
{
    public static $IS_INVALIDCONFIG = IS_EBASE + 1;
    public static $IS_NOSNAPSHOT = IS_EBASE + 2;
    public static $IS_BADDATA = IS_EBASE + 3;

    private function GetFormStatus()
    {
        $formStatus = [];

        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => self::$IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid configuration)'];
        $formStatus[] = ['code' => self::$IS_NOSNAPSHOT, 'icon' => 'error', 'caption' => 'Instance is inactive (no snapshot)'];
        $formStatus[] = ['code' => self::$IS_BADDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (bad data)'];

        return $formStatus;
    }
}
