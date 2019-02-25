# IPSymconSyslog

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-2.1-blue.svg)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/126683101/shield?branch=master)](https://github.styleci.io/repos/126683101)

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

## 2. Voraussetzungen

 - IP-Symcon ab Version 5.x
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

### Variablen

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :-----------------------: | :-----:  | :----------: | :----------------------------------------------------------------------------------------------------------: |
| Server                    | string   |              | Hostname / IP-Adresse des Syslog-Servers |
| Port                      | integer  | 514          | Port, unter dem der Syslog-Server die Daten empfängt |
| Schwere                   | string   | info         | Schwere (severity) der Nachricht |
| Kategorie                 | string   | info         | Kategorie (facility) der Nachricht |
| Programm                  | string   | info         | Programm der Nachricht |
|                           |          |              | |
| Aktualisiere Daten ...    | integer  | 10           | Aktualisierungsintervall, Angabe in Sekunden |

### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :------------------------------------------------: |
| Übertrage Nachrichten        | führt eine sofortige Aktualisierung durch |
| Testnachricht                | Sendet eine Testnachricht |

## 6. Anhang

GUIDs

- Modul: `{4FF4E908-F7EC-40A4-9114-A93AA5E29FAF}`
- Instanzen:
  - Syslog: `{2D3D36C0-E7AC-4F4C-ACB7-D54D87011B0E}`

## 7. Versions-Historie

- 2.1 @ 25.02.2019 16:41<br>
  - Protokllierung aller IPS-Messages ...

- 1.1 @ 17.09.2018 17:47<br>
  - Versionshistorie dazu,
  - define's der Variablentypen,
  - Schaltfläche mit Link zu README.md im Konfigurationsdialog

- 1.0 @ 25.03.2018 10:07<br>
  Initiale Version
