<?php

if (!defined('KL_MESSAGE')) {
    define('IPS_BASE', 10000);
    // --- KERNEL LOGMESSAGE
    define('IPS_LOGMESSAGE', IPS_BASE + 200);           // Logmessage Message
    define('KL_MESSAGE', IPS_LOGMESSAGE + 1);           // Normal Message
    define('KL_SUCCESS', IPS_LOGMESSAGE + 2);           // Success Message
    define('KL_NOTIFY', IPS_LOGMESSAGE + 3);            // Notiy about Changes
    define('KL_WARNING', IPS_LOGMESSAGE + 4);           // Warnings
    define('KL_ERROR', IPS_LOGMESSAGE + 5);             // Error Message
    define('KL_DEBUG', IPS_LOGMESSAGE + 6);             // Debug Informations + Script Results
    define('KL_CUSTOM', IPS_LOGMESSAGE + 7);            // User Message
}

if (!defined('IS_SBASE')) {
    define('IS_SBASE', 100);							// Wertebasis für Status Codes
    define('IS_CREATING', IS_SBASE + 1);				// Instanz wurde erstellt
    define('IS_ACTIVE', IS_SBASE + 2);					// Instanz wurde erstellt und ist aktiv
    define('IS_DELETING', IS_SBASE + 3);				// Instanz wurde gelöscht
    define('IS_INACTIVE', IS_SBASE + 4);				// Instanz wird nicht benutzt
    define('IS_NOTCREATED', IS_SBASE + 5);				// Instanz wurde nicht erstellt
    define('IS_EBASE', 200);							// Base Message
}

if (!defined('IS_INVALIDCONFIG')) {
    define('IS_INVALIDCONFIG', IS_EBASE + 1);
}

class Syslog extends IPSModule
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('server', '');
        $this->RegisterPropertyInteger('port', '514');
        $this->RegisterPropertyString('default_severity', 'info');
        $this->RegisterPropertyString('default_facility', 'local0');
        $this->RegisterPropertyString('default_program', 'ipsymcon');

        $this->RegisterPropertyInteger('update_interval', '0');

        $this->RegisterPropertyBoolean('with_KL_MESSAGE', false);
        $this->RegisterPropertyBoolean('with_KL_SUCCESS', false);
        $this->RegisterPropertyBoolean('with_KL_NOTIFY', false);
        $this->RegisterPropertyBoolean('with_KL_WARNING', false);
        $this->RegisterPropertyBoolean('with_KL_ERROR', false);
        $this->RegisterPropertyBoolean('with_KL_DEBUG', false);
        $this->RegisterPropertyBoolean('with_KL_CUSTOM', false);

        $this->RegisterTimer('CheckMessages', 0, 'Syslog_CheckMessages(' . $this->InstanceID . ');');
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 0;

        $this->MaintainVariable('LastMessage', $this->Translate('Timestamp of last message'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);
        $this->MaintainVariable('LastCycle', $this->Translate('Last cycle'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, true);

        $syslog_server = $this->ReadPropertyString('server');
        $syslog_port = $this->ReadPropertyInteger('port');
        $default_severity = $this->ReadPropertyString('default_severity');
        $default_facility = $this->ReadPropertyString('default_facility');
        $default_program = $this->ReadPropertyString('default_program');

        if ($syslog_server != '' && $syslog_port > 0) {
            $ok = true;
            if ($default_severity != '') {
                if ($this->decode_severity($default_severity) == -1) {
                    echo "unsupported value \"$default_severity\" for property \"severity\"";
                    $ok = false;
                }
            }
            if ($default_facility != '') {
                if ($this->decode_facility($default_facility) == -1) {
                    echo "unsupported value \"$default_facility\" for property \"facility\"";
                    $ok = false;
                }
            }
            if ($default_program == '') {
                echo 'no value for property "program"';
                $ok = false;
            }
            $this->SetStatus($ok ? IS_ACTIVE : IS_INVALIDCONFIG);
        } else {
            $this->SetStatus(IS_INACTIVE);
        }

        $this->SetUpdateInterval();
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'server', 'caption' => 'Server'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'port', 'caption' => 'Port'];
        $formElements[] = ['type' => 'Label', 'label' => 'default settings'];
        $formElements[] = ['type' => 'Label', 'label' => 'possible values for severity: emerg, alert, crit, err, warning, notice, info, debug'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'default_severity', 'caption' => 'severity'];
        $formElements[] = ['type' => 'Label', 'label' => 'possible values for facility: auth, local0, local1, local2, local3, local4, local5, local6, local7, user'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'default_facility', 'caption' => 'facility'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'default_program', 'caption' => 'program'];
        $formElements[] = ['type' => 'Label', 'label' => ''];
        $formElements[] = ['type' => 'Label', 'label' => 'transfer IPS-messages to syslog'];
        $formElements[] = ['type' => 'Label', 'label' => 'Check messages every X seconds'];
        $formElements[] = ['type' => 'IntervalBox', 'name' => 'update_interval', 'caption' => 'Seconds'];
        $formElements[] = ['type' => 'Label', 'label' => 'with message-type ...'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_KL_ERROR', 'caption' => ' ... ERROR'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_KL_WARNING', 'caption' => ' ... WARNING'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_KL_SUCCESS', 'caption' => ' ... SUCCESS'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_KL_MESSAGE', 'caption' => ' ... MESSAGE'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_KL_NOTIFY', 'caption' => ' ... NOTIFY'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_KL_CUSTOM', 'caption' => ' ... CUSTOM'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_KL_DEBUG', 'caption' => ' ... DEBUG'];

        $formActions = [];
        $formActions[] = ['type' => 'Button', 'label' => 'Testmessage', 'onClick' => 'Syslog_TestMessage($id);'];
        $formActions[] = ['type' => 'Button', 'label' => 'Check messages', 'onClick' => 'Syslog_CheckMessages($id);'];
        $formActions[] = ['type' => 'Label', 'label' => '____________________________________________________________________________________________________'];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Module description',
                            'onClick' => 'echo "https://github.com/demel42/IPSymconSyslog/blob/master/README.md";'
                        ];

        $formStatus = [];
        $formStatus[] = ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'];
        $formStatus[] = ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'];

        $formStatus[] = ['code' => IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid configuration)'];

        return json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $this->InitialSnapshot();
        }
    }

    protected function SetUpdateInterval()
    {
        $sec = $this->ReadPropertyInteger('update_interval');
        $msec = $sec > 0 ? $sec * 1000 : 0;
        $this->SetTimerInterval('CheckMessages', $msec);
    }

    protected function InitialSnapshot()
    {
        $r = IPS_GetSnapshotChanges(0);
        $snapshot = json_decode($r, true);
        $this->SetBuffer('TimeStamp', $snapshot[0]['TimeStamp']);
    }

    public function CheckMessages()
    {
        $with_KL_MESSAGE = $this->ReadPropertyBoolean('with_KL_MESSAGE');
        $with_KL_SUCCESS = $this->ReadPropertyBoolean('with_KL_SUCCESS');
        $with_KL_NOTIFY = $this->ReadPropertyBoolean('with_KL_NOTIFY');
        $with_KL_WARNING = $this->ReadPropertyBoolean('with_KL_WARNING');
        $with_KL_ERROR = $this->ReadPropertyBoolean('with_KL_ERROR');
        $with_KL_DEBUG = $this->ReadPropertyBoolean('with_KL_DEBUG');
        $with_KL_CUSTOM = $this->ReadPropertyBoolean('with_KL_CUSTOM');

        $TimeStamp = $this->GetBuffer('TimeStamp');
        if ($TimeStamp == '' || $TimeStamp == 0) {
            $this->InitialSnapshot();
            $TimeStamp = $this->GetBuffer('TimeStamp');
        }

        $this->SendDebug(__FUNCTION__, 'start cycle with TimeStamp=' . $TimeStamp, 0);

        $r = IPS_GetSnapshotChanges($TimeStamp);
        $snapshot = json_decode($r, true);

        $last_tstamp = 0;
        foreach ($snapshot as $obj) {
            $SenderID = $obj['SenderID'];
            $TimeStamp = $obj['TimeStamp'];
            $Message = $obj['Message'];
            $Data = $obj['Data'];

            if ($SenderID == $this->InstanceID) {
                continue;
            }

            $sender = '';
            $text = '';
            $tstamp = 0;

            switch ($Message) {
                case KL_MESSAGE:
                case KL_SUCCESS:
                case KL_NOTIFY:
                case KL_WARNING:
                case KL_ERROR:
                case KL_DEBUG:
                case KL_CUSTOM:
                    $sender = $Data[0];
                    $text = $Data[1];
                    $tstamp = $Data[2];
                    break;
                default:
                    break;
            }

            if ($sender == '') {
                continue;
            }

            $last_tstamp = $tstamp;

            $ts = date('d.m.Y H:i:s', $tstamp);
            $this->SendDebug(__FUNCTION__, 'SenderID=' . $SenderID . ', Message=' . $Message . ', sender=' . $sender . ', text=' . utf8_decode($text) . ', tstamp=' . $ts, 0);

            $severity = '';
            switch ($Message) {
                case KL_ERROR:
                    if ($with_KL_ERROR) {
                        $severity = 'error';
                    }
                    break;
                case KL_WARNING:
                    if ($with_KL_WARNING) {
                        $severity = 'warning';
                    }
                    break;
                case KL_MESSAGE:
                    if ($with_KL_MESSAGE) {
                        $severity = 'info';
                    }
                    break;
                case KL_CUSTOM:
                    if ($with_KL_CUSTOM) {
                        $severity = 'info';
                    }
                    break;
                case KL_SUCCESS:
                    if ($with_KL_SUCCESS) {
                        $severity = 'notice';
                    }
                case KL_NOTIFY:
                    if ($with_KL_NOTIFY) {
                        $severity = 'notice';
                    }
                    break;
                case KL_DEBUG:
                    if ($with_KL_DEBUG) {
                        $severity = 'debug';
                    }
                    break;
                default:
                    $severity = '';
                    break;
            }
            if ($severity != '') {
                $this->Message($text, $severity);
            }
        }

        $this->SetBuffer('TimeStamp', $TimeStamp);

        $this->SetValue('LastMessage', $last_tstamp);
        $this->SetValue('LastCycle', time());
    }

    public function TestMessage()
    {
        $this->Message('Testnachricht');
    }

    public function Message(string $msg, string $severity = null, string $facility = null, string $program = null)
    {
        $server = $this->ReadPropertyString('server');
        $port = $this->ReadPropertyInteger('port');
        $default_severity = $this->ReadPropertyString('default_severity');
        $default_facility = $this->ReadPropertyString('default_facility');
        $default_program = $this->ReadPropertyString('default_program');

        if ($severity == null || $severity == '') {
            $severity = $default_severity;
        }
        $_severity = $this->decode_severity($severity);
        if ($_severity == -1) {
            echo "unsupported severity \"$default_severity\"";

            return -1;
        }
        if ($facility == null || $facility == '') {
            $facility = $default_facility;
        }
        $_facility = $this->decode_facility($facility);
        if ($_facility == -1) {
            echo "unsupported facility \"$default_facility\"";

            return -1;
        }
        if ($program == null || $program == '') {
            $program = $default_program;
        }

        $pri = $_facility + $_severity;
        $host = gethostname();
        $timestamp = date('Y-m-d\TH:i:sP');
        $msgid = date('Uu');
        $procid = '-';
        $sdata = '-';

        $syslog_message = '<' . $pri . '>'
            . '1'
            . ' '
            . $timestamp
            . ' '
            . $host
            . ' '
            . $program
            . ' '
            . $procid
            . ' '
            . $msgid
            . ' '
            . $sdata
            . ' '
            . $msg;

        $this->SendDebug(__FUNCTION__, "server=$server:$port, message=\"$syslog_message\"", 0);

        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$sock) {
            echo "unable to create socket(server:$server, port=$port)\n";

            return -1;
        }
        $l = strlen($syslog_message);
        $n = socket_sendto($sock, $syslog_message, $l, 0, $server, (int) $port);
        if ($n != $l) {
            echo "unable to set messages \"$syslog_message\" to socket(server:$server, port=$port)\n";

            return -1;
        }
        socket_close($sock);
    }

    public function Error(string $msg)
    {
        $this->Message($msg, 'error', null, null);
    }

    public function Warning(string $msg)
    {
        $this->Message($msg, 'warn', null, null);
    }

    public function Notice(string $msg)
    {
        $this->Message($msg, 'notice', null, null);
    }

    public function Info(string $msg)
    {
        $this->Message($msg, 'info', null, null);
    }

    private function decode_facility($str)
    {
        $str2facility = [
                'auth'    => LOG_AUTH,
                'local0'  => LOG_LOCAL0,
                'local1'  => LOG_LOCAL1,
                'local2'  => LOG_LOCAL2,
                'local3'  => LOG_LOCAL3,
                'local4'  => LOG_LOCAL4,
                'local5'  => LOG_LOCAL5,
                'local6'  => LOG_LOCAL6,
                'local7'  => LOG_LOCAL7,
                'user'    => LOG_USER,
            ];

        $str = strtolower($str);
        foreach ($str2facility as $key => $val) {
            if ($key == $str) {
                return $val;
            }
        }

        return -1;
    }

    private function decode_severity($str)
    {
        $str2severity = [
                'emerg'         => LOG_EMERG,
                'emergency'     => LOG_EMERG,
                'alert'         => LOG_ALERT,
                'crit'          => LOG_CRIT,
                'critical'      => LOG_CRIT,
                'err'           => LOG_ERR,
                'error'         => LOG_ERR,
                'warn'          => LOG_WARNING,
                'warning'       => LOG_WARNING,
                'notice'        => LOG_NOTICE,
                'info'          => LOG_INFO,
                'informational' => LOG_INFO,
                'debug'         => LOG_DEBUG,
            ];

        $str = strtolower($str);
        foreach ($str2severity as $key => $val) {
            if ($key == $str) {
                return $val;
            }
        }

        return -1;
    }
}
