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

    private const ACTIONABLE_IDENTS = [
        'WP_frc', 'WP_lmo', 'WP_psm', 'WP_amp', 'WP_ama', 'WP_ust',
        'WP_sch_week_ctrl', 'WP_sch_satur_ctrl', 'WP_sch_sund_ctrl',
        'WP_fte', 'WP_ftt',
        'WP_ocppe', 'WP_ocppu', 'WP_ocpph', 'WP_ocpps',
        'WP_wan', 'WP_wak',
        'WP_al1', 'WP_al2', 'WP_al3', 'WP_al4', 'WP_al5',
    ];

    private const ALWAYS_UPDATE_ON_FULL = ['WP_nrg_ptotal'];

    private const OPTIONAL_GROUPS = [
        'ShowCurrent'        => ['WP_nrg_il1', 'WP_nrg_il2', 'WP_nrg_il3'],
        'ShowVoltage'        => ['WP_nrg_ul1', 'WP_nrg_ul2', 'WP_nrg_ul3'],
        'ShowPowerFactor'    => ['WP_nrg_pfl1', 'WP_nrg_pfl2', 'WP_nrg_pfl3'],
        'ShowTemperature'    => ['WP_tma1', 'WP_tma2', 'WP_tma3', 'WP_tma4'],
        'ShowFrequency'      => ['WP_fhz'],
        'ShowPhaseDetail'    => ['WP_pha_l1', 'WP_pha_l2', 'WP_pha_l3', 'WP_pnp'],
        'ShowChargeAdvanced' => ['WP_ama', 'WP_mca', 'WP_fsp'],
        'ShowLock'           => ['WP_ust', 'WP_lck', 'WP_ffb', 'WP_cus'],
        'ShowExtras'         => ['WP_adi', 'WP_trx', 'WP_dwo'],
        'ShowCurrentLevels'  => ['WP_al1', 'WP_al2', 'WP_al3', 'WP_al4', 'WP_al5'],
        'ShowNextTrip'       => ['WP_fte', 'WP_ftt'],
        'ShowOCPP'           => ['WP_ocppe', 'WP_ocppu', 'WP_ocpph', 'WP_ocpps'],
        'ShowHotspot'        => ['WP_wan', 'WP_wak'],
        'ShowPV'             => ['WP_pGrid', 'WP_pPv', 'WP_pAkku', 'WP_akkuSOC'],
        'ShowPVConfig'       => ['WP_fst', 'WP_fup', 'WP_po', 'WP_sh', 'WP_psh', 'WP_spl3'],
        'ShowAwattar'        => ['WP_awc', 'WP_awp', 'WP_ful'],
        'ShowScheduler'      => ['WP_sch_week_ctrl', 'WP_sch_week_slots', 'WP_sch_satur_ctrl', 'WP_sch_satur_slots', 'WP_sch_sund_ctrl', 'WP_sch_sund_slots'],
        'ShowSystem'         => ['WP_fwv', 'WP_sse', 'WP_var', 'WP_rbc', 'WP_rbt'],
        'ShowNetwork'        => ['WP_wst', 'WP_wsms', 'WP_rssi', 'WP_host', 'WP_fna'],
    ];

    private const ARCHIVE_CONFIG = [
        'LogPower'  => ['WP_nrg_ptotal', 0],
        'LogEnergy' => ['WP_eto', 1],
    ];

    private const VARIABLES = [
        // ── Fahrzeug & Laden ──────────────────────────────────────────────
        ['WP_car',         'Fahrzeugstatus',            VARIABLETYPE_INTEGER, 'WP.CarState',         'Fahrzeug & Laden',    1],
        ['WP_alw',         'Laden erlaubt',             VARIABLETYPE_BOOLEAN, '~Switch',             'Fahrzeug & Laden',    2],
        ['WP_frc',         'Force-Modus',               VARIABLETYPE_INTEGER, 'WP.ForceModus',       'Fahrzeug & Laden',    3],
        ['WP_lmo',         'Lademodus',                 VARIABLETYPE_INTEGER, 'WP.Lademodus',        'Fahrzeug & Laden',    4],
        ['WP_psm',         'Phasenmodus',               VARIABLETYPE_INTEGER, 'WP.Phasenmodus',      'Fahrzeug & Laden',    5],
        ['WP_amp',         'Ladestrom gesetzt (A)',     VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',    6],
        ['WP_acu',         'Ladestrom erlaubt (A)',     VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',    7],
        ['WP_ama',         'Max. Strom-Limit (A)',      VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',    8],
        ['WP_mca',         'Min. Ladestrom (A)',        VARIABLETYPE_INTEGER, '',                    'Fahrzeug & Laden',   10],
        ['WP_pnp',         'Aktive Phasen',             VARIABLETYPE_INTEGER, '',                    'Fahrzeug & Laden',   11],
        ['WP_pha_l1',      'Phase L1 aktiv',            VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   12],
        ['WP_pha_l2',      'Phase L2 aktiv',            VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   13],
        ['WP_pha_l3',      'Phase L3 aktiv',            VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   14],
        ['WP_fsp',         'Einphasig erzwungen',       VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   15],
        ['WP_modelStatus', 'Modellstatus',              VARIABLETYPE_INTEGER, 'WP.ModelStatus',      'Fahrzeug & Laden',   16],
        ['WP_err',         'Fehlerstatus',              VARIABLETYPE_INTEGER, 'WP.ErrorState',       'Fahrzeug & Laden',   17],
        ['WP_adi',         '16A Adapter aktiv',         VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   18],
        ['WP_trx',         'Transaktion',               VARIABLETYPE_INTEGER, '',                    'Fahrzeug & Laden',   19],
        ['WP_dwo',         'Energie-Limit (Wh)',        VARIABLETYPE_FLOAT,   'WP.Energie',          'Fahrzeug & Laden',   20],
        // ── Stromstufen ───────────────────────────────────────────────────
        ['WP_al1',         'Adapter-Stufe 1 (A)',       VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Stromstufen',         1],
        ['WP_al2',         'Adapter-Stufe 2 (A)',       VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Stromstufen',         2],
        ['WP_al3',         'Adapter-Stufe 3 (A)',       VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Stromstufen',         3],
        ['WP_al4',         'Adapter-Stufe 4 (A)',       VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Stromstufen',         4],
        ['WP_al5',         'Adapter-Stufe 5 (A)',       VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Stromstufen',         5],
        // ── Next Trip ─────────────────────────────────────────────────────
        ['WP_fte',         'Next Trip Energie (Wh)',    VARIABLETYPE_INTEGER, '',                    'Next Trip',           1],
        ['WP_ftt',         'Next Trip Uhrzeit',         VARIABLETYPE_STRING,  '',                    'Next Trip',           2],
        // ── OCPP ──────────────────────────────────────────────────────────
        ['WP_ocppe',       'OCPP aktiviert',            VARIABLETYPE_BOOLEAN, '~Switch',             'OCPP',                1],
        ['WP_ocppu',       'OCPP Server-URL',           VARIABLETYPE_STRING,  '',                    'OCPP',                2],
        ['WP_ocpph',       'OCPP Heartbeat (s)',        VARIABLETYPE_INTEGER, '',                    'OCPP',                3],
        ['WP_ocpps',       'OCPP verbunden',            VARIABLETYPE_BOOLEAN, '~Switch',             'OCPP',                4],
        // ── Hotspot ───────────────────────────────────────────────────────
        ['WP_wan',         'Hotspot Name (SSID)',       VARIABLETYPE_STRING,  '',                    'Hotspot',             1],
        ['WP_wak',         'Hotspot Passwort',          VARIABLETYPE_STRING,  '',                    'Hotspot',             2],
        // ── Verriegelung ──────────────────────────────────────────────────
        ['WP_ust',         'Kabelverriegelung',         VARIABLETYPE_INTEGER, 'WP.UnlockSetting',    'Verriegelung',        1],
        ['WP_lck',         'Effektive Verriegelung',    VARIABLETYPE_STRING,  '',                    'Verriegelung',        2],
        ['WP_ffb',         'Schloss-Feedback',          VARIABLETYPE_STRING,  '',                    'Verriegelung',        3],
        ['WP_cus',         'Kabel-Status',              VARIABLETYPE_STRING,  '',                    'Verriegelung',        4],
        // ── Energie ───────────────────────────────────────────────────────
        ['WP_wh',          'Session-Energie (Wh)',      VARIABLETYPE_FLOAT,   'WP.Energie',          'Messwerte.Energie',   1],
        ['WP_eto',         'Gesamtenergie (Wh)',        VARIABLETYPE_FLOAT,   'WP.Energie',          'Messwerte.Energie',   2],
        ['WP_etop',        'Gesamtenergie pers. (Wh)',  VARIABLETYPE_FLOAT,   'WP.Energie',          'Messwerte.Energie',   3],
        // ── Messwerte ─────────────────────────────────────────────────────
        ['WP_nrg_ptotal',  'Ladeleistung gesamt (W)',   VARIABLETYPE_FLOAT,   '~Watt',               'Messwerte.Leistung',  1],
        ['WP_nrg_pl1',     'Leistung L1 (W)',           VARIABLETYPE_FLOAT,   '~Watt',               'Messwerte.Leistung',  2],
        ['WP_nrg_pl2',     'Leistung L2 (W)',           VARIABLETYPE_FLOAT,   '~Watt',               'Messwerte.Leistung',  3],
        ['WP_nrg_pl3',     'Leistung L3 (W)',           VARIABLETYPE_FLOAT,   '~Watt',               'Messwerte.Leistung',  4],
        ['WP_nrg_il1',     'Strom L1 (A)',              VARIABLETYPE_FLOAT,   '~Ampere.16',          'Messwerte.Strom',     1],
        ['WP_nrg_il2',     'Strom L2 (A)',              VARIABLETYPE_FLOAT,   '~Ampere.16',          'Messwerte.Strom',     2],
        ['WP_nrg_il3',     'Strom L3 (A)',              VARIABLETYPE_FLOAT,   '~Ampere.16',          'Messwerte.Strom',     3],
        ['WP_nrg_ul1',     'Spannung L1 (V)',           VARIABLETYPE_FLOAT,   '~Volt',               'Messwerte.Spannung',  1],
        ['WP_nrg_ul2',     'Spannung L2 (V)',           VARIABLETYPE_FLOAT,   '~Volt',               'Messwerte.Spannung',  2],
        ['WP_nrg_ul3',     'Spannung L3 (V)',           VARIABLETYPE_FLOAT,   '~Volt',               'Messwerte.Spannung',  3],
        ['WP_nrg_pfl1',    'Leistungsfaktor L1',        VARIABLETYPE_FLOAT,   '',                    'Messwerte.Leistungsfaktor', 1],
        ['WP_nrg_pfl2',    'Leistungsfaktor L2',        VARIABLETYPE_FLOAT,   '',                    'Messwerte.Leistungsfaktor', 2],
        ['WP_nrg_pfl3',    'Leistungsfaktor L3',        VARIABLETYPE_FLOAT,   '',                    'Messwerte.Leistungsfaktor', 3],
        ['WP_fhz',         'Netzfrequenz (Hz)',          VARIABLETYPE_FLOAT,   '~Hertz',             'Messwerte',           5],
        // ── PV & Batterie ─────────────────────────────────────────────────
        ['WP_pGrid',       'Netzleistung (W)',           VARIABLETYPE_FLOAT,   '~Watt',              'PV & Batterie',       1],
        ['WP_pPv',         'PV-Leistung (W)',            VARIABLETYPE_FLOAT,   '~Watt',              'PV & Batterie',       2],
        ['WP_pAkku',       'Batterie-Leistung (W)',      VARIABLETYPE_FLOAT,   '~Watt',              'PV & Batterie',       3],
        ['WP_akkuSOC',     'Batterie SoC (%)',           VARIABLETYPE_FLOAT,   'WP.Prozent',         'PV & Batterie',       4],
        // ── PV Einstellungen ──────────────────────────────────────────────
        ['WP_fst',         'Start-Leistung (W)',         VARIABLETYPE_FLOAT,   '~Watt',              'PV Einstellungen',    1],
        ['WP_fup',         'PV-Überschuss aktiv',        VARIABLETYPE_BOOLEAN, '',                   'PV Einstellungen',    2],
        ['WP_po',          'Prio-Offset (W)',            VARIABLETYPE_FLOAT,   '~Watt',              'PV Einstellungen',    3],
        ['WP_sh',          'Stop-Hysterese (W)',         VARIABLETYPE_FLOAT,   '~Watt',              'PV Einstellungen',    4],
        ['WP_psh',         'Phasen-Hysterese (W)',       VARIABLETYPE_FLOAT,   '~Watt',              'PV Einstellungen',    5],
        ['WP_spl3',        '3-Phasen Schwelle (W)',      VARIABLETYPE_FLOAT,   '~Watt',              'PV Einstellungen',    6],
        // ── Awattar ───────────────────────────────────────────────────────
        ['WP_awc',         'Awattar Land',               VARIABLETYPE_STRING,  '',                   'Awattar',             1],
        ['WP_awp',         'Awattar Max-Preis (ct)',     VARIABLETYPE_FLOAT,   '',                   'Awattar',             2],
        ['WP_ful',         'Dynamische Preise aktiv',    VARIABLETYPE_BOOLEAN, '',                   'Awattar',             3],
        // ── Ladeplaner ────────────────────────────────────────────────────
        ['WP_sch_week_ctrl',   'Zeitplan Mo–Fr',         VARIABLETYPE_INTEGER, 'WP.ScheduleCtrl',    'Ladeplaner',          1],
        ['WP_sch_week_slots',  'Zeitfenster Mo–Fr',     VARIABLETYPE_STRING,  '',                    'Ladeplaner',          2],
        ['WP_sch_satur_ctrl',  'Zeitplan Samstag',       VARIABLETYPE_INTEGER, 'WP.ScheduleCtrl',    'Ladeplaner',          3],
        ['WP_sch_satur_slots', 'Zeitfenster Samstag',   VARIABLETYPE_STRING,  '',                    'Ladeplaner',          4],
        ['WP_sch_sund_ctrl',   'Zeitplan Sonntag',       VARIABLETYPE_INTEGER, 'WP.ScheduleCtrl',    'Ladeplaner',          5],
        ['WP_sch_sund_slots',  'Zeitfenster Sonntag',   VARIABLETYPE_STRING,  '',                    'Ladeplaner',          6],
        // ── System ────────────────────────────────────────────────────────
        ['WP_fwv',         'Firmware Version',           VARIABLETYPE_STRING,  '',                    'System',              1],
        ['WP_sse',         'Seriennummer',               VARIABLETYPE_STRING,  '',                    'System',              2],
        ['WP_var',         'Variante (kW)',              VARIABLETYPE_INTEGER, '',                    'System',              3],
        ['WP_rbc',         'Neustarts',                  VARIABLETYPE_INTEGER, '',                    'System',              4],
        ['WP_rbt',         'Laufzeit (h)',               VARIABLETYPE_FLOAT,   '',                    'System',              5],
        ['WP_tma1',        'Temperatur 1 (°C)',          VARIABLETYPE_FLOAT,   '~Temperature',        'System',              7],
        ['WP_tma2',        'Temperatur 2 (°C)',          VARIABLETYPE_FLOAT,   '~Temperature',        'System',              8],
        ['WP_tma3',        'Temperatur 3 (°C)',          VARIABLETYPE_FLOAT,   '~Temperature',        'System',              9],
        ['WP_tma4',        'Temperatur 4 (°C)',          VARIABLETYPE_FLOAT,   '~Temperature',        'System',             10],
        // ── Netzwerk ──────────────────────────────────────────────────────
        ['WP_wst',         'WiFi STA Status',            VARIABLETYPE_STRING,  '',                    'Netzwerk',            1],
        ['WP_wsms',        'WiFi State Machine',         VARIABLETYPE_STRING,  '',                    'Netzwerk',            2],
        ['WP_rssi',        'WLAN Signal (dBm)',          VARIABLETYPE_INTEGER, '',                    'Netzwerk',            3],
        ['WP_host',        'Hostname',                   VARIABLETYPE_STRING,  '',                    'Netzwerk',            4],
        ['WP_fna',         'Friendly Name',              VARIABLETYPE_STRING,  '',                    'Netzwerk',            5],
    ];

    private const CATEGORY_POSITIONS = [
        'Fahrzeug & Laden'  => 1,
        'Stromstufen'       => 2,
        'Verriegelung'      => 3,
        'Energie'           => 4,
        'Messwerte'         => 5,
        'Next Trip'         => 6,
        'OCPP'              => 7,
        'Hotspot'           => 8,
        'PV & Batterie'     => 9,
        'PV Einstellungen'  => 10,
        'Awattar'           => 11,
        'Ladeplaner'        => 12,
        'System'            => 13,
        'Netzwerk'          => 14,
    ];

    private const SCHEDULE_MAP = [
        'sch_week'  => ['WP_sch_week_ctrl',  'WP_sch_week_slots'],
        'sch_satur' => ['WP_sch_satur_ctrl', 'WP_sch_satur_slots'],
        'sch_sund'  => ['WP_sch_sund_ctrl',  'WP_sch_sund_slots'],
    ];

    public function Create()
    {
        parent::Create();
        $this->SetBuffer('IdentCache', '');
        $this->ConnectParent(self::SPLITTER_GUID);
        $this->RegisterAttributeString('VariableMap', '{}');
        $this->RegisterAttributeString('SchedulerData', '{}');
        $this->RegisterPropertyBoolean('ShowCurrent', false);
        $this->RegisterPropertyBoolean('ShowVoltage', false);
        $this->RegisterPropertyBoolean('ShowPowerFactor', false);
        $this->RegisterPropertyBoolean('ShowTemperature', false);
        $this->RegisterPropertyBoolean('ShowFrequency', false);
        $this->RegisterPropertyBoolean('ShowPhaseDetail', false);
        $this->RegisterPropertyBoolean('ShowChargeAdvanced', false);
        $this->RegisterPropertyBoolean('ShowLock', false);
        $this->RegisterPropertyBoolean('ShowExtras', false);
        $this->RegisterPropertyBoolean('ShowCurrentLevels', false);
        $this->RegisterPropertyBoolean('ShowNextTrip', false);
        $this->RegisterPropertyBoolean('ShowOCPP', false);
        $this->RegisterPropertyBoolean('ShowHotspot', false);
        $this->RegisterPropertyBoolean('ShowPV', false);
        $this->RegisterPropertyBoolean('ShowPVConfig', false);
        $this->RegisterPropertyBoolean('ShowAwattar', false);
        $this->RegisterPropertyBoolean('ShowScheduler', false);
        $this->RegisterPropertyBoolean('ShowSystem', false);
        $this->RegisterPropertyBoolean('ShowNetwork', false);
        $this->RegisterPropertyBoolean('LogPower', false);
        $this->RegisterPropertyBoolean('LogEnergy', false);
    }

    public function Destroy()
    {
        $instances = IPS_GetInstanceListByModuleID('{8F5E8A3C-7D2A-4B1E-9F6C-2E4A8B3D5F7E}');
        $otherInstances = array_filter($instances, fn($id) => $id !== $this->InstanceID);

        if (count($otherInstances) === 0) {
            $profiles = [
                'WP.Lademodus', 'WP.ForceModus', 'WP.Phasenmodus',
                'WP.Ladestrom', 'WP.Energie', 'WP.UnlockSetting',
                'WP.Prozent', 'WP.ScheduleCtrl',
                'WP.CarState', 'WP.ErrorState', 'WP.ModelStatus'
            ];
            foreach ($profiles as $p) {
                if (@IPS_VariableProfileExists($p)) {
                    @IPS_DeleteVariableProfile($p);
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
        $this->createVariableStructure();
        $this->removeDisabledVariables();
        $this->configureArchiving();
        $this->SetStatus(self::STATUS_OFFLINE);
        $this->UpdateStatus();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // VariableMap – für Runtime-Lookup in Unterkategorien
    // ══════════════════════════════════════════════════════════════════════════

    private function getVariableMap(): array
    {
        return json_decode($this->ReadAttributeString('VariableMap'), true) ?: [];
    }

    private function setVariableMap(array $m): void
    {
        $this->WriteAttributeString('VariableMap', json_encode($m));
    }

    private function registerInMap(string $ident, int $id): void
    {
        $m = $this->getVariableMap();
        $m[$ident] = $id;
        $this->setVariableMap($m);
    }

    private function unregisterFromMap(string $ident): void
    {
        $m = $this->getVariableMap();
        unset($m[$ident]);
        $this->setVariableMap($m);
    }

    private function findVariableByIdent(string $ident): int
    {
        $map = $this->getVariableMap();

        if (isset($map[$ident])) {
            $id = (int)$map[$ident];
            if ($id > 0 && @IPS_ObjectExists($id)) {
                $o = @IPS_GetObject($id);
                if ($o !== false && $o['ObjectType'] === 2 && $o['ObjectIdent'] === $ident) {
                    return $id;
                }
            }
            unset($map[$ident]);
            $this->setVariableMap($map);
        }

        $id = $this->findByIdentRecursive($ident, $this->InstanceID);
        if ($id > 0) {
            $this->registerInMap($ident, $id);
            return $id;
        }

        return 0;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Optionale Gruppen
    // ══════════════════════════════════════════════════════════════════════════

    private function isIdentEnabled(string $ident): bool
    {
        foreach (self::OPTIONAL_GROUPS as $p => $ids) {
            if (in_array($ident, $ids, true)) {
                return $this->ReadPropertyBoolean($p);
            }
        }
        return true;
    }

    private function removeDisabledVariables(): void
    {
        foreach (self::OPTIONAL_GROUPS as $p => $ids) {
            if (!$this->ReadPropertyBoolean($p)) {
                foreach ($ids as $ident) {
                    $this->removeVariable($ident);
                }
            }
        }
        $this->removeEmptyCategories($this->InstanceID);
    }

    /**
     * Entfernt eine Variable einheitlich: zurück zur Instanz holen, UnregisterVariable, Map bereinigen.
     */
    private function removeVariable(string $ident): void
    {
        $id = $this->findVariableByIdent($ident);
        if ($id > 0) {
            // Zurück zur Instanz verschieben, damit GetIDForIdent/UnregisterVariable funktioniert
            $parent = IPS_GetObject($id)['ParentID'];
            if ($parent !== $this->InstanceID) {
                IPS_SetParent($id, $this->InstanceID);
            }
        }

        // UnregisterVariable entfernt die Variable wenn sie unter der Instanz registriert ist
        if (@$this->GetIDForIdent($ident) !== false) {
            $this->UnregisterVariable($ident);
        }

        $this->unregisterFromMap($ident);
    }

    private function removeEmptyCategories(int $pid): void
    {
        foreach (IPS_GetChildrenIDs($pid) as $cid) {
            $o = IPS_GetObject($cid);

            if ($o['ObjectType'] !== 1) {
                continue;
            }
            if (strpos($o['ObjectIdent'], 'CAT_') !== 0) {
                continue;
            }

            $inst = @IPS_GetInstance($cid);
            if ($inst === false) {
                continue;
            }
            if ($inst['ModuleInfo']['ModuleID'] !== self::DUMMY_GUID) {
                continue;
            }

            $this->removeEmptyCategories($cid);

            if (count(IPS_GetChildrenIDs($cid)) === 0) {
                IPS_DeleteInstance($cid);
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Archivierung
    // ══════════════════════════════════════════════════════════════════════════

    private function configureArchiving(): void
    {
        $aid = $this->getArchiveId();
        if ($aid === 0) {
            return;
        }

        foreach (self::ARCHIVE_CONFIG as $p => [$ident, $agg]) {
            $vid = $this->findVariableByIdent($ident);
            if ($vid <= 0) {
                continue;
            }
            if ($this->ReadPropertyBoolean($p)) {
                AC_SetLoggingStatus($aid, $vid, true);
                AC_SetAggregationType($aid, $vid, $agg);
            } else {
                AC_SetLoggingStatus($aid, $vid, false);
            }
        }
        IPS_ApplyChanges($aid);
    }

    private function getArchiveId(): int
    {
        $ids = IPS_GetInstanceListByModuleID(self::ARCHIVE_GUID);
        return count($ids) > 0 ? $ids[0] : 0;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen-Struktur erstellen (alle Variablen via RegisterVariable)
    // ══════════════════════════════════════════════════════════════════════════

    private function createVariableStructure(): void
    {
        $cats = [];
        foreach (self::VARIABLES as [$ident, $name, $type, $profile, $catPath, $position]) {
            if (!$this->isIdentEnabled($ident)) {
                continue;
            }

            $parentId = $this->getOrCreateCategory($catPath, $cats);
            $isActionable = in_array($ident, self::ACTIONABLE_IDENTS, true);

            $this->ensureVariable($ident, $name, $type, $profile, $parentId, $position, $isActionable);
        }
    }

    /**
     * Erstellt oder aktualisiert eine Variable via RegisterVariable.
     * Verschiebt sie anschließend in die Zielkategorie.
     */
    private function ensureVariable(string $ident, string $name, int $type, string $profile, int $parentId, int $position, bool $actionable): void
    {
        // Existierende Variable suchen (könnte in Unterkategorie sein)
        $existingId = $this->findVariableByIdent($ident);

        if ($existingId > 0) {
            // Zur Instanz verschieben, damit RegisterVariable sie findet
            $currentParent = IPS_GetObject($existingId)['ParentID'];
            if ($currentParent !== $this->InstanceID) {
                IPS_SetParent($existingId, $this->InstanceID);
            }
        }

        // RegisterVariable erstellt/aktualisiert und setzt Profil als Standarddarstellung
        match ($type) {
            VARIABLETYPE_BOOLEAN => $this->RegisterVariableBoolean($ident, $name, $profile, $position),
            VARIABLETYPE_INTEGER => $this->RegisterVariableInteger($ident, $name, $profile, $position),
            VARIABLETYPE_FLOAT   => $this->RegisterVariableFloat($ident, $name, $profile, $position),
            VARIABLETYPE_STRING  => $this->RegisterVariableString($ident, $name, $profile, $position),
        };

        // Aktion aktivieren für steuerbare Variablen
        if ($actionable) {
            $this->EnableAction($ident);
        }

        // In Zielkategorie verschieben
        $id = $this->GetIDForIdent($ident);
        IPS_SetParent($id, $parentId);
        IPS_SetPosition($id, $position);
        $this->registerInMap($ident, $id);
    }

    /**
     * Erstellt oder findet eine Dummy-Kategorie anhand des Pfads.
     */
    private function getOrCreateCategory(string $catPath, array &$cats): int
    {
        $parts = explode('.', $catPath);
        $parentId = $this->InstanceID;
        $catKey = '';
        $depth = 0;

        foreach ($parts as $part) {
            $catKey .= 'CAT_' . preg_replace('/[^a-zA-Z0-9]/', '_', $part);
            if (!array_key_exists($catKey, $cats)) {
                $id = @IPS_GetObjectIDByIdent($catKey, $parentId);

                if ($id === false || $id <= 0) {
                    $id = IPS_CreateInstance(self::DUMMY_GUID);
                    IPS_SetParent($id, $parentId);
                    IPS_SetIdent($id, $catKey);
                    IPS_SetName($id, $part);
                    if ($depth === 0 && isset(self::CATEGORY_POSITIONS[$part])) {
                        IPS_SetPosition($id, self::CATEGORY_POSITIONS[$part]);
                    }
                }
                $cats[$catKey] = (int)$id;
            }
            $parentId = $cats[$catKey];
            $depth++;
        }

        return $parentId;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // RequestAction
    // ══════════════════════════════════════════════════════════════════════════

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'WP_al1':
                $this->SetAdapterLevel(1, (int)$Value);
                break;
            case 'WP_al2':
                $this->SetAdapterLevel(2, (int)$Value);
                break;
            case 'WP_al3':
                $this->SetAdapterLevel(3, (int)$Value);
                break;
            case 'WP_al4':
                $this->SetAdapterLevel(4, (int)$Value);
                break;
            case 'WP_al5':
                $this->SetAdapterLevel(5, (int)$Value);
                break;
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
            case 'WP_ama':
                $this->SetMaxCurrent((int)$Value);
                break;
            case 'WP_ust':
                $this->SetUnlockSetting((int)$Value);
                break;
            case 'WP_sch_week_ctrl':
                $this->setScheduleControl('sch_week', (int)$Value);
                break;
            case 'WP_sch_satur_ctrl':
                $this->setScheduleControl('sch_satur', (int)$Value);
                break;
            case 'WP_sch_sund_ctrl':
                $this->setScheduleControl('sch_sund', (int)$Value);
                break;
            case 'WP_fte':
                $this->SetNextTripEnergy((int)$Value);
                break;
            case 'WP_ftt':
                $this->SetNextTripTime((string)$Value);
                break;
            case 'WP_ocppe':
                $this->SetOCPPEnabled((bool)$Value);
                break;
            case 'WP_ocppu':
                $this->SetOCPPUrl((string)$Value);
                break;
            case 'WP_ocpph':
                $this->SetOCPPHeartbeat((int)$Value);
                break;
            case 'WP_ocpps':
                break;
            case 'WP_wan':
                $this->SetHotspotName((string)$Value);
                break;
            case 'WP_wak':
                $this->SetHotspotPassword((string)$Value);
                break;
            default:
                $this->SendDebug('RequestAction', "Unbekannter Ident: $Ident", 0);
                break;
        }
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString, true);
        $buffer = json_decode($data['Buffer'] ?? '{}', true);
        $type = $buffer['type'] ?? '';
        $status = $buffer['status'] ?? [];

        if ($type === 'fullStatus' || $type === 'deltaStatus') {
            $this->SetStatus(self::STATUS_OK);
            $this->writeStatusToVariables($status, ($type === 'fullStatus'));
        }

        return '';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Steuerfunktionen
    // ══════════════════════════════════════════════════════════════════════════

    public function SetLademodus(int $m): bool
    {
        return $this->sendCommand('lmo', $m);
    }

    public function SetForceModus(int $f): bool
    {
        return $this->sendCommand('frc', $f);
    }

    public function SetPhasenmodus(int $p): bool
    {
        return $this->sendCommand('psm', $p);
    }

    public function SetMaxCurrent(int $a): bool
    {
        return $this->sendCommand('ama', max(6, min(32, $a)));
    }

    public function SetUnlockSetting(int $u): bool
    {
        return $this->sendCommand('ust', max(0, min(2, $u)));
    }

    public function StartLaden(): bool
    {
        return $this->sendCommand('frc', 2);
    }

    public function StopLaden(): bool
    {
        return $this->sendCommand('frc', 1);
    }

    public function SetLadestrom(int $a): bool
    {
        return $this->sendCommand('amp', max(6, min($this->getMaxAmp(), $a)));
    }

    public function SetWPValue(string $key, $value): bool
    {
        return $this->sendCommand($key, $value);
    }

    public function Reboot(): bool
    {
        $this->SendDebug('CMD', 'Reboot angefordert', 0);
        return $this->sendCommand('rst', true);
    }

    public function SetNextTripEnergy(int $energy): bool
    {
        return $this->sendCommand('fte', max(0, $energy));
    }

    public function SetNextTripTime(string $time): bool
    {
        if (strpos($time, ':') !== false) {
            $parts = explode(':', $time);
            $seconds = ((int)$parts[0] * 3600) + ((int)($parts[1] ?? 0) * 60);
        } else {
            $seconds = (int)$time;
        }
        return $this->sendCommand('ftt', max(0, min(86399, $seconds)));
    }

    public function SetAdapterLevel(int $level, int $ampere): bool
    {
        if ($level < 1 || $level > 5) {
            return false;
        }
        return $this->sendCommand('al' . $level, max(6, min(32, $ampere)));
    }

    public function SetOCPPEnabled(bool $enabled): bool
    {
        return $this->sendCommand('ocppe', $enabled);
    }

    public function SetOCPPUrl(string $url): bool
    {
        return $this->sendCommand('ocppu', $url);
    }

    public function SetOCPPHeartbeat(int $seconds): bool
    {
        return $this->sendCommand('ocpph', max(0, $seconds));
    }

    public function SetHotspotName(string $name): bool
    {
        return $this->sendCommand('wan', $name);
    }

    public function SetHotspotPassword(string $password): bool
    {
        return $this->sendCommand('wak', $password);
    }

    public function SetSchedule(string $day, int $control, string $r1Start, string $r1End, string $r2Start, string $r2End): bool
    {
        $keyMap = ['week' => 'sch_week', 'saturday' => 'sch_satur', 'sunday' => 'sch_sund'];
        $key = $keyMap[$day] ?? null;
        if ($key === null) {
            return false;
        }

        $schedule = [
            'control' => max(0, min(4, $control)),
            'ranges'  => [
                ['begin' => $this->parseTime($r1Start), 'end' => $this->parseTime($r1End)],
                ['begin' => $this->parseTime($r2Start), 'end' => $this->parseTime($r2End)],
            ],
        ];
        return $this->sendCommand($key, $schedule);
    }

    public function UpdateStatus(): void
    {
        @$this->SendDataToParent(json_encode([
            'DataID'   => self::SPLITTER_SEND_GUID,
            'Function' => 'ForceUpdate',
            'Payload'  => '',
        ]));
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
            if (($res['status'] ?? '') === 'ok') {
                return true;
            }
            $errorMsg = $res['message'] ?? 'unbekannter Fehler';
            $this->SendDebug('CMD', "Befehl '$key' fehlgeschlagen: $errorMsg", 0);
            $this->LogMessage("Wattpilot: Befehl '$key' fehlgeschlagen – $errorMsg", KL_WARNING);
            return false;
        }

        $this->SendDebug('CMD', "Befehl '$key' fehlgeschlagen – keine Antwort vom Splitter", 0);
        $this->LogMessage("Wattpilot: Befehl '$key' konnte nicht gesendet werden (Splitter nicht erreichbar)", KL_WARNING);
        return false;
    }

    // ── Scheduler Helpers ────────────────────────────────────────────────────

    private function setScheduleControl(string $apiKey, int $ctrl): void
    {
        $schData = json_decode($this->ReadAttributeString('SchedulerData'), true) ?: [];
        $current = $schData[$apiKey] ?? [
            'ranges' => [
                ['begin' => ['hour' => 0, 'minute' => 0], 'end' => ['hour' => 0, 'minute' => 0]],
                ['begin' => ['hour' => 0, 'minute' => 0], 'end' => ['hour' => 0, 'minute' => 0]],
            ],
        ];

        $schedule = [
            'control' => max(0, min(4, $ctrl)),
            'ranges'  => $current['ranges'],
        ];
        $this->sendCommand($apiKey, $schedule);
    }

    private function parseTime(string $t): array
    {
        $p = explode(':', $t);
        return ['hour' => (int)($p[0] ?? 0), 'minute' => (int)($p[1] ?? 0)];
    }

    private function formatTime(array $t): string
    {
        return sprintf('%02d:%02d', $t['hour'] ?? 0, $t['minute'] ?? 0);
    }

    private function formatSlots(array $ranges): string
    {
        $s1 = '–';
        $s2 = '–';
        if (isset($ranges[0])) {
            $s1 = $this->formatTime($ranges[0]['begin'] ?? []) . '–' . $this->formatTime($ranges[0]['end'] ?? []);
        }
        if (isset($ranges[1])) {
            $s2 = $this->formatTime($ranges[1]['begin'] ?? []) . '–' . $this->formatTime($ranges[1]['end'] ?? []);
        }
        return "Slot 1: $s1 | Slot 2: $s2";
    }

    // ── Ladestrom ────────────────────────────────────────────────────────────

    private function getMaxAmp(): int
    {
        $id = $this->findByIdent('WP_var');
        return ($id > 0 && @GetValueInteger($id) === 11) ? 16 : 32;
    }

    private function updateLadestromProfile(int $v): void
    {
        if (IPS_VariableProfileExists('WP.Ladestrom')) {
            IPS_SetVariableProfileValues('WP.Ladestrom', 6, ($v === 11) ? 16 : 32, 1);
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen schreiben
    // ══════════════════════════════════════════════════════════════════════════

    private function writeStatusToVariables(array $s, bool $fu = false): void
    {
        $lckMap = [0 => 'Normal', 1 => 'Auto Unlock', 2 => 'Always Lock', 3 => 'Force Unlock'];
        $ffbMap = [0 => 'OK', 1 => 'Problem Lock', 2 => 'Problem Unlock'];
        $cusMap = [0 => 'Unbekannt', 1 => 'Entriegelt', 2 => 'Entriegeln fehlgeschlagen', 3 => 'Verriegelt', 4 => 'Verriegeln fehlgeschlagen', 5 => 'Stromausfall'];
        $wstMap = [0 => 'Idle', 1 => 'No SSID', 2 => 'Scan done', 3 => 'Verbunden', 4 => 'Fehlgeschlagen', 5 => 'Verbindung verloren', 6 => 'Getrennt', 8 => 'Verbinde...', 9 => 'Trenne...'];
        $wsmsMap = [0 => 'None', 1 => 'Scanning', 2 => 'Connecting', 3 => 'Connected'];
        $awcMap = [0 => 'Österreich', 1 => 'Deutschland'];

        // ── Fahrzeug & Laden ─────────────────────────────────────────────
        if (isset($s['car'])) $this->setVar('WP_car', (int)$s['car'], $fu);
        if (isset($s['alw'])) $this->setVar('WP_alw', (bool)$s['alw'], $fu);
        if (isset($s['frc'])) $this->setVar('WP_frc', (int)$s['frc'], $fu);
        if (isset($s['lmo'])) $this->setVar('WP_lmo', (int)$s['lmo'], $fu);
        if (isset($s['psm'])) $this->setVar('WP_psm', (int)$s['psm'], $fu);
        if (isset($s['amp'])) $this->setVar('WP_amp', (int)$s['amp'], $fu);
        if (isset($s['acu'])) $this->setVar('WP_acu', (int)$s['acu'], $fu);
        if (isset($s['ama'])) $this->setVar('WP_ama', (int)$s['ama'], $fu);
        if (isset($s['mca'])) $this->setVar('WP_mca', (int)$s['mca'], $fu);
        if (isset($s['pnp'])) $this->setVar('WP_pnp', (int)$s['pnp'], $fu);
        if (isset($s['fsp'])) $this->setVar('WP_fsp', (bool)$s['fsp'], $fu);
        if (isset($s['modelStatus'])) $this->setVar('WP_modelStatus', (int)$s['modelStatus'], $fu);
        if (isset($s['err'])) $this->setVar('WP_err', (int)$s['err'], $fu);
        if (isset($s['adi'])) $this->setVar('WP_adi', (bool)$s['adi'], $fu);
        if (isset($s['trx'])) $this->setVar('WP_trx', (int)($s['trx'] ?? 0), $fu);
        if (isset($s['dwo'])) $this->setVar('WP_dwo', (float)($s['dwo'] ?? 0), $fu);

        // ── Stromstufen ──────────────────────────────────────────────────
        if (isset($s['al1'])) $this->setVar('WP_al1', (int)$s['al1'], $fu);
        if (isset($s['al2'])) $this->setVar('WP_al2', (int)$s['al2'], $fu);
        if (isset($s['al3'])) $this->setVar('WP_al3', (int)$s['al3'], $fu);
        if (isset($s['al4'])) $this->setVar('WP_al4', (int)$s['al4'], $fu);
        if (isset($s['al5'])) $this->setVar('WP_al5', (int)$s['al5'], $fu);

        // ── Next Trip ────────────────────────────────────────────────────
        if (isset($s['fte'])) $this->setVar('WP_fte', (int)$s['fte'], $fu);
        if (isset($s['ftt'])) {
            $secs = (int)$s['ftt'];
            $timeStr = sprintf('%02d:%02d', intdiv($secs, 3600), intdiv($secs % 3600, 60));
            $this->setVar('WP_ftt', $timeStr, $fu);
        }

        // ── OCPP ─────────────────────────────────────────────────────────
        if (isset($s['ocppe'])) $this->setVar('WP_ocppe', (bool)$s['ocppe'], $fu);
        if (isset($s['ocppu'])) $this->setVar('WP_ocppu', (string)$s['ocppu'], $fu);
        if (isset($s['ocpph'])) $this->setVar('WP_ocpph', (int)$s['ocpph'], $fu);
        if (isset($s['ocpps'])) $this->setVar('WP_ocpps', (bool)$s['ocpps'], $fu);

        // ── Hotspot ──────────────────────────────────────────────────────
        if (isset($s['wan'])) $this->setVar('WP_wan', (string)$s['wan'], $fu);
        if (isset($s['wak'])) {
            $this->setVar('WP_wak', is_string($s['wak']) ? $s['wak'] : (($s['wak'] === true) ? '(gesetzt)' : '(leer)'), $fu);
        }

        // ── Verriegelung ─────────────────────────────────────────────────
        if (isset($s['ust'])) $this->setVar('WP_ust', (int)$s['ust'], $fu);
        if (isset($s['lck'])) $this->setVar('WP_lck', $lckMap[$s['lck']] ?? 'Unbekannt', $fu);
        if (isset($s['ffb'])) $this->setVar('WP_ffb', $ffbMap[$s['ffb']] ?? 'Unbekannt', $fu);
        if (isset($s['cus'])) $this->setVar('WP_cus', $cusMap[$s['cus']] ?? 'Unbekannt', $fu);

        // ── Energie ──────────────────────────────────────────────────────
        if (isset($s['wh'])) $this->setVar('WP_wh', round((float)$s['wh'], 1), $fu);
        if (isset($s['eto'])) $this->setVar('WP_eto', round((float)$s['eto'], 0), $fu);
        if (isset($s['etop'])) $this->setVar('WP_etop', round((float)$s['etop'], 0), $fu);

        // ── Messwerte ────────────────────────────────────────────────────
        if (isset($s['fhz'])) $this->setVar('WP_fhz', round((float)$s['fhz'], 3), $fu);

        // ── PV & Batterie ────────────────────────────────────────────────
        if (isset($s['fbuf_pGrid'])) $this->setVar('WP_pGrid', round((float)$s['fbuf_pGrid'], 1), $fu);
        if (isset($s['fbuf_pPv'])) $this->setVar('WP_pPv', round((float)$s['fbuf_pPv'], 1), $fu);
        if (isset($s['fbuf_pAkku'])) $this->setVar('WP_pAkku', round((float)$s['fbuf_pAkku'], 1), $fu);
        if (isset($s['fbuf_akkuSOC'])) $this->setVar('WP_akkuSOC', round((float)$s['fbuf_akkuSOC'], 1), $fu);

        // ── PV Einstellungen ─────────────────────────────────────────────
        if (isset($s['fst'])) $this->setVar('WP_fst', round((float)$s['fst'], 0), $fu);
        if (isset($s['fup'])) $this->setVar('WP_fup', (bool)$s['fup'], $fu);
        if (isset($s['po'])) $this->setVar('WP_po', round((float)$s['po'], 0), $fu);
        if (isset($s['sh'])) $this->setVar('WP_sh', round((float)$s['sh'], 0), $fu);
        if (isset($s['psh'])) $this->setVar('WP_psh', round((float)$s['psh'], 0), $fu);
        if (isset($s['spl3'])) $this->setVar('WP_spl3', round((float)$s['spl3'], 0), $fu);

        // ── Awattar ──────────────────────────────────────────────────────
        if (isset($s['awc'])) $this->setVar('WP_awc', $awcMap[$s['awc']] ?? 'Unbekannt', $fu);
        if (isset($s['awp'])) $this->setVar('WP_awp', round((float)$s['awp'], 2), $fu);
        if (isset($s['ful'])) $this->setVar('WP_ful', (bool)$s['ful'], $fu);

        // ── System ───────────────────────────────────────────────────────
        if (isset($s['fwv'])) $this->setVar('WP_fwv', (string)$s['fwv'], $fu);
        if (isset($s['sse'])) $this->setVar('WP_sse', (string)$s['sse'], $fu);
        if (isset($s['var'])) {
            $this->setVar('WP_var', (int)$s['var'], $fu);
            $this->updateLadestromProfile((int)$s['var']);
        }
        if (isset($s['rbc'])) $this->setVar('WP_rbc', (int)$s['rbc'], $fu);
        if (isset($s['rbt'])) $this->setVar('WP_rbt', round($s['rbt'] / 3600000, 1), $fu);
        if (isset($s['rssi'])) $this->setVar('WP_rssi', (int)$s['rssi'], $fu);

        // ── Netzwerk ─────────────────────────────────────────────────────
        if (isset($s['wst'])) $this->setVar('WP_wst', $wstMap[$s['wst']] ?? 'Unbekannt', $fu);
        if (isset($s['wsms'])) $this->setVar('WP_wsms', $wsmsMap[$s['wsms']] ?? 'Unbekannt', $fu);
        if (isset($s['host'])) $this->setVar('WP_host', (string)($s['host'] ?? ''), $fu);
        if (isset($s['fna'])) $this->setVar('WP_fna', (string)$s['fna'], $fu);

        // ── Phasen (Array) ───────────────────────────────────────────────
        if (isset($s['pha']) && is_array($s['pha']) && count($s['pha']) >= 6) {
            $p = $s['pha'];
            $this->setVar('WP_pha_l1', (bool)($p[3] ?? false), $fu);
            $this->setVar('WP_pha_l2', (bool)($p[4] ?? false), $fu);
            $this->setVar('WP_pha_l3', (bool)($p[5] ?? false), $fu);
        }

        // ── Energie-Array (nrg) ──────────────────────────────────────────
        if (isset($s['nrg']) && is_array($s['nrg']) && count($s['nrg']) >= 16) {
            $n = $s['nrg'];
            $this->setVar('WP_nrg_ptotal', round((float)($n[11] ?? 0), 1), $fu);
            $this->setVar('WP_nrg_pl1', round((float)($n[7] ?? 0), 1), $fu);
            $this->setVar('WP_nrg_pl2', round((float)($n[8] ?? 0), 1), $fu);
            $this->setVar('WP_nrg_pl3', round((float)($n[9] ?? 0), 1), $fu);
            $this->setVar('WP_nrg_il1', round((float)($n[4] ?? 0), 2), $fu);
            $this->setVar('WP_nrg_il2', round((float)($n[5] ?? 0), 2), $fu);
            $this->setVar('WP_nrg_il3', round((float)($n[6] ?? 0), 2), $fu);
            $this->setVar('WP_nrg_ul1', round((float)($n[0] ?? 0), 1), $fu);
            $this->setVar('WP_nrg_ul2', round((float)($n[1] ?? 0), 1), $fu);
            $this->setVar('WP_nrg_ul3', round((float)($n[2] ?? 0), 1), $fu);
            $this->setVar('WP_nrg_pfl1', round((float)($n[12] ?? 0), 3), $fu);
            $this->setVar('WP_nrg_pfl2', round((float)($n[13] ?? 0), 3), $fu);
            $this->setVar('WP_nrg_pfl3', round((float)($n[14] ?? 0), 3), $fu);
        }

        // ── Temperaturen (Array) ─────────────────────────────────────────
        if (isset($s['tma']) && is_array($s['tma']) && count($s['tma']) >= 6) {
            $t = $s['tma'];
            foreach ([2 => 1, 3 => 2, 4 => 3, 5 => 4] as $i => $nr) {
                if (isset($t[$i]) && $t[$i] !== null) {
                    $this->setVar("WP_tma{$nr}", round((float)$t[$i], 1), $fu);
                }
            }
        }

        // ── Scheduler ────────────────────────────────────────────────────
        $schData = json_decode($this->ReadAttributeString('SchedulerData'), true) ?: [];
        foreach (self::SCHEDULE_MAP as $apiKey => [$ctrlIdent, $slotsIdent]) {
            if (isset($s[$apiKey]) && is_array($s[$apiKey])) {
                $sch = $s[$apiKey];
                $ctrl = (int)($sch['control'] ?? 0);
                $ranges = $sch['ranges'] ?? [];
                $schData[$apiKey] = ['control' => $ctrl, 'ranges' => $ranges];
                $this->setVar($ctrlIdent, $ctrl, $fu);
                $this->setVar($slotsIdent, $this->formatSlots($ranges), $fu);
            }
        }
        $this->WriteAttributeString('SchedulerData', json_encode($schData));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen-Zugriff (Runtime)
    // ══════════════════════════════════════════════════════════════════════════

    private function setVar(string $ident, $value, bool $fu = false): void
    {
        $id = $this->findByIdent($ident);
        if ($id <= 0) {
            return;
        }

        if ($fu && in_array($ident, self::ALWAYS_UPDATE_ON_FULL, true)) {
            SetValue($id, $value);
            return;
        }

        if (GetValue($id) !== $value) {
            SetValue($id, $value);
        }
    }

    private function findByIdent(string $ident): int
    {
        $cj = $this->GetBuffer('IdentCache');
        $c = $cj !== '' ? json_decode($cj, true) : [];

        if (isset($c[$ident])) {
            if ($c[$ident] === 0) {
                return 0;
            }
            if (@IPS_ObjectExists($c[$ident])) {
                return $c[$ident];
            }
            unset($c[$ident]);
        }

        if (!$this->isIdentEnabled($ident)) {
            $c[$ident] = 0;
            $this->SetBuffer('IdentCache', json_encode($c));
            return 0;
        }

        $id = $this->findVariableByIdent($ident);
        $c[$ident] = $id;
        $this->SetBuffer('IdentCache', json_encode($c));
        return $id;
    }

    private function findByIdentRecursive(string $ident, int $pid): int
    {
        foreach (IPS_GetChildrenIDs($pid) as $cid) {
            $o = IPS_GetObject($cid);
            if ($o['ObjectIdent'] === $ident) {
                return $cid;
            }
            if ($o['ObjectType'] === 0 || $o['ObjectType'] === 1) {
                $f = $this->findByIdentRecursive($ident, $cid);
                if ($f > 0) {
                    return $f;
                }
            }
        }
        return 0;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Profile
    // ══════════════════════════════════════════════════════════════════════════

    private function createProfiles(): void
    {
        if ($this->cp('WP.CarState', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.CarState', 0, 'Unbekannt/Fehler', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.CarState', 1, 'Bereit', '', 0x00AA00);
            IPS_SetVariableProfileAssociation('WP.CarState', 2, 'Lädt', '', 0x0055FF);
            IPS_SetVariableProfileAssociation('WP.CarState', 3, 'Warte auf Auto', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.CarState', 4, 'Fertig', '', 0x00CC00);
            IPS_SetVariableProfileAssociation('WP.CarState', 5, 'Fehler', '', 0xFF0000);
        }

        if ($this->cp('WP.ErrorState', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.ErrorState', 0, 'Kein Fehler', '', 0x00AA00);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 1, 'FI AC', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 2, 'FI DC', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 3, 'Phase', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 4, 'Überspannung', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 5, 'Überstrom', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 6, 'Diode', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 7, 'PP ungültig', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 8, 'GND ungültig', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 9, 'Schütz klebt', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 10, 'Schütz fehlt', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 11, 'FI unbekannt', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 12, 'Unbekannt', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 13, 'Übertemperatur', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 14, 'Keine Kommunikation', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 15, 'Schloss klemmt offen', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ErrorState', 16, 'Schloss klemmt zu', '', 0xFF0000);
        }

        if ($this->cp('WP.ModelStatus', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 0, 'Keine Daten', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 1, 'Übertemperatur', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 2, 'Zugang: Warten', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 3, 'Force Ein', '', 0x00CC00);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 4, 'Force Aus', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 5, 'Zeitplan', '', 0x0055FF);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 6, 'Energie-Limit', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 7, 'Awattar', '', 0x00CCAA);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 8, 'AutoStop Test', '', 0x0055FF);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 9, 'AutoStop ZuWenigZeit', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 10, 'AutoStop', '', 0x0055FF);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 11, 'AutoStop KeineUhr', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 12, 'PV Überschuss', '', 0x00CC00);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 13, 'Fallback GoE Default', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 14, 'Fallback GoE Scheduler', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 15, 'Fallback Default', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 16, 'Fallback GoE Awattar', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 17, 'Fallback Awattar', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 18, 'Fallback AutoStop', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 19, 'KeepAlive', '', 0x00CCAA);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 20, 'Pause nicht erlaubt', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 22, 'Simulate Unplug', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 23, 'Phasenwechsel', '', 0x0055FF);
            IPS_SetVariableProfileAssociation('WP.ModelStatus', 24, 'Min. Pause', '', 0xFFAA00);
        }

        if ($this->cp('WP.Lademodus', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.Lademodus', 3, 'Standard', '', 0x00AA00);
            IPS_SetVariableProfileAssociation('WP.Lademodus', 4, 'ECO / PV-Überschuss', '', 0xFFAA00);
            IPS_SetVariableProfileAssociation('WP.Lademodus', 5, 'AutoStop', '', 0x0055FF);
        }
        if ($this->cp('WP.ForceModus', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.ForceModus', 0, 'Neutral', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ForceModus', 1, 'Aus', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ForceModus', 2, 'Ein', '', 0x00CC00);
        }
        if ($this->cp('WP.Phasenmodus', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.Phasenmodus', 0, 'Auto', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.Phasenmodus', 1, '1-phasig', '', 0x0055FF);
            IPS_SetVariableProfileAssociation('WP.Phasenmodus', 2, '3-phasig', '', 0x00AA00);
        }
        if ($this->cp('WP.Ladestrom', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileValues('WP.Ladestrom', 6, 32, 1);
            IPS_SetVariableProfileText('WP.Ladestrom', '', ' A');
        }
        if ($this->cp('WP.Energie', VARIABLETYPE_FLOAT)) {
            IPS_SetVariableProfileValues('WP.Energie', 0, 0, 0);
            IPS_SetVariableProfileText('WP.Energie', '', ' Wh');
            IPS_SetVariableProfileDigits('WP.Energie', 1);
        }
        if ($this->cp('WP.UnlockSetting', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.UnlockSetting', 0, 'Normal', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.UnlockSetting', 1, 'Auto Entriegeln', '', 0x00AA00);
            IPS_SetVariableProfileAssociation('WP.UnlockSetting', 2, 'Immer verriegelt', '', 0xFF0000);
        }
        if ($this->cp('WP.Prozent', VARIABLETYPE_FLOAT)) {
            IPS_SetVariableProfileValues('WP.Prozent', 0, 100, 0.1);
            IPS_SetVariableProfileText('WP.Prozent', '', ' %');
            IPS_SetVariableProfileDigits('WP.Prozent', 1);
        }
        if ($this->cp('WP.ScheduleCtrl', VARIABLETYPE_INTEGER)) {
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl', 0, 'Deaktiviert', '', 0x888888);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl', 1, 'Laden erlauben', '', 0x00AA00);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl', 2, 'Laden sperren', '', 0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl', 3, 'Erlauben + PV', '', 0x00CCAA);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl', 4, 'Sperren + PV', '', 0xFF8800);
        }
    }

    private function cp(string $n, int $t): bool
    {
        if (!IPS_VariableProfileExists($n)) {
            IPS_CreateVariableProfile($n, $t);
            return true;
        }
        return false;
    }
}   