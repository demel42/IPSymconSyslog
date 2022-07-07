<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/common.php';
require_once __DIR__ . '/../libs/local.php';

class Syslog extends IPSModule
{
    use Syslog\StubsCommonLib;
    use SyslogLocalLib;

    private $ModuleDir;

    public function __construct(string $InstanceID)
    {
        parent::__construct($InstanceID);

        $this->ModuleDir = __DIR__;
    }

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean('module_disable', false);

        $this->RegisterPropertyString('server', '');
        $this->RegisterPropertyInteger('port', '514');
        $this->RegisterPropertyString('default_severity', 'info');
        if (defined('LOG_LOCAL0')) {
            $this->RegisterPropertyString('default_facility', 'local0');
        } else {
            $this->RegisterPropertyString('default_facility', 'user');
        }
        $this->RegisterPropertyString('default_program', 'ipsymcon');

        $this->RegisterPropertyInteger('update_interval', '0');

        $msgtypes = [
            ['msgtype' => KL_MESSAGE, 'title' => 'MESSAGE', 'active' => true],
            ['msgtype' => KL_SUCCESS, 'title' => 'SUCCESS', 'active' => true],
            ['msgtype' => KL_NOTIFY, 'title' => 'NOTIFY', 'active' => true],
            ['msgtype' => KL_WARNING, 'title' => 'WARNING', 'active' => true],
            ['msgtype' => KL_ERROR, 'title' => 'ERROR', 'active' => true],
            ['msgtype' => KL_DEBUG, 'title' => 'DEBUG', 'active' => false],
            ['msgtype' => KL_CUSTOM, 'title' => 'CUSTOM', 'active' => true],
        ];
        $this->RegisterPropertyString('msgtypes', json_encode($msgtypes));

        $exclude_filters = [
            ['field' => 'Sender', 'expression' => 'VariableManager'],
        ];
        $this->RegisterPropertyString('exclude_filters', json_encode($exclude_filters));

        $this->RegisterPropertyBoolean('with_tstamp_vars', false);

        $this->RegisterAttributeString('UpdateInfo', '');

        $this->InstallVarProfiles(false);

        $this->RegisterTimer('CheckMessages', 0, 'IPS_RequestAction(' . $this->InstanceID . ', "CheckMessages", "");');

        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($tstamp, $senderID, $message, $data)
    {
        parent::MessageSink($tstamp, $senderID, $message, $data);

        if ($message == IPS_KERNELMESSAGE && $data[0] == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function CheckModuleConfiguration()
    {
        $r = [];

        $server = $this->ReadPropertyString('server');
        if ($server == '') {
            $this->SendDebug(__FUNCTION__, '"server" is missing', 0);
            $r[] = $this->Translate('Server must be specified');
        }

        $default_severity = $this->ReadPropertyString('default_severity');
        if ($default_severity != '' && $this->decode_severity($default_severity) == -1) {
            $this->SendDebug(__FUNCTION__, '"default_severity" has unsupported value "' . $default_severity . '"', 0);
            $r[] = $this->Translate('Default severity has unsupported value');
        }

        $default_facility = $this->ReadPropertyString('default_facility');
        if ($default_facility != '' && $this->decode_facility($default_facility) == -1) {
            $this->SendDebug(__FUNCTION__, '"default_facility" has unsupported value "' . $default_facility . '"', 0);
            $r[] = $this->Translate('Default facility has unsupported value');
        }

        $default_program = $this->ReadPropertyString('default_program');
        if ($default_program == '') {
            $this->SendDebug(__FUNCTION__, '"default_program" is missing', 0);
            $r[] = $this->Translate('Default program must be specified');
        }

        return $r;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->MaintainReferences();

        if ($this->CheckPrerequisites() != false) {
            $this->MaintainTimer('CheckMessages', 0);
            $this->MaintainStatus(self::$IS_INVALIDPREREQUISITES);
            return;
        }

        if ($this->CheckUpdate() != false) {
            $this->MaintainTimer('CheckMessages', 0);
            $this->MaintainStatus(self::$IS_UPDATEUNCOMPLETED);
            return;
        }

        if ($this->CheckConfiguration() != false) {
            $this->MaintainTimer('CheckMessages', 0);
            $this->MaintainStatus(self::$IS_INVALIDCONFIG);
            return;
        }

        $vpos = 0;

        $with_tstamp_vars = $this->ReadPropertyBoolean('with_tstamp_vars');
        $this->MaintainVariable('LastMessage', $this->Translate('Timestamp of last message'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_tstamp_vars);
        $this->MaintainVariable('LastCycle', $this->Translate('Last cycle'), VARIABLETYPE_INTEGER, '~UnixTimestamp', $vpos++, $with_tstamp_vars);

        $module_disable = $this->ReadPropertyBoolean('module_disable');
        if ($module_disable) {
            $this->MaintainTimer('CheckMessages', 0);
            $this->MaintainStatus(IS_INACTIVE);
            return;
        }

        $this->MaintainStatus(IS_ACTIVE);

        if (IPS_GetKernelRunlevel() == KR_READY) {
            $this->SetUpdateInterval();
        }
    }

    private function GetFormElements()
    {
        $formElements = $this->GetCommonFormElements('Syslog');

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            return $formElements;
        }

        $formElements[] = [
            'name'    => 'module_disable',
            'type'    => 'CheckBox',
            'caption' => 'Disable instance'
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'name'    => 'server',
                    'type'    => 'ValidationTextBox',
                    'caption' => 'Server',
                ],
                [
                    'name'    => 'port',
                    'type'    => 'NumberSpinner',
                    'caption' => 'Port',
                ],
            ],
            'caption' => 'Basic configuration',
        ];

        $formElements[] = [
            'type'    => 'ExpansionPanel',
            'items'   => [
                [
                    'type'    => 'RowLayout',
                    'items'   => [
                        [
                            'name'    => 'default_severity',
                            'type'    => 'ValidationTextBox',
                            'caption' => 'severity',
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => 'possible values: emerg, alert, crit, err, warning, notice, info, debug',
                        ],
                    ],
                ],
                [
                    'type'    => 'RowLayout',
                    'items'   => [
                        [
                            'name'    => 'default_facility',
                            'type'    => 'ValidationTextBox',
                            'caption' => 'facility',
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => 'possible values: auth, local0, local1, local2, local3, local4, local5, local6, local7, user',
                        ],
                    ],
                ],
                [
                    'name'    => 'default_program',
                    'type'    => 'ValidationTextBox',
                    'caption' => 'program',
                ],
                [
                    'type'     => 'List',
                    'name'     => 'msgtypes',
                    'rowCount' => 7,
                    'add'      => false,
                    'delete'   => false,
                    'columns'  => [
                        [
                            'name'    => 'title',
                            'width'   => '150px',
                            'caption' => 'Name',
                        ],
                        [
                            'name'    => 'active',
                            'width'   => 'auto',
                            'edit'    => [
                                'type'    => 'CheckBox',
                                'caption' => 'Message is active'
                            ],
                            'caption' => 'Active',
                        ],
                        [
                            'name'    => 'msgtype',
                            'width'   => 'auto',
                            'save'    => true,
                            'visible' => false,
                            'caption' => 'Type',
                        ],
                    ],
                    'caption'  => 'Messages',
                ],
            ],
            'caption' => 'Default settings',
        ];

        $formElements[] = [
            'name'    => 'update_interval',
            'type'    => 'NumberSpinner',
            'minimum' => 0,
            'suffix'  => 'Seconds',
            'caption' => 'Check messages interval',
        ];

        $formElements[] = [
            'type'     => 'List',
            'name'     => 'exclude_filters',
            'rowCount' => 5,
            'add'      => true,
            'delete'   => true,
            'columns'  => [
                [
                    'caption' => 'Field',
                    'name'    => 'field',
                    'add'     => 'Sender',
                    'width'   => '150px',
                    'edit'    => [
                        'name'    => 'field',
                        'type'    => 'Select',
                        'options' => [
                            [
                                'caption' => 'Sender',
                                'value'   => 'Sender'
                            ],
                            [
                                'caption' => 'Text',
                                'value'   => 'Text'
                            ],
                        ],
                        'caption' => 'Field',
                    ]
                ],
                [
                    'name'    => 'expression',
                    'width'   => 'auto',
                    'add'     => '',
                    'edit'    => [
                        'type' => 'ValidationTextBox'
                    ],
                    'caption' => 'Regular expression for named field',
                ],
            ],
            'caption'  => 'Exclude filter',
        ];

        $formElements[] = [
            'type'    => 'CheckBox',
            'name'    => 'with_tstamp_vars',
            'caption' => 'Variables for Timestamps'
        ];

        return $formElements;
    }

    private function GetFormActions()
    {
        $formActions = [];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formActions[] = $this->GetCompleteUpdateFormAction();

            $formActions[] = $this->GetInformationFormAction();
            $formActions[] = $this->GetReferencesFormAction();

            return $formActions;
        }

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Testmessage',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "TestMessage", "");',
        ];

        $formActions[] = [
            'type'    => 'Button',
            'caption' => 'Check messages',
            'onClick' => 'IPS_RequestAction(' . $this->InstanceID . ', "CheckMessages", "");',
        ];

        $formActions[] = [
            'type'      => 'ExpansionPanel',
            'caption'   => 'Expert area',
            'expanded ' => false,
            'items'     => [
                $this->GetInstallVarProfilesFormItem(),
            ],
        ];

        $formActions[] = $this->GetInformationFormAction();
        $formActions[] = $this->GetReferencesFormAction();

        return $formActions;
    }

    private function LocalRequestAction($ident, $value)
    {
        $r = true;
        switch ($ident) {
            case 'TestMessage':
                $this->TestMessage();
                break;
            case 'CheckMessages':
                $this->CheckMessages();
                break;
            default:
                $r = false;
                break;
        }
        return $r;
    }

    public function RequestAction($ident, $value)
    {
        if ($this->LocalRequestAction($ident, $value)) {
            return;
        }
        if ($this->CommonRequestAction($ident, $value)) {
            return;
        }
        switch ($ident) {
            default:
                $this->SendDebug(__FUNCTION__, 'invalid ident ' . $ident, 0);
                break;
        }
    }

    private function TestMessage()
    {
        $this->Message('Testnachricht');
    }

    private function SetUpdateInterval()
    {
        $sec = $this->ReadPropertyInteger('update_interval');
        $msec = $sec > 0 ? $sec * 1000 : 0;
        $this->MaintainTimer('CheckMessages', $msec);
    }

    private function InitialSnapshot()
    {
        $r = IPS_GetSnapshotChanges(0);
        $snapshot = json_decode($r, true);
        $this->SetBuffer('TimeStamp', $snapshot[0]['TimeStamp']);
    }

    private function CheckMessages()
    {
        $type2severity = [
            KL_ERROR   => 'error',
            KL_WARNING => 'warning',
            KL_MESSAGE => 'info',
            KL_CUSTOM  => 'info',
            KL_SUCCESS => 'notice',
            KL_NOTIFY  => 'notice',
            KL_DEBUG   => 'debug',
        ];

        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

        $TimeStamp = $this->GetBuffer('TimeStamp');
        if ($TimeStamp == '' || $TimeStamp == 0) {
            $this->InitialSnapshot();
            $TimeStamp = $this->GetBuffer('TimeStamp');
        }

        $this->SendDebug(__FUNCTION__, 'start cycle with TimeStamp=' . $TimeStamp, 0);

        $sdata = @IPS_GetSnapshotChanges($TimeStamp);
        if ($sdata == '') {
            $this->MaintainStatus(self::$IS_NOSNAPSHOT);
            $old_ts = $TimeStamp;
            $this->InitialSnapshot();
            $TimeStamp = $this->GetBuffer('TimeStamp');
            $this->SendDebug(__FUNCTION__, 'unable to get snapshot (old=' . $old_ts . ', new=' . $TimeStamp . ') , resetting', 0);
            $this->LogMessage('unable to get snapshot (old=' . $old_ts . ', new=' . $TimeStamp . ') , resetting', KL_NOTIFY);
            $sdata = @IPS_GetSnapshotChanges($TimeStamp);
            if ($sdata == '') {
                $this->SendDebug(__FUNCTION__, 'unable to get snapshot (#' . $TimeStamp . '), reset failed', 0);
                $this->LogMessage('unable to get snapshot (#' . $TimeStamp . ') , reset failed', KL_NOTIFY);
                return;
            }
        }
        $udata = utf8_encode($sdata);
        $snapshot = json_decode($udata, true);
        if ($snapshot == '') {
            $txt = strlen($udata) > 7000 ? substr($udata, 0, 7000) . '...' : $r;
            $this->SendDebug(__FUNCTION__, 'unable to decode json-data, error=' . json_last_error() . ', len=' . strlen($sdata) . ', data=' . $txt . '...', 0);
            $this->LogMessage('unable to decode json-data, error=' . json_last_error() . ', length of data=' . strlen($sdata), KL_NOTIFY);
            $this->MaintainStatus(self::$IS_BADDATA);
            return;
        }

        $active_types = [];
        $s = $this->ReadPropertyString('msgtypes');
        $msgtypes = json_decode($s, true);
        if ($msgtypes != '') {
            foreach ($msgtypes as $msgtype) {
                if ($msgtype['active']) {
                    $active_types[] = $msgtype['msgtype'];
                }
            }
        }

        $s = $this->ReadPropertyString('exclude_filters');
        $exclude_filters = json_decode($s, true);

        $last_tstamp = 0;
        $n_accepted = 0;
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
                    $sender = trim($Data[0]);
                    $text = utf8_decode($Data[1]);
                    $tstamp = $Data[2];
                    break;
                default:
                    break;
            }

            if ($sender != '' && $exclude_filters != '') {
                foreach ($exclude_filters as $filter) {
                    if ($filter['field'] != 'Sender') {
                        continue;
                    }
                    $expr = $filter['expression'];
                    if (preg_match('/^[^\/].*[^\/]$/', $expr)) {
                        $expr = '/' . $expr . '/';
                    }
                    if (preg_match($expr, $sender)) {
                        // $this->SendDebug(__FUNCTION__, 'expr=' . $expr . ', sender=' . $sender . ' => suppress', 0);
                        $sender = '';
                        break;
                    }
                }
            }
            if ($sender == '') {
                continue;
            }

            if ($text != '' && $exclude_filters != '') {
                foreach ($exclude_filters as $filter) {
                    if ($filter['field'] != 'Text') {
                        continue;
                    }
                    $expr = $filter['expression'];
                    if (preg_match('/^[^\/].*[^\/]$/', $expr)) {
                        $expr = '/' . $expr . '/';
                    }
                    if (preg_match($expr, $text)) {
                        // $this->SendDebug(__FUNCTION__, 'expr=' . $expr . ', text=' . $text . ' => suppress', 0);
                        $text = '';
                        break;
                    }
                }
            }
            if ($text == '') {
                continue;
            }

            if ($tstamp) {
                $last_tstamp = $tstamp;
            }

            $ts = $tstamp ? date('d.m.Y H:i:s', $tstamp) : '';
            $n_txt = strlen($text);
            $txt = $n_txt > 1024 ? substr($text, 0, 1024) . '...' : $text;
            $this->SendDebug(__FUNCTION__, 'SenderID=' . $SenderID . ', Message=' . $Message . ', sender=' . $sender . ', tetx-len=' . $n_txt . ', text=' . utf8_decode($txt) . ', tstamp=' . $ts, 0);

            if (in_array($Message, $active_types) && isset($type2severity[$Message])) {
                $severity = $type2severity[$Message];
            } else {
                $severity = '';
            }
            if ($severity != '') {
                $this->Message($txt, $severity);
                $n_accepted++;
            }
        }

        $this->SendDebug(__FUNCTION__, 'length of data=' . strlen($sdata) . ', messages=' . count($snapshot) . ', sent=' . $n_accepted, 0);

        $this->SetBuffer('TimeStamp', $TimeStamp);

        $with_tstamp_vars = $this->ReadPropertyBoolean('with_tstamp_vars');
        if ($with_tstamp_vars) {
            if ($last_tstamp) {
                $this->SetValue('LastMessage', $last_tstamp);
            }
            $this->SetValue('LastCycle', time());
        }

        $this->MaintainStatus(IS_ACTIVE);

        $this->SendDebug(__FUNCTION__, $this->PrintTimer('CheckMessages'), 0);
    }

    public function Message(string $msg, string $severity = null, string $facility = null, string $program = null)
    {
        if ($this->CheckStatus() == self::$STATUS_INVALID) {
            $this->SendDebug(__FUNCTION__, $this->GetStatusText() . ' => skip', 0);
            return;
        }

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
            echo 'unsupported severity "' . $default_severity . '"';
            return -1;
        }
        if ($facility == null || $facility == '') {
            $facility = $default_facility;
        }
        $_facility = $this->decode_facility($facility);
        if ($_facility == -1) {
            echo 'unsupported facility "' . $default_facility . '"';

            return -1;
        }
        if ($program == null || $program == '') {
            $program = $default_program;
        }

        $pri = $_facility + $_severity;
        $host = gethostname();
        $timestamp = date('Y-m-d\TH:i:sP');
        $msgid = '-';
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

        $this->SendDebug(__FUNCTION__, 'server=' . $server . ', port=' . $port . ', message="' . $syslog_message . '"', 0);

        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$sock) {
            echo 'unable to create socket(' . $server . ':' . $port . ')';
            return -1;
        }
        $l = strlen($syslog_message);
        $n = socket_sendto($sock, $syslog_message, $l, 0, $server, (int) $port);
        if ($n != $l) {
            echo 'unable to set messages "' . $syslog_message . '" to socket(' . $server . ':' . $port . ')';
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
        if (defined('LOG_LOCAL0')) {
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
        } else {
            $str2facility = [
                'auth'    => LOG_AUTH,
                'user'    => LOG_USER,
            ];
        }

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
