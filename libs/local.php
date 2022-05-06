<?php

declare(strict_types=1);

trait SyslogLocalLib
{
    public static $IS_NOSNAPSHOT = IS_EBASE + 10;
    public static $IS_BADDATA = IS_EBASE + 11;

    private function GetFormStatus()
    {
        $formStatus = $this->GetCommonFormStatus();

        $formStatus[] = ['code' => self::$IS_NOSNAPSHOT, 'icon' => 'error', 'caption' => 'Instance is inactive (no snapshot)'];
        $formStatus[] = ['code' => self::$IS_BADDATA, 'icon' => 'error', 'caption' => 'Instance is inactive (bad data)'];

        return $formStatus;
    }

    public static $STATUS_INVALID = 0;
    public static $STATUS_VALID = 1;
    public static $STATUS_RETRYABLE = 2;

    private function CheckStatus()
    {
        switch ($this->GetStatus()) {
            case IS_ACTIVE:
                $class = self::$STATUS_VALID;
                break;
            default:
                $class = self::$STATUS_INVALID;
                break;
        }

        return $class;
    }

    public function InstallVarProfiles(bool $reInstall = false)
    {
        if ($reInstall) {
            $this->SendDebug(__FUNCTION__, 'reInstall=' . $this->bool2str($reInstall), 0);
        }
    }
}
