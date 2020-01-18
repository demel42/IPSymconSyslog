# IPSymconSyslog

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Das Modul umfasst zwei Funktionsblöcke

a) die Übertragung einzelner Nachrichten an den Syslog-Server. Dazu stehen einige Funktionen zur Verfügung.<br>
b) die automatische, zyklische Übertragung der IPS-Logmeldungen an der Syslog-Server

## 2. Voraussetzungen

 - IP-Symcon ab Version 5.x<br>
   Version 4.4 mit Branch _ips_4.4_ (nur noch Fehlerkorrekturen)
 - Syslog-Server
   - Protokoll: IETF (RFC 5424)
   - Transportschicht: UDP

## 3. Installation

### a. Laden des Moduls

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconSyslog.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _(sonstiges)_ und als Gerät _Syslog_ auswählen.

In dem Konfigurationsdialog den Syslog-Server eintragen (Name oder IP-Adresse sind zulässig)

## 4. Funktionsreferenz

### zentrale Funktion

`Syslog_Message(integer $InstanzID, string $Message, string Severity, string $Facility, string $Program)`

Sendet die Nachricht _Message_ an der Syslog-Server. Die Optionen _Severity-, _Facility_ und _Program_ können leer bleiben, dann werden die Standardwerte aus der Konfiguration verwendet.

`Syslog_Error(integer $InstanzID, string $Message)`

Sendet die Nachricht _Message_ an der Syslog-Server mit der severity _error_; als _Facility_ und _Program_ werden die Standardwerte verwendet.

`Syslog_Warning(integer $InstanzID, string $Message)`

Sendet die Nachricht _Message_ an der Syslog-Server mit der severity _warning_; als _Facility_ und _Program_ werden die Standardwerte verwendet.

`Syslog_Notice(integer $InstanzID, string $Message)`

Sendet die Nachricht _Message_ an der Syslog-Server mit der severity _notice_; als _Facility_ und _Program_ werden die Standardwerte verwendet.

`Syslog_Info(integer $InstanzID, string $Message)`

Sendet die Nachricht _Message_ an der Syslog-Server mit der severity _info_; als _Facility_ und _Program_ werden die Standardwerte verwendet.

## 5. Konfiguration:

### globale Variablen

| Eigenschaft | Typ     | Standardwert | Beschreibung |
| :---------- | :------ | :----------- | :----------- |
| Server      | string  |              | Hostname / IP-Adresse des Syslog-Servers |
| Port        | integer | 514          | Port, unter dem der Syslog-Server die Daten empfängt |

### Variablen für das Senden von einzelnen Nachrichten

| Eigenschaft | Typ     | Standardwert | Beschreibung |
| :---------- | :------ | :----------- | :----------- |
| Schwere     | string  | info         | Schwere (severity) der Nachricht |
| Kategorie   | string  | info         | Kategorie (facility) der Nachricht |
| Programm    | string  | info         | Programm der Nachricht |

### Variablen für die zyklische Übertragung von IPS-Logmeldungen

| Eigenschaft               | Typ     | Standardwert                | Beschreibung |
| :------------------------ | :------ | :-------------------------- | :----------- |
| Intervall                 | integer | 10                          | Aktualisierungsintervall, Angabe in Sekunden |
| aktive Nachrichten        | list    | alles ausser DEBUG auf true | Angabe der Nachrichten-Typen, die übertragen werden sollen |
| Ausschlussfilter          | list    | VariablenManager            | Angabe von regulären Ausdrücken zur Unterdrückung von Nachrichten nach _Sender_ und _Text_ |
| Variablen für Zeitstempel | bool    | false                       | Variablen für einen Zeitstempel der letzten Prüfung und der letzten übertragenen Nachricht |
| Ausschlussfilter          | list    | VariablenManager            | Angabe von regulären Ausdrücken zur Unterdrückung von Nachrichten nach _Sender_ und _Text_ |

Hinweis zu _Intervall_
- das Intervall sollte nicht zu groß sein, damit alle Nachrichten zum vorigen Zyklus noch vorhanden sind. Zuständig hier für ist die Spezialschalter [MessageRingBufferSize](https://www.symcon.de/service/dokumentation/entwicklerbereich/spezialschalter), der muss ggfs erhöht werden. Wenn dieser Wert nicht ausreicht wird im IPS-Log eine Meldung mit dem Text _unable to get snapshot ..._ ausgegeben.

Hinweis zu _Ausschluss-Filter_
- der Ausdruck ist ein regulärer Ausdruck, bei fehlendem **/** (notwendig für einen regulären Ausdruck) an Anfang oder Ende wird dies ergänzt.
- die Werte für _Sender_ finden sich in der 4. Spalte des IPS-Logs
- die einzelnen Ausdrücke wirken unabhängig von einander (ODER-Verknüpfung)

Hinweis zu _Variablen für Zeitstempel_
- Wenn man die Änderung von Variablen nicht ausschliessst (_VariablenManager_), erzeugt man sich ein zusätzliches Nachrichten-Aufkommen, wenn die Variablen benutzt werden.

### Schaltflächen

| Bezeichnung       | Beschreibung |
| :---------------- | :----------- |
| Testnachricht     | Sendet eine Testnachricht |
| Prüfe Nachrichten | führt eine sofortige Prüfung durch |

## 6. Anhang

GUIDs

- Modul: `{4FF4E908-F7EC-40A4-9114-A93AA5E29FAF}`
- Instanzen:
  - Syslog: `{2D3D36C0-E7AC-4F4C-ACB7-D54D87011B0E}`

## 7. Versions-Historie

- 2.9 @ 30.12.2019 10:56
  - Anpassungen an IPS 5.3
    - Formular-Elemente: 'label' in 'caption' geändert

- 2.8 @ 06.12.2019 07:35
  - 'Zeitstempel der letzten Nachricht' wird nicht mehr gelöscht, wenn keine Nachricht zu übertragen war

- 2.7 @ 30.10.2019 17:59
  - Umlaut-Problem bei der Übertragung von IPS-Messages gefixed

- 2.6 @ 10.10.2019 17:27
  - Anpassungen an IPS 5.2
    - IPS_SetVariableProfileValues(), IPS_SetVariableProfileDigits() nur bei INTEGER, FLOAT
    - Dokumentation-URL in module.json
  - Umstellung auf strict_types=1
  - Umstellung von StyleCI auf php-cs-fixer

- 2.5 @ 09.08.2019 14:32
  - Schreibfehler korrigiert

- 2.4 @ 02.05.2019 19:31
  - fehlende Übersetzungen (für IS_DELETING, IS_DELETING)

- 2.3 @ 29.03.2019 16:19
  - SetValue() abgesichert

- 2.2 @ 20.03.2019 20:27
  - Konfigurations-Element IntervalBox -> NumberSpinner
  - Anpassungen IPS 5

- 2.1 @ 02.03.2019 09:24
  - Anpassungen IPS 5, Abspaltung Branch _ips_4.4_
  - Protokollierung aller IPS-Protokoll-Messages ...

- 1.1 @ 17.09.2018 17:47
  - Versionshistorie dazu,
  - define's der Variablentypen,
  - Schaltfläche mit Link zu README.md im Konfigurationsdialog

- 1.0 @ 25.03.2018 10:07
  - Initiale Version
