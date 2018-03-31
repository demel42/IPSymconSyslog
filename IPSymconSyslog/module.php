<?php

class IPSymconSyslog extends IPSModule
{
    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('server', '');
        $this->RegisterPropertyInteger('port', '514');
        $this->RegisterPropertyString('default_severity', 'info');
        $this->RegisterPropertyString('default_facility', 'local0');
        $this->RegisterPropertyString('default_program', 'ipsymcon');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

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
            $this->SetStatus($ok ? 102 : 201);
        } else {
            $this->SetStatus(104);
        }
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

        $syslog_message = '<'.$pri.'>'
            .'1'
            .' '
            .$timestamp
            .' '
            .$host
            .' '
            .$program
            .' '
            .$procid
            .' '
            .$msgid
            .' '
            .$sdata
            .' '
            .$msg;

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
