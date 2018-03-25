# Syslog

Modul für IP-Symcon ab Version 4.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguartion)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

## 2. Voraussetzungen

 - IPS 4.x
 - Syslog-Server
   - Protokoll: IETF (RFC 5424)
   - Transportschicht: UDP

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min Ver. 4.x) Konsole öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconSyslog.git`
    
und mit _OK_ bestätigen.    
        
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    

### b. Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _(sonstiges)_ und als Gerät _Syslog_ auswählen.

In dem Konfigurationsdialog den Syslog-Server eintragen (Name oder IP-Adresse sind zulässig)

## 4. PHP-Befehlsreferenz

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

### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :------------------------------------------------: |
| Testnachricht                | Sendet eine Testnachricht |

## 6. Anhang

GUID: `{4FF4E908-F7EC-40A4-9114-A93AA5E29FAF}` 
