<?php
declare(strict_types=1);

class WattpilotFlex extends IPSModule
{
    private const STATUS_OK      = 102;
    private const STATUS_OFFLINE = 201;

    private const SPLITTER_SEND_GUID = '{1E4A7F3B-8C2D-4E5F-9A6B-7C8D9E0F1A2B}';
    private const SPLITTER_GUID      = '{D5E8F3A2-7B4C-4E9D-8A1F-6C3B5D7E9F0A}';
    private const DUMMY_GUID         = '{485D0419-BE97-4548-AA9C-C083EB82E61E}';
    private const ARCHIVE_GUID       = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';

    private const ACTIONABLE_IDENTS = ['WP_frc', 'WP_lmo', 'WP_psm', 'WP_amp'];

    private const ALWAYS_UPDATE_ON_FULL = ['WP_nrg_ptotal'];

    // Optionale Gruppen: Property-Name => [Idents]
    private const OPTIONAL_GROUPS = [
        'ShowCurrent'     => ['WP_nrg_il1', 'WP_nrg_il2', 'WP_nrg_il3'],
        'ShowVoltage'     => ['WP_nrg_ul1', 'WP_nrg_ul2', 'WP_nrg_ul3'],
        'ShowTemperature' => ['WP_tma1', 'WP_tma2', 'WP_tma3'],
        'ShowPhaseDetail' => ['WP_pha_l1', 'WP_pha_l2', 'WP_pha_l3', 'WP_pnp'],
        'ShowSystem'      => ['WP_fwv', 'WP_sse', 'WP_var', 'WP_rbc', 'WP_rbt', 'WP_rssi'],
        'ShowExtras'      => ['WP_adi', 'WP_trx', 'WP_dwo', 'WP_cbl'],
    ];

    // Archivierung: Property => [Ident, AggregationType]
    // AggregationType: 0 = Standard, 1 = Zähler
    private const ARCHIVE_CONFIG = [
        'LogPower'  => ['WP_nrg_ptotal', 0],
        'LogEnergy' => ['WP_eto', 1],
    ];

    // [ident, name, type, profile, catPath, position]
    private const VARIABLES = [
        ['WP_car',         'Fahrzeugstatus',            VARIABLETYPE_STRING,  '',               'Fahrzeug & Laden',    1],
        ['WP_alw',         'Laden erlaubt',             VARIABLETYPE_BOOLEAN, '~Switch',        'Fahrzeug & Laden',    2],
        ['WP_frc',         'Force-Modus',               VARIABLETYPE_INTEGER, 'WP.ForceModus',  'Fahrzeug & Laden',    3],
        ['WP_lmo',         'Lademodus',                 VARIABLETYPE_INTEGER, 'WP.Lademodus',   'Fahrzeug & Laden',    4],
        ['WP_psm',         'Phasenmodus',               VARIABLETYPE_INTEGER, 'WP.Phasenmodus', 'Fahrzeug & Laden',    5],
        ['WP_amp',         'Ladestrom gesetzt (A)',     VARIABLETYPE_INTEGER, 'WP.Ladestrom',   'Fahrzeug & Laden',    6],
        ['WP_acu',         'Ladestrom erlaubt (A)',     VARIABLETYPE_INTEGER, 'WP.Ladestrom',   'Fahrzeug & Laden',    7],
        ['WP_pnp',         'Aktive Phasen',             VARIABLETYPE_INTEGER, '',               'Fahrzeug & Laden',    8],
        ['WP_pha_l1',      'Phase L1 aktiv',            VARIABLETYPE_BOOLEAN, '',               'Fahrzeug & Laden',    9],
        ['WP_pha_l2',      'Phase L2 aktiv',            VARIABLETYPE_BOOLEAN, '',               'Fahrzeug & Laden',   10],
        ['WP_pha_l3',      'Phase L3 aktiv',            VARIABLETYPE_BOOLEAN, '',               'Fahrzeug & Laden',   11],
        ['WP_modelStatus', 'Modellstatus',              VARIABLETYPE_STRING,  '',               'Fahrzeug & Laden',   12],
        ['WP_err',         'Fehlerstatus',              VARIABLETYPE_STRING,  '',               'Fahrzeug & Laden',   13],
        ['WP_cbl',         'Kabel-Limit (A)',           VARIABLETYPE_INTEGER, 'WP.Ladestrom',   'Fahrzeug & Laden',   14],
        ['WP_adi',         '16A Adapter aktiv',         VARIABLETYPE_BOOLEAN, '',               'Fahrzeug & Laden',   15],
        ['WP_trx',         'Transaktion',               VARIABLETYPE_INTEGER, '',               'Fahrzeug & Laden',   16],
        ['WP_dwo',         'Energie-Limit (Wh)',        VARIABLETYPE_FLOAT,   'WP.Energie',     'Fahrzeug & Laden',   17],
        ['WP_wh',          'Session-Energie (Wh)',      VARIABLETYPE_FLOAT,   'WP.Energie',     'Energie',             1],
        ['WP_eto',         'Gesamtenergie (Wh)',        VARIABLETYPE_FLOAT,   'WP.Energie',     'Energie',             2],
        ['WP_etop',        'Gesamtenergie pers. (Wh)',  VARIABLETYPE_FLOAT,   'WP.Energie',     'Energie',             3],
        ['WP_nrg_ptotal',  'Ladeleistung gesamt (W)',   VARIABLETYPE_FLOAT,   '~Watt',          'Messwerte.Leistung',  1],
        ['WP_nrg_pl1',     'Leistung L1 (W)',           VARIABLETYPE_FLOAT,   '~Watt',          'Messwerte.Leistung',  2],
        ['WP_nrg_pl2',     'Leistung L2 (W)',           VARIABLETYPE_FLOAT,   '~Watt',          'Messwerte.Leistung',  3],
        ['WP_nrg_pl3',     'Leistung L3 (W)',           VARIABLETYPE_FLOAT,   '~Watt',          'Messwerte.Leistung',  4],
        ['WP_nrg_il1',     'Strom L1 (A)',              VARIABLETYPE_FLOAT,   '~Ampere.16',     'Messwerte.Strom',     1],
        ['WP_nrg_il2',     'Strom L2 (A)',              VARIABLETYPE_FLOAT,   '~Ampere.16',     'Messwerte.Strom',     2],
        ['WP_nrg_il3',     'Strom L3 (A)',              VARIABLETYPE_FLOAT,   '~Ampere.16',     'Messwerte.Strom',     3],
        ['WP_nrg_ul1',     'Spannung L1 (V)',           VARIABLETYPE_FLOAT,   '~Volt',          'Messwerte.Spannung',  1],
        ['WP_nrg_ul2',     'Spannung L2 (V)',           VARIABLETYPE_FLOAT,   '~Volt',          'Messwerte.Spannung',  2],
        ['WP_nrg_ul3',     'Spannung L3 (V)',           VARIABLETYPE_FLOAT,   '~Volt',          'Messwerte.Spannung',  3],
        ['WP_fwv',         'Firmware Version',          VARIABLETYPE_STRING,  '',               'System',              1],
        ['WP_sse',         'Seriennummer',              VARIABLETYPE_STRING,  '',               'System',              2],
        ['WP_var',         'Variante (kW)',             VARIABLETYPE_INTEGER, '',               'System',              3],
        ['WP_rbc',         'Neustarts',                 VARIABLETYPE_INTEGER, '',               'System',              4],
        ['WP_rbt',         'Laufzeit (h)',              VARIABLETYPE_FLOAT,   '',               'System',              5],
        ['WP_rssi',        'WLAN Signal (dBm)',         VARIABLETYPE_INTEGER, '',               'System',              6],
        ['WP_tma1',        'Temperatur 1 (°C)',         VARIABLETYPE_FLOAT,   '~Temperature',   'System',              7],
        ['WP_tma2',        'Temperatur 2 (°C)',         VARIABLETYPE_FLOAT,   '~Temperature',   'System',              8],
        ['WP_tma3',        'Temperatur 3 (°C)',         VARIABLETYPE_FLOAT,   '~Temperature',   'System',              9],
    ];

    private const CATEGORY_POSITIONS = [
        'Fahrzeug & Laden' => 1,
        'Energie'          => 2,
        'Messwerte'        => 3,
        'System'           => 4,
    ];

    // ══════════════════════════════════════════════════════════════════════════
    // IPS Lifecycle
    // ══════════════════════════════════════════════════════════════════════════

    public function Create()
    {
        parent::Create();
        $this->SetBuffer('IdentCache', '');
        $this->ConnectParent(self::SPLITTER_GUID);

        // Optionale Gruppen (Standard: AUS)
        $this->RegisterPropertyBoolean('ShowCurrent', false);
        $this->RegisterPropertyBoolean('ShowVoltage', false);
        $this->RegisterPropertyBoolean('ShowTemperature', false);
        $this->RegisterPropertyBoolean('ShowPhaseDetail', false);
        $this->RegisterPropertyBoolean('ShowSystem', false);
        $this->RegisterPropertyBoolean('ShowExtras', false);

        // Archivierung (Standard: AUS)
        $this->RegisterPropertyBoolean('LogPower', false);
        $this->RegisterPropertyBoolean('LogEnergy', false);
    }

    public function Destroy()
    {
        $instances = IPS_GetInstanceListByModuleID('{8F5E8A3C-7D2A-4B1E-9F6C-2E4A8B3D5F7E}');
        if (count($instances) <= 1) {
            $profiles = ['WP.Lademodus', 'WP.ForceModus', 'WP.Phasenmodus', 'WP.Ladestrom', 'WP.Energie'];
            foreach ($profiles as $profile) {
                if (@IPS_VariableProfileExists($profile)) {
                    @IPS_DeleteVariableProfile($profile);
                }
            }
        }
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();
        $this->SetBuffer('IdentCache', '');

        $this->createProfiles();
        $this->createActionableVariables();
        $this->createVariableStructure();
        $this->removeDisabledVariables();
        $this->configureArchiving();

        $this->SetStatus(self::STATUS_OFFLINE);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Optionale Gruppen
    // ══════════════════════════════════════════════════════════════════════════

    private function isIdentEnabled(string $ident): bool
    {
        foreach (self::OPTIONAL_GROUPS as $property => $idents) {
            if (in_array($ident, $idents, true)) {
                return $this->ReadPropertyBoolean($property);
            }
        }
        return true;
    }

    private function removeDisabledVariables(): void
    {
        foreach (self::OPTIONAL_GROUPS as $property => $idents) {
            if (!$this->ReadPropertyBoolean($property)) {
                foreach ($idents as $ident) {
                    $id = $this->findByIdentRecursive($ident, $this->InstanceID);
                    if ($id > 0) {
                        IPS_DeleteVariable($id);
                    }
                }
            }
        }
        $this->removeEmptyCategories($this->InstanceID);
    }

    private function removeEmptyCategories(int $parentId): void
    {
        foreach (IPS_GetChildrenIDs($parentId) as $childId) {
            $obj = IPS_GetObject($childId);
            if ($obj['ObjectType'] === 1 && strpos($obj['ObjectIdent'], 'CAT_') === 0) {
                $this->removeEmptyCategories($childId);
                if (count(IPS_GetChildrenIDs($childId)) === 0) {
                    IPS_DeleteInstance($childId);
                }
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Archivierung konfigurieren
    // ══════════════════════════════════════════════════════════════════════════

    private function configureArchiving(): void
    {
        $archiveId = $this->getArchiveId();
        if ($archiveId === 0) {
            $this->SendDebug('Archive', 'Kein Archive Control gefunden', 0);
            return;
        }

        foreach (self::ARCHIVE_CONFIG as $property => [$ident, $aggregationType]) {
            $enabled = $this->ReadPropertyBoolean($property);
            $varId   = $this->findByIdentRecursive($ident, $this->InstanceID);

            if ($varId <= 0) continue;

            if ($enabled) {
                AC_SetLoggingStatus($archiveId, $varId, true);
                AC_SetAggregationType($archiveId, $varId, $aggregationType);
                $this->SendDebug('Archive', "$ident Logging aktiviert (Typ: $aggregationType)", 0);
            } else {
                AC_SetLoggingStatus($archiveId, $varId, false);
                $this->SendDebug('Archive', "$ident Logging deaktiviert", 0);
            }
        }

        // Änderungen im Archive übernehmen
        IPS_ApplyChanges($archiveId);
    }

    private function getArchiveId(): int
    {
        $ids = IPS_GetInstanceListByModuleID(self::ARCHIVE_GUID);
        if (count($ids) > 0) {
            return $ids[0];
        }
        return 0;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Steuerbare Variablen: RegisterVariable + EnableAction, dann verschieben
    // ══════════════════════════════════════════════════════════════════════════

    private function createActionableVariables(): void
    {
        foreach (self::ACTIONABLE_IDENTS as $ident) {
            $existingId = $this->findByIdentRecursive($ident, $this->InstanceID);
            if ($existingId > 0) {
                $obj = IPS_GetObject($existingId);
                if ((int)$obj['ParentID'] !== $this->InstanceID) {
                    IPS_SetParent($existingId, $this->InstanceID);
                }
            }

            foreach (self::VARIABLES as $varDef) {
                if ($varDef[0] !== $ident) continue;
                $name    = $varDef[1];
                $type    = $varDef[2];
                $profile = $varDef[3];

                switch ($type) {
                    case VARIABLETYPE_BOOLEAN:
                        $this->RegisterVariableBoolean($ident, $name, $profile, 0);
                        break;
                    case VARIABLETYPE_INTEGER:
                        $this->RegisterVariableInteger($ident, $name, $profile, 0);
                        break;
                    case VARIABLETYPE_FLOAT:
                        $this->RegisterVariableFloat($ident, $name, $profile, 0);
                        break;
                    case VARIABLETYPE_STRING:
                        $this->RegisterVariableString($ident, $name, $profile, 0);
                        break;
                }
                $this->EnableAction($ident);
                break;
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // RequestAction
    // ══════════════════════════════════════════════════════════════════════════

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'WP_frc':
                $this->SetForceModus((int)$Value);
                break;
            case 'WP_lmo':
                $this->SetLademodus((int)$Value);
                break;
            case 'WP_psm':
                $this->SetPhasenmodus((int)$Value);
                break;
            case 'WP_amp':
                $this->SetLadestrom((int)$Value);
                break;
            default:
                $this->SendDebug('RequestAction', "Unbekannter Ident: $Ident", 0);
                break;
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Datenempfang vom Splitter
    // ══════════════════════════════════════════════════════════════════════════

    public function ReceiveData($JSONString)
    {
        $data   = json_decode($JSONString, true);
        $buffer = json_decode($data['Buffer'] ?? '{}', true);

        $type   = $buffer['type'] ?? '';
        $status = $buffer['status'] ?? [];

        if ($type === 'fullStatus' || $type === 'deltaStatus') {
            $this->SetStatus(self::STATUS_OK);
            $forceUpdate = ($type === 'fullStatus');
            $this->writeStatusToVariables($status, $forceUpdate);
        }

        return '';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Öffentliche Steuerfunktionen
    // ══════════════════════════════════════════════════════════════════════════

    public function SetLademodus(int $modus): bool
    {
        return $this->sendCommand('lmo', $modus);
    }

    public function SetForceModus(int $force): bool
    {
        return $this->sendCommand('frc', $force);
    }

    public function SetLadestrom(int $amp): bool
    {
        $maxAmp = $this->getMaxAmp();
        return $this->sendCommand('amp', max(6, min($maxAmp, $amp)));
    }

    public function SetPhasenmodus(int $psm): bool
    {
        return $this->sendCommand('psm', $psm);
    }

    public function StartLaden(): bool
    {
        return $this->sendCommand('frc', 2);
    }

    public function StopLaden(): bool
    {
        return $this->sendCommand('frc', 1);
    }

    public function SetWPValue(string $key, $value): bool
    {
        return $this->sendCommand($key, $value);
    }

    public function UpdateStatus(): void
    {
        @$this->SendDataToParent(json_encode([
            'DataID'   => self::SPLITTER_SEND_GUID,
            'Function' => 'ForceUpdate',
            'Payload'  => '',
        ]));
        $this->SendDebug('UpdateStatus', 'ForceUpdate angefordert', 0);
    }

    private function sendCommand(string $key, $value): bool
    {
        $msg = [
            'type'      => 'setValue',
            'requestId' => time(),
            'key'       => $key,
            'value'     => $value,
        ];

        $result = @$this->SendDataToParent(json_encode([
            'DataID'   => self::SPLITTER_SEND_GUID,
            'Function' => 'SendToWattpilot',
            'Payload'  => json_encode($msg),
        ]));

        $this->SendDebug('CMD', "setValue '$key' = " . json_encode($value), 0);

        if ($result !== false && $result !== '') {
            $res = json_decode($result, true);
            return ($res['status'] ?? '') === 'ok';
        }
        return false;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Max. Ladestrom basierend auf Variante
    // ══════════════════════════════════════════════════════════════════════════

    private function getMaxAmp(): int
    {
        $varId = $this->findByIdent('WP_var');
        if ($varId > 0) {
            $variant = @GetValueInteger($varId);
            if ($variant === 11) return 16;
        }
        return 32;
    }

    private function updateLadestromProfile(int $variant): void
    {
        $maxAmp = ($variant === 11) ? 16 : 32;
        if (IPS_VariableProfileExists('WP.Ladestrom')) {
            IPS_SetVariableProfileValues('WP.Ladestrom', 6, $maxAmp, 1);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen schreiben
    // ══════════════════════════════════════════════════════════════════════════

    private function writeStatusToVariables(array $s, bool $forceUpdate = false): void
    {
        $carMap = [1 => 'Bereit', 2 => 'Lädt', 3 => 'Warte auf Auto', 4 => 'Fertig', 5 => 'Fehler'];
        $errMap = [
            0 => 'Kein Fehler', 1 => 'FiAc', 2 => 'FiDc', 3 => 'Phase',
            4 => 'Überspannung', 5 => 'Überstrom', 6 => 'Diode',
            13 => 'Übertemperatur', 14 => 'NoComm',
        ];
        $modMap = [
            0 => 'No Data', 3 => 'Force Ein', 4 => 'Force Aus',
            5 => 'Zeitplan', 6 => 'Energie-Limit', 7 => 'Awattar',
            12 => 'PV Überschuss', 15 => 'Fallback',
        ];

        if (isset($s['car']))  $this->setVar('WP_car', $carMap[$s['car']] ?? 'Unbekannt', $forceUpdate);
        if (isset($s['alw']))  $this->setVar('WP_alw', (bool)$s['alw'], $forceUpdate);
        if (isset($s['frc']))  $this->setVar('WP_frc', (int)$s['frc'], $forceUpdate);
        if (isset($s['lmo']))  $this->setVar('WP_lmo', (int)$s['lmo'], $forceUpdate);
        if (isset($s['psm']))  $this->setVar('WP_psm', (int)$s['psm'], $forceUpdate);
        if (isset($s['amp']))  $this->setVar('WP_amp', (int)$s['amp'], $forceUpdate);
        if (isset($s['acu']))  $this->setVar('WP_acu', (int)$s['acu'], $forceUpdate);
        if (isset($s['pnp']))  $this->setVar('WP_pnp', (int)$s['pnp'], $forceUpdate);
        if (isset($s['modelStatus'])) $this->setVar('WP_modelStatus', $modMap[$s['modelStatus']] ?? '', $forceUpdate);
        if (isset($s['err']))  $this->setVar('WP_err', $errMap[$s['err']] ?? 'Unbekannt', $forceUpdate);
        if (isset($s['cbl']))  $this->setVar('WP_cbl', (int)$s['cbl'], $forceUpdate);
        if (isset($s['adi']))  $this->setVar('WP_adi', (bool)$s['adi'], $forceUpdate);
        if (isset($s['trx']))  $this->setVar('WP_trx', (int)$s['trx'], $forceUpdate);
        if (isset($s['dwo']))  $this->setVar('WP_dwo', (float)$s['dwo'], $forceUpdate);
        if (isset($s['wh']))   $this->setVar('WP_wh', round((float)$s['wh'], 1), $forceUpdate);
        if (isset($s['eto']))  $this->setVar('WP_eto', round((float)$s['eto'], 0), $forceUpdate);
        if (isset($s['etop'])) $this->setVar('WP_etop', round((float)$s['etop'], 0), $forceUpdate);
        if (isset($s['fwv']))  $this->setVar('WP_fwv', (string)$s['fwv'], $forceUpdate);
        if (isset($s['sse']))  $this->setVar('WP_sse', (string)$s['sse'], $forceUpdate);
        if (isset($s['var'])) {
            $this->setVar('WP_var', (int)$s['var'], $forceUpdate);
            $this->updateLadestromProfile((int)$s['var']);
        }
        if (isset($s['rbc']))  $this->setVar('WP_rbc', (int)$s['rbc'], $forceUpdate);
        if (isset($s['rbt']))  $this->setVar('WP_rbt', round($s['rbt'] / 3600000, 1), $forceUpdate);
        if (isset($s['rssi'])) $this->setVar('WP_rssi', (int)$s['rssi'], $forceUpdate);

        if (isset($s['pha'])) {
            $pha = $s['pha'];
            $this->setVar('WP_pha_l1', (bool)($pha[3] ?? false), $forceUpdate);
            $this->setVar('WP_pha_l2', (bool)($pha[4] ?? false), $forceUpdate);
            $this->setVar('WP_pha_l3', (bool)($pha[5] ?? false), $forceUpdate);
        }

        if (isset($s['nrg'])) {
            $nrg = $s['nrg'];
            $this->setVar('WP_nrg_ptotal', round((float)($nrg[11] ?? 0), 1), $forceUpdate);
            $this->setVar('WP_nrg_pl1',   round((float)($nrg[7] ?? 0), 1), $forceUpdate);
            $this->setVar('WP_nrg_pl2',   round((float)($nrg[8] ?? 0), 1), $forceUpdate);
            $this->setVar('WP_nrg_pl3',   round((float)($nrg[9] ?? 0), 1), $forceUpdate);
            $this->setVar('WP_nrg_il1',   round((float)($nrg[4] ?? 0), 2), $forceUpdate);
            $this->setVar('WP_nrg_il2',   round((float)($nrg[5] ?? 0), 2), $forceUpdate);
            $this->setVar('WP_nrg_il3',   round((float)($nrg[6] ?? 0), 2), $forceUpdate);
            $this->setVar('WP_nrg_ul1',   round((float)($nrg[0] ?? 0), 1), $forceUpdate);
            $this->setVar('WP_nrg_ul2',   round((float)($nrg[1] ?? 0), 1), $forceUpdate);
            $this->setVar('WP_nrg_ul3',   round((float)($nrg[2] ?? 0), 1), $forceUpdate);
        }

        if (isset($s['tma'])) {
            $tma = $s['tma'];
            foreach ([2 => 1, 3 => 2, 4 => 3] as $idx => $nr) {
                if (isset($tma[$idx]) && $tma[$idx] !== null) {
                    $this->setVar("WP_tma{$nr}", round((float)$tma[$idx], 1), $forceUpdate);
                }
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen-Zugriff
    // ══════════════════════════════════════════════════════════════════════════

    private function setVar(string $ident, $value, bool $forceUpdate = false): void
    {
        $id = $this->findByIdent($ident);
        if ($id <= 0) return;

        // ALWAYS_UPDATE_ON_FULL: Bei fullStatus immer schreiben
        if ($forceUpdate && in_array($ident, self::ALWAYS_UPDATE_ON_FULL, true)) {
            SetValue($id, $value);
            return;
        }

        if (GetValue($id) !== $value) {
            SetValue($id, $value);
        }
    }

    private function findByIdent(string $ident): int
    {
        $cacheJson = $this->GetBuffer('IdentCache');
        $cache = $cacheJson !== '' ? json_decode($cacheJson, true) : [];

        if (isset($cache[$ident])) {
            if ($cache[$ident] === 0) return 0;
            if (@IPS_ObjectExists($cache[$ident])) return $cache[$ident];
            unset($cache[$ident]);
        }

        if (!$this->isIdentEnabled($ident)) {
            $cache[$ident] = 0;
            $this->SetBuffer('IdentCache', json_encode($cache));
            return 0;
        }

        $id = $this->findByIdentRecursive($ident, $this->InstanceID);
        $cache[$ident] = $id;
        $this->SetBuffer('IdentCache', json_encode($cache));
        return $id;
    }

    private function findByIdentRecursive(string $ident, int $parentId): int
    {
        foreach (IPS_GetChildrenIDs($parentId) as $childId) {
            $obj = IPS_GetObject($childId);
            if ($obj['ObjectIdent'] === $ident) return $childId;
            if ($obj['ObjectType'] === 0 || $obj['ObjectType'] === 1) {
                $found = $this->findByIdentRecursive($ident, $childId);
                if ($found > 0) return $found;
            }
        }
        return 0;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Profile
    // ══════════════════════════════════════════════════════════════════════════

    private function createProfiles(): void
    {
        if ($this->createProfileIfNotExists('WP.Lademodus', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.Lademodus', 3, 'Standard', '', 0x00AA00);
            IPS_SetVariableProfileAssociation('WP.Lademodus', 4, 'ECO / PV-Überschuss', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.Lademodus', 5, 'AutoStop', '', 0x0055FF);
        }
        if ($this->createProfileIfNotExists('WP.ForceModus', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.ForceModus', 0, 'Neutral', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ForceModus', 1, 'Aus', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ForceModus', 2, 'Ein', '', 0x00CC00);
        }
        if ($this->createProfileIfNotExists('WP.Phasenmodus', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.Phasenmodus', 0, 'Auto', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.Phasenmodus', 1, '1-phasig', '', 0x0055FF);
            IPS_SetVariableProfileAssociation('WP.Phasenmodus', 2, '3-phasig', '', 0x00AA00);
        }
        if ($this->createProfileIfNotExists('WP.Ladestrom', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileValues('WP.Ladestrom', 6, 32, 1);
            IPS_SetVariableProfileText('WP.Ladestrom', '', ' A');
        }
        if ($this->createProfileIfNotExists('WP.Energie', VARIABLETYPE_FLOAT)) {
            IPS_SetVariableProfileValues('WP.Energie', 0, 0, 0);
            IPS_SetVariableProfileText('WP.Energie', '', ' Wh');
            IPS_SetVariableProfileDigits('WP.Energie', 1);
        }
    }

    private function createProfileIfNotExists(string $name, int $type): bool
    {
        if (!IPS_VariableProfileExists($name)) {
            IPS_CreateVariableProfile($name, $type);
            return true;
        }
        return false;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen-Struktur
    // ══════════════════════════════════════════════════════════════════════════

    private function createVariableStructure(): void
    {
        $cats = [];
        foreach (self::VARIABLES as [$ident, $name, $type, $profile, $catPath, $position]) {
            if (!$this->isIdentEnabled($ident)) {
                continue;
            }

            $parts    = explode('.', $catPath);
            $parentId = $this->InstanceID;
            $catKey   = '';
            $depth    = 0;
            foreach ($parts as $part) {
                $catKey .= 'CAT_' . preg_replace('/[^a-zA-Z0-9]/', '_', $part);
                if (!array_key_exists($catKey, $cats)) {
                    $id = @IPS_GetObjectIDByIdent($catKey, $parentId);
                    if ($id === false || $id <= 0) {
                        $id = IPS_CreateInstance(self::DUMMY_GUID);
                        IPS_SetParent($id, $parentId);
                        IPS_SetIdent($id, $catKey);
                        IPS_SetName($id, $part);
                    }
                    if ($depth === 0 && isset(self::CATEGORY_POSITIONS[$part])) {
                        IPS_SetPosition($id, self::CATEGORY_POSITIONS[$part]);
                    }
                    $cats[$catKey] = (int)$id;
                }
                $parentId = $cats[$catKey];
                $depth++;
            }
            $this->ensureVariable($ident, $name, $type, $profile, $parentId, $position);
        }
    }

    private function ensureVariable(string $ident, string $name, int $type, string $profile, int $parentId, int $position): void
    {
        // Steuerbare Variablen: per RegisterVariable erstellt → nur verschieben
        if (in_array($ident, self::ACTIONABLE_IDENTS, true)) {
            $id = @$this->GetIDForIdent($ident);
            if ($id !== false && $id > 0) {
                IPS_SetParent($id, $parentId);
                IPS_SetPosition($id, $position);
                return;
            }
        }

        // Nicht-steuerbare Variablen
        $id = @IPS_GetObjectIDByIdent($ident, $parentId);
        if ($id === false || $id <= 0) {
            $id = $this->findByIdentRecursive($ident, $this->InstanceID);
        }

        if ($id !== false && $id > 0) {
            if (IPS_GetVariable($id)['VariableType'] !== $type) {
                IPS_DeleteVariable($id);
                $id = false;
            }
        }

        if ($id === false || $id <= 0) {
            $id = IPS_CreateVariable($type);
            IPS_SetIdent($id, $ident);
            IPS_SetName($id, $name);
            if ($profile !== '' && IPS_VariableProfileExists($profile)) {
                if (IPS_GetVariableProfile($profile)['ProfileType'] === $type) {
                    IPS_SetVariableCustomProfile($id, $profile);
                }
            }
        }

        IPS_SetParent($id, $parentId);
        IPS_SetPosition($id, $position);
    }
}