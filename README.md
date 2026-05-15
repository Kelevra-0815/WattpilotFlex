# Fronius Wattpilot Flex – IP-Symcon Modul

[![IPS Version](https://img.shields.io/badge/IP--Symcon-7.1%2B-blue)](https://www.symcon.de)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)
[![PayPal Spende](https://img.shields.io/badge/PayPal-Spenden-blue?logo=paypal)](https://www.paypal.com/donate/?hosted_button_id=4HVXKG7QKF4WW)

Lokale WebSocket-Integration des **Fronius Wattpilot Flex (C6)** für [IP-Symcon](https://www.symcon.de).  
Keine Cloud, keine API-Keys – vollständig lokal über persistente WebSocket-Verbindung.

---

## ⚡ Funktionen

- **Echtzeit-Daten** – Persistente WebSocket-Verbindung mit Push-Updates (deltaStatus)
- **Steuerung** – Lademodus, Force-Modus, Ladestrom, Phasenmodus, Stromstufen direkt aus dem WebFront
- **Optionale Variablen** – Strom, Spannung, Temperaturen, PV, System-Infos nur bei Bedarf aktivieren
- **Archivierung** – Ladeleistung (Standard) und Gesamtenergie (Zähler) optional loggen
- **Ladestrom-Begrenzung** – Automatisch an Wattpilot-Variante angepasst (11 kW → max 16 A, 22 kW → max 32 A)
- **Splitter/Device-Architektur** – Saubere Trennung von Verbindung und Datenverarbeitung
- **Throttle** – Gebündelte Variablen-Updates über konfigurierbaren Timer (verhindert WebFront-Flut)
- **bcrypt-Cache** – Teure Passwort-Berechnung nur einmalig
- **Reconnect mit Backoff** – Automatischer Verbindungsaufbau mit exponentiellem Backoff

---

## 📋 Voraussetzungen

- IP-Symcon **7.1** oder höher
- Fronius Wattpilot Flex (C6) im lokalen Netzwerk erreichbar
- Wattpilot-Passwort (aus der Wattpilot-App → Einstellungen → WLAN-Passwort)

---

## 📦 Installation

### Über den IP-Symcon Module Store (empfohlen)

1. In der IP-Symcon Verwaltungskonsole den **Module Store** öffnen
2. Nach **„Fronius - Wattpilot flex C6"** suchen
3. Modul installieren – fertig!

---

## 🔧 Einrichtung

### 1. Splitter-Instanz (Wattpilot Verbindung)

Die Splitter-Instanz wird automatisch mit der Flex-Instanz erstellt. Konfiguration:

| Einstellung | Beschreibung | Standard |
|---|---|---|
| IP-Adresse | IP des Wattpilot im lokalen Netzwerk | – |
| Passwort | Wattpilot-Passwort aus der App | – |
| Reconnect-Check Intervall | Prüft periodisch ob Verbindung steht | 60 s |
| Variablen-Aktualisierung | Timer für gebündelte Updates an Variablen | 60 s |

### 2. Flex-Instanz (Wattpilot Flex)

Die Konfiguration erfolgt über aufklappbare Panels. Jedes Panel enthält Checkboxen zum Aktivieren/Deaktivieren von Variablengruppen sowie ein Dokumentations-Panel mit Beschreibung der Variablen und Skript-Funktionen.

#### Optionale Variablengruppen

| Gruppe | Variablen | Standard |
|---|---|---|
| Strom (L1, L2, L3) | WP_nrg_il1, il2, il3 | ❌ |
| Spannung (L1, L2, L3) | WP_nrg_ul1, ul2, ul3 | ❌ |
| Leistungsfaktor (L1, L2, L3) | WP_nrg_pfl1, pfl2, pfl3 | ❌ |
| Temperaturen | WP_tma1–4 | ❌ |
| Netzfrequenz | WP_fhz | ❌ |
| Phasen-Details | WP_pha_l1–l3, WP_pnp | ❌ |
| Laden erweitert | WP_ama, WP_mca, WP_fsp | ❌ |
| Stromstufen | WP_al1–5, WP_clp | ❌ |
| Verriegelung | WP_ust, WP_lck, WP_ffb, WP_cus | ❌ |
| Extras | WP_adi, WP_trx, WP_dwo | ❌ |
| Next Trip | WP_fte, WP_ftt | ❌ |
| OCPP | WP_ocppe, WP_ocppu, WP_ocpph, WP_ocpps | ❌ |
| Hotspot | WP_wan, WP_wak | ❌ |
| PV & Batterie | WP_pGrid, WP_pPv, WP_pAkku, WP_akkuSOC | ❌ |
| PV Einstellungen | WP_fst, WP_fup, WP_po, WP_sh, WP_psh, WP_spl3 | ❌ |
| Awattar | WP_awc, WP_awp, WP_ful | ❌ |
| Ladeplaner | WP_sch_week/satur/sund_ctrl + _slots | ❌ |
| System | WP_fwv, WP_sse, WP_var, WP_rbc, WP_rbt | ❌ |
| Netzwerk | WP_wst, WP_wsms, WP_rssi, WP_host, WP_fna | ❌ |

#### Archivierung / Logging

| Einstellung | Beschreibung | Standard |
|---|---|---|
| Ladeleistung gesamt | Archivierung `WP_nrg_ptotal` (Standard-Aggregation) | ❌ |
| Gesamtenergie | Archivierung `WP_eto` (Zähler-Aggregation) | ❌ |

> **Hinweis:** Deaktivierte Variablen werden beim Speichern gelöscht. Leere Kategorien werden automatisch aufgeräumt.

---

## 📊 Variablen

### Immer aktiv (Kernvariablen)

| Kategorie | Variable | Beschreibung | Steuerbar |
|---|---|---|---|
| Fahrzeug & Laden | WP_car | Fahrzeugstatus (Integer mit Variablenprofil) | |
| | WP_alw | Laden erlaubt | |
| | WP_frc | Force-Modus (Neutral / Aus / Ein) | ✅ |
| | WP_lmo | Lademodus (Standard / ECO / AutoStop) | ✅ |
| | WP_psm | Phasenmodus (Auto / 1-phasig / 3-phasig) | ✅ |
| | WP_amp | Ladestrom gesetzt (6–32 A) | ✅ |
| | WP_acu | Ladestrom erlaubt (A) | |
| | WP_modelStatus | Modellstatus (Integer mit Variablenprofil) | |
| | WP_err | Fehlerstatus (Integer mit Variablenprofil) | |
| Energie | WP_wh | Session-Energie (Wh) | |
| | WP_eto | Gesamtenergie (Wh) | |
| | WP_etop | Gesamtenergie personalisiert (Wh) | |
| Messwerte | WP_nrg_ptotal | Ladeleistung gesamt (W) | |
| | WP_nrg_pl1 / pl2 / pl3 | Leistung L1 / L2 / L3 (W) | |

### Steuerbare Variablen (WebFront)

| Variable | Beschreibung | Wertebereich |
|---|---|---|
| WP_frc | Force-Modus | 0=Neutral, 1=Aus, 2=Ein |
| WP_lmo | Lademodus | 3=Standard, 4=ECO/PV, 5=AutoStop |
| WP_psm | Phasenmodus | 0=Auto, 1=1-phasig, 2=3-phasig |
| WP_amp | Ladestrom | 6–32 A |
| WP_ama | Max. Strom-Limit | 6–32 A |
| WP_ust | Kabelverriegelung | 0=Normal, 1=Auto, 2=Immer |
| WP_al1–al5 | Adapter-Stromstufen | 6–32 A |
| WP_fte | Next Trip Energie | Wh |
| WP_ftt | Next Trip Abfahrtszeit | HH:MM |
| WP_ocppe | OCPP aktiviert | an/aus |
| WP_ocppu | OCPP Server-URL | String |
| WP_ocpph | OCPP Heartbeat | Sekunden |
| WP_wan | Hotspot SSID | String |
| WP_wak | Hotspot Passwort | String |
| WP_sch_*_ctrl | Ladeplaner Modus | 0–4 |

---

## 💻 Skript-Funktionen

### Laden steuern

```php
// Lademodus setzen (3=Standard, 4=ECO/PV-Überschuss, 5=AutoStop)
WP_SetLademodus($id, 4);

// Force-Modus (0=Neutral, 1=Aus, 2=Ein)
WP_SetForceModus($id, 2);

// Ladestrom setzen (6–32 A, wird automatisch an Variante begrenzt)
WP_SetLadestrom($id, 16);

// Max. Strom-Limit
WP_SetMaxCurrent($id, 20);

// Phasenmodus (0=Auto, 1=1-phasig, 2=3-phasig)
WP_SetPhasenmodus($id, 0);

// Kabelverriegelung (0=Normal, 1=Auto Entriegeln, 2=Immer verriegelt)
WP_SetUnlockSetting($id, 0);

// Schnell-Befehle
WP_StartLaden($id);   // = Force Ein
WP_StopLaden($id);    // = Force Aus
```

### Stromstufen

```php
// Adapter-Stufe setzen (Stufe 1–5, Wert 6–32 A)
WP_SetAdapterLevel($id, 1, 6);
WP_SetAdapterLevel($id, 2, 10);
WP_SetAdapterLevel($id, 3, 13);
WP_SetAdapterLevel($id, 4, 16);
WP_SetAdapterLevel($id, 5, 20);
```

### Next Trip

```php
// Minimale Ladeenergie in Wh
WP_SetNextTripEnergy($id, 20000);

// Abfahrtszeit (Format HH:MM)
WP_SetNextTripTime($id, '07:00');
```

### OCPP

```php
WP_SetOCPPEnabled($id, true);
WP_SetOCPPUrl($id, 'ws://server/ocpp');
WP_SetOCPPHeartbeat($id, 60);
```

### Hotspot

```php
WP_SetHotspotName($id, 'MeinWattpilot');
WP_SetHotspotPassword($id, 'geheim');
```

### Ladeplaner

```php
// Zeitplan setzen
// Parameter: $id, Tag, Modus, Slot1-Start, Slot1-Ende, Slot2-Start, Slot2-Ende
WP_SetSchedule($id, 'week', 1, '22:00', '06:00', '00:00', '00:00');
// Tage: 'week' = Mo–Fr, 'saturday' = Sa, 'sunday' = So
// Modus: 0=Deaktiviert, 1=Erlauben, 2=Sperren, 3=Erlauben+PV, 4=Sperren+PV
```

### PV-Einstellungen (generisch)
```php
WP_SetWPValue($id, 'fst', 1400);  // Startleistung 1400W
WP_SetWPValue($id, 'fup', true);  // PV-Überschuss aktivieren
WP_SetWPValue($id, 'po', -300);   // Prio-Offset
WP_SetWPValue($id, 'sh', 200);    // Stop-Hysterese
WP_SetWPValue($id, 'psh', 500);   // Phasen-Hysterese
WP_SetWPValue($id, 'spl3', 4200); // 3-Phasen Schwelle
```

### System

```php
// Status manuell aktualisieren (fordert FullStatus vom Splitter an)
WP_UpdateStatus($id);

// Wattpilot neu starten
WP_Reboot($id);

// Beliebigen API-Key setzen
WP_SetWPValue($id, 'key', value);
```

### Splitter-Funktionen

```php
// Verbindung manuell neu aufbauen
WPSP_Reconnect($id);

// Verbindung prüfen
WPSP_CheckConnection($id);

// Verbindungstest (Ausgabe)
WPSP_TestConnection($id);
```

________________________________________
## 🏗️ Architektur
```php
┌──────────────────────────────────────────────────────┐ 
│  Fronius Wattpilot (Hardware)                        │ 
│  ws://<IP>/ws                                        │ 
└────────────────────────┬─────────────────────────────┘ 
                         │ WebSocket (Push) 
┌────────────────────────▼─────────────────────────────┐ 
│  WebSocket Client (IPS I/O)                          │ 
└────────────────────────┬─────────────────────────────┘ 
                         │ 
┌────────────────────────▼─────────────────────────────┐ 
│  Wattpilot Splitter (WPSP)                           │ 
│  • Authentifizierung (bcrypt + SHA256 + HMAC)        │ 
│  • Status-Verwaltung (FullStatus + DeltaBuffer)      │ 
│  • Reconnect mit exponentiellem Backoff              │ 
│  • Timer: gebündelte Updates an Children             │ 
│  • Response auf Steuerbefehle → sofort pushen        │ 
│  • Frame-Buffer für fragmentierte Nachrichten        │ 
└────────────────────────┬─────────────────────────────┘ 
                         │ 
┌────────────────────────▼─────────────────────────────┐ 
│  Wattpilot Flex (WP)                                 │ 
│  • Variablen-Erstellung (mit Dummy-Kategorien)       │ 
│  • Optionale Variablengruppen                        │ 
│  • Steuerung (RequestAction → WebFront)              │ 
│  • Archivierung (Standard + Zähler)                  │ 
│  • Ladestrom-Begrenzung nach Variante                │ 
│  • Variablenprofile für Status-Werte                 │ 
└──────────────────────────────────────────────────────┘ 
```
________________________________________
## Datenfluss

Quelle	Verhalten	Latenz
deltaStatus (Messwerte)	Im DeltaBuffer gesammelt → Timer pusht gebündelt	max. UpdateInterval
response (Steuerbefehl)	Sofort an Device gepusht	~0 ms
fullStatus (Verbindungsaufbau)	Sofort an Device gepusht	~0 ms

________________________________________
## 📁 Modulstruktur
```php
WattpilotFlex/
├── library.json
├── README.md
├── LICENSE
├── WattpilotSplitter/
│   ├── module.json
│   ├── module.php
│   └── form.json
└── WattpilotFlex/
    ├── module.json
    ├── module.php
    └── form.json
```
________________________________________
## ⚠️ Hinweise
```php
•	Das Variablen-Aktualisierungsintervall im Splitter (Standard: 60s) bündelt eingehende Messwerte. Steuerbefehle werden immer sofort zurückgemeldet.
•	Bei der 11 kW Variante wird der Ladestrom automatisch auf max. 16 A begrenzt (Profil + Befehl).
•	Variablen werden nur bei tatsächlicher Wertänderung geschrieben – unnötige WebFront-Aktualisierungen und Archiv-Einträge werden vermieden.
•	WP_nrg_ptotal wird bei FullStatus immer geschrieben (auch bei gleichem Wert), damit Archive einen Datenpunkt erhalten.
•	Status-Variablen (WP_car, WP_err, WP_modelStatus) verwenden Integer-Werte mit Variablenprofilen für die Textdarstellung.
•	Die Stromstufen (WP_al1–WP_al5) sind direkt im WebFront per Slider steuerbar.
```
________________________________________
## 🙏 Danksagung
Das WebSocket-Protokoll des Fronius Wattpilot ist nicht offiziell dokumentiert.
Die Implementierung basiert auf der Vorarbeit folgender Projekte:
- [mk-maddin/wattpilot](https://github.com/mk-maddin/wattpilot) – Python-Implementierung
- [joscha82/wattpilot](https://github.com/joscha82/wattpilot/pkgs/container/wattpilot) – Container-Integration
________________________________________
## 👨‍💻 Entwicklung
Entwickelt von Kelevra26 (Kelevra-0815) mit Unterstützung von KI.
Die KI unterstützte bei WebSocket-Protokoll, PHP-Architektur und Dokumentation.
Idee, Anforderungen und Tests stammen vom Autor.
________________________________________
## ☕ Unterstützung

Gefällt dir dieses Modul? Unterstütze die Entwicklung:

[![PayPal Spende](https://img.shields.io/badge/PayPal-Spenden-blue?logo=paypal)](https://www.paypal.com/donate/?hosted_button_id=4HVXKG7QKF4WW)
________________________________________
---
## Lizenz
MIT License – siehe [LICENSE](LICENSE)
