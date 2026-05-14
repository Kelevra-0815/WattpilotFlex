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
    ];

    private const ALWAYS_UPDATE_ON_FULL = ['WP_nrg_ptotal', 'WP_pPv'];

    private const OPTIONAL_GROUPS = [
        'ShowCurrent'        => ['WP_nrg_il1', 'WP_nrg_il2', 'WP_nrg_il3'],
        'ShowVoltage'        => ['WP_nrg_ul1', 'WP_nrg_ul2', 'WP_nrg_ul3'],
        'ShowPowerFactor'    => ['WP_nrg_pfl1', 'WP_nrg_pfl2', 'WP_nrg_pfl3'],
        'ShowTemperature'    => ['WP_tma1', 'WP_tma2', 'WP_tma3'],
        'ShowFrequency'      => ['WP_fhz'],
        'ShowPhaseDetail'    => ['WP_pha_l1', 'WP_pha_l2', 'WP_pha_l3', 'WP_pnp'],
        'ShowChargeAdvanced' => ['WP_ama', 'WP_amt', 'WP_mca', 'WP_fsp'],
        'ShowLock'           => ['WP_ust', 'WP_lck', 'WP_ffb', 'WP_cus'],
        'ShowExtras'         => ['WP_adi', 'WP_trx', 'WP_dwo', 'WP_cbl'],
        'ShowPV'             => ['WP_pGrid', 'WP_pPv', 'WP_pAkku', 'WP_akkuSOC', 'WP_avgPGrid', 'WP_avgPPv', 'WP_avgPAkku'],
        'ShowPVConfig'       => ['WP_fst', 'WP_fup', 'WP_po', 'WP_sh', 'WP_psh', 'WP_spl3'],
        'ShowAwattar'        => ['WP_awc', 'WP_awp', 'WP_ful'],
        'ShowScheduler'      => ['WP_sch_week_ctrl', 'WP_sch_week_slots', 'WP_sch_satur_ctrl', 'WP_sch_satur_slots', 'WP_sch_sund_ctrl', 'WP_sch_sund_slots'],
        'ShowSystem'         => ['WP_fwv', 'WP_sse', 'WP_var', 'WP_rbc', 'WP_rbt', 'WP_rssi'],
        'ShowNetwork'        => ['WP_wst', 'WP_wsms', 'WP_host', 'WP_fna'],
    ];

    private const ARCHIVE_CONFIG = [
        'LogPower'  => ['WP_nrg_ptotal', 0],
        'LogEnergy' => ['WP_eto', 1],
        'LogPV'     => ['WP_pPv', 0],
    ];

    private const VARIABLES = [
        ['WP_car',         'Fahrzeugstatus',            VARIABLETYPE_STRING,  '',                    'Fahrzeug & Laden',    1],
        ['WP_alw',         'Laden erlaubt',             VARIABLETYPE_BOOLEAN, '~Switch',             'Fahrzeug & Laden',    2],
        ['WP_frc',         'Force-Modus',               VARIABLETYPE_INTEGER, 'WP.ForceModus',       'Fahrzeug & Laden',    3],
        ['WP_lmo',         'Lademodus',                 VARIABLETYPE_INTEGER, 'WP.Lademodus',        'Fahrzeug & Laden',    4],
        ['WP_psm',         'Phasenmodus',               VARIABLETYPE_INTEGER, 'WP.Phasenmodus',      'Fahrzeug & Laden',    5],
        ['WP_amp',         'Ladestrom gesetzt (A)',     VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',    6],
        ['WP_acu',         'Ladestrom erlaubt (A)',     VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',    7],
        ['WP_ama',         'Max. Strom-Limit (A)',      VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',    8],
        ['WP_amt',         'Temp. Strom-Limit (A)',     VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',    9],
        ['WP_mca',         'Min. Ladestrom (A)',        VARIABLETYPE_INTEGER, '',                    'Fahrzeug & Laden',   10],
        ['WP_pnp',         'Aktive Phasen',             VARIABLETYPE_INTEGER, '',                    'Fahrzeug & Laden',   11],
        ['WP_pha_l1',      'Phase L1 aktiv',            VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   12],
        ['WP_pha_l2',      'Phase L2 aktiv',            VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   13],
        ['WP_pha_l3',      'Phase L3 aktiv',            VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   14],
        ['WP_fsp',         'Einphasig erzwungen',       VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   15],
        ['WP_modelStatus', 'Modellstatus',              VARIABLETYPE_STRING,  '',                    'Fahrzeug & Laden',   16],
        ['WP_err',         'Fehlerstatus',              VARIABLETYPE_STRING,  '',                    'Fahrzeug & Laden',   17],
        ['WP_cbl',         'Kabel-Limit (A)',           VARIABLETYPE_INTEGER, 'WP.Ladestrom',        'Fahrzeug & Laden',   18],
        ['WP_adi',         '16A Adapter aktiv',         VARIABLETYPE_BOOLEAN, '',                    'Fahrzeug & Laden',   19],
        ['WP_trx',         'Transaktion',               VARIABLETYPE_INTEGER, '',                    'Fahrzeug & Laden',   20],
        ['WP_dwo',         'Energie-Limit (Wh)',        VARIABLETYPE_FLOAT,   'WP.Energie',          'Fahrzeug & Laden',   21],
        ['WP_ust',         'Kabelverriegelung',         VARIABLETYPE_INTEGER, 'WP.UnlockSetting',   'Verriegelung',        1],
        ['WP_lck',         'Effektive Verriegelung',    VARIABLETYPE_STRING,  '',                    'Verriegelung',        2],
        ['WP_ffb',         'Schloss-Feedback',          VARIABLETYPE_STRING,  '',                    'Verriegelung',        3],
        ['WP_cus',         'Kabel-Status',              VARIABLETYPE_STRING,  '',                    'Verriegelung',        4],
        ['WP_wh',          'Session-Energie (Wh)',      VARIABLETYPE_FLOAT,   'WP.Energie',          'Energie',             1],
        ['WP_eto',         'Gesamtenergie (Wh)',        VARIABLETYPE_FLOAT,   'WP.Energie',          'Energie',             2],
        ['WP_etop',        'Gesamtenergie pers. (Wh)',  VARIABLETYPE_FLOAT,   'WP.Energie',          'Energie',             3],
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
        ['WP_fhz',         'Netzfrequenz (Hz)',          VARIABLETYPE_FLOAT,   '~Hertz',              'Messwerte',           5],
        ['WP_pGrid',       'Netzleistung (W)',           VARIABLETYPE_FLOAT,   '~Watt',               'PV & Batterie',       1],
        ['WP_pPv',         'PV-Leistung (W)',            VARIABLETYPE_FLOAT,   '~Watt',               'PV & Batterie',       2],
        ['WP_pAkku',       'Batterie-Leistung (W)',      VARIABLETYPE_FLOAT,   '~Watt',               'PV & Batterie',       3],
        ['WP_akkuSOC',     'Batterie SoC (%)',           VARIABLETYPE_FLOAT,   'WP.Prozent',          'PV & Batterie',       4],
        ['WP_avgPGrid',    'Ø Netzleistung (W)',         VARIABLETYPE_FLOAT,   '~Watt',               'PV & Batterie',       5],
        ['WP_avgPPv',      'Ø PV-Leistung (W)',          VARIABLETYPE_FLOAT,   '~Watt',               'PV & Batterie',       6],
        ['WP_avgPAkku',    'Ø Batterie-Leistung (W)',    VARIABLETYPE_FLOAT,   '~Watt',               'PV & Batterie',       7],
        ['WP_fst',         'Start-Leistung (W)',         VARIABLETYPE_FLOAT,   '~Watt',               'PV Einstellungen',    1],
        ['WP_fup',         'PV-Überschuss aktiv',        VARIABLETYPE_BOOLEAN, '',                    'PV Einstellungen',    2],
        ['WP_po',          'Prio-Offset (W)',            VARIABLETYPE_FLOAT,   '~Watt',               'PV Einstellungen',    3],
        ['WP_sh',          'Stop-Hysterese (W)',         VARIABLETYPE_FLOAT,   '~Watt',               'PV Einstellungen',    4],
        ['WP_psh',         'Phasen-Hysterese (W)',       VARIABLETYPE_FLOAT,   '~Watt',               'PV Einstellungen',    5],
        ['WP_spl3',        '3-Phasen Schwelle (W)',      VARIABLETYPE_FLOAT,   '~Watt',               'PV Einstellungen',    6],
        ['WP_awc',         'Awattar Land',               VARIABLETYPE_STRING,  '',                    'Awattar',             1],
        ['WP_awp',         'Awattar Max-Preis (ct)',     VARIABLETYPE_FLOAT,   '',                    'Awattar',             2],
        ['WP_ful',         'Dynamische Preise aktiv',    VARIABLETYPE_BOOLEAN, '',                    'Awattar',             3],
        ['WP_sch_week_ctrl',   'Zeitplan Mo–Fr',         VARIABLETYPE_INTEGER, 'WP.ScheduleCtrl',    'Ladeplaner',          1],
        ['WP_sch_week_slots',  'Zeitfenster Mo–Fr',     VARIABLETYPE_STRING,  '',                    'Ladeplaner',          2],
        ['WP_sch_satur_ctrl',  'Zeitplan Samstag',       VARIABLETYPE_INTEGER, 'WP.ScheduleCtrl',    'Ladeplaner',          3],
        ['WP_sch_satur_slots', 'Zeitfenster Samstag',   VARIABLETYPE_STRING,  '',                    'Ladeplaner',          4],
        ['WP_sch_sund_ctrl',   'Zeitplan Sonntag',       VARIABLETYPE_INTEGER, 'WP.ScheduleCtrl',    'Ladeplaner',          5],
        ['WP_sch_sund_slots',  'Zeitfenster Sonntag',   VARIABLETYPE_STRING,  '',                    'Ladeplaner',          6],
        ['WP_fwv',         'Firmware Version',           VARIABLETYPE_STRING,  '',                    'System',              1],
        ['WP_sse',         'Seriennummer',               VARIABLETYPE_STRING,  '',                    'System',              2],
        ['WP_var',         'Variante (kW)',              VARIABLETYPE_INTEGER, '',                    'System',              3],
        ['WP_rbc',         'Neustarts',                  VARIABLETYPE_INTEGER, '',                    'System',              4],
        ['WP_rbt',         'Laufzeit (h)',               VARIABLETYPE_FLOAT,   '',                    'System',              5],
        ['WP_rssi',        'WLAN Signal (dBm)',          VARIABLETYPE_INTEGER, '',                    'System',              6],
        ['WP_tma1',        'Temperatur 1 (°C)',          VARIABLETYPE_FLOAT,   '~Temperature',        'System',              7],
        ['WP_tma2',        'Temperatur 2 (°C)',          VARIABLETYPE_FLOAT,   '~Temperature',        'System',              8],
        ['WP_tma3',        'Temperatur 3 (°C)',          VARIABLETYPE_FLOAT,   '~Temperature',        'System',              9],
        ['WP_wst',         'WiFi STA Status',            VARIABLETYPE_STRING,  '',                    'Netzwerk',            1],
        ['WP_wsms',        'WiFi State Machine',         VARIABLETYPE_STRING,  '',                    'Netzwerk',            2],
        ['WP_host',        'Hostname',                   VARIABLETYPE_STRING,  '',                    'Netzwerk',            3],
        ['WP_fna',         'Friendly Name',              VARIABLETYPE_STRING,  '',                    'Netzwerk',            4],
    ];

    private const CATEGORY_POSITIONS = [
        'Fahrzeug & Laden'  => 1,
        'Verriegelung'      => 2,
        'Energie'           => 3,
        'Messwerte'         => 4,
        'PV & Batterie'     => 5,
        'PV Einstellungen'  => 6,
        'Awattar'           => 7,
        'Ladeplaner'        => 8,
        'System'            => 9,
        'Netzwerk'          => 10,
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
        $this->RegisterPropertyBoolean('ShowPV', false);
        $this->RegisterPropertyBoolean('ShowPVConfig', false);
        $this->RegisterPropertyBoolean('ShowAwattar', false);
        $this->RegisterPropertyBoolean('ShowScheduler', false);
        $this->RegisterPropertyBoolean('ShowSystem', false);
        $this->RegisterPropertyBoolean('ShowNetwork', false);
        $this->RegisterPropertyBoolean('LogPower', false);
        $this->RegisterPropertyBoolean('LogEnergy', false);
        $this->RegisterPropertyBoolean('LogPV', false);
    }

    public function Destroy()
    {
        $instances = IPS_GetInstanceListByModuleID('{8F5E8A3C-7D2A-4B1E-9F6C-2E4A8B3D5F7E}');
        if (count($instances) <= 1) {
            foreach (['WP.Lademodus','WP.ForceModus','WP.Phasenmodus','WP.Ladestrom','WP.Energie','WP.UnlockSetting','WP.Prozent','WP.ScheduleCtrl'] as $p) {
                if (@IPS_VariableProfileExists($p)) @IPS_DeleteVariableProfile($p);
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
        $this->maintainActionableVariables();
        $this->removeDisabledVariables();
        $this->configureArchiving();
        $this->SetStatus(self::STATUS_OFFLINE);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // VariableMap
    // ══════════════════════════════════════════════════════════════════════════

    private function getVariableMap(): array { return json_decode($this->ReadAttributeString('VariableMap'), true) ?: []; }
    private function setVariableMap(array $m): void { $this->WriteAttributeString('VariableMap', json_encode($m)); }
    private function registerInMap(string $i, int $id): void { $m=$this->getVariableMap();$m[$i]=$id;$this->setVariableMap($m); }
    private function unregisterFromMap(string $i): void { $m=$this->getVariableMap();unset($m[$i]);$this->setVariableMap($m); }

    private function findVariableByIdent(string $ident): int
    {
        $map=$this->getVariableMap();
        if(isset($map[$ident])){$id=(int)$map[$ident];if($id>0&&@IPS_ObjectExists($id)){$o=@IPS_GetObject($id);if($o!==false&&$o['ObjectType']===2&&$o['ObjectIdent']===$ident)return $id;}unset($map[$ident]);$this->setVariableMap($map);}
        $id=$this->findByIdentRecursive($ident,$this->InstanceID);
        if($id>0){$this->registerInMap($ident,$id);return $id;}
        return 0;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Optionale Gruppen
    // ══════════════════════════════════════════════════════════════════════════

    private function isIdentEnabled(string $ident): bool
    {
        foreach(self::OPTIONAL_GROUPS as $p=>$ids){if(in_array($ident,$ids,true))return $this->ReadPropertyBoolean($p);}
        return true;
    }

    private function removeDisabledVariables(): void
    {
        foreach(self::OPTIONAL_GROUPS as $p=>$ids){
            if(!$this->ReadPropertyBoolean($p)){
                foreach($ids as $ident){
                    if(in_array($ident,self::ACTIONABLE_IDENTS,true)){
                        $id=$this->findVariableByIdent($ident);
                        if($id>0&&IPS_GetObject($id)['ParentID']!==$this->InstanceID)IPS_SetParent($id,$this->InstanceID);
                        if(@$this->GetIDForIdent($ident)!==false)$this->UnregisterVariable($ident);
                        $this->unregisterFromMap($ident);
                    }else{
                        $id=$this->findVariableByIdent($ident);
                        if($id>0){IPS_DeleteVariable($id);$this->unregisterFromMap($ident);}
                    }
                }
            }
        }
        $this->removeEmptyCategories($this->InstanceID);
    }

    private function removeEmptyCategories(int $pid): void
    {
        foreach(IPS_GetChildrenIDs($pid) as $cid){$o=IPS_GetObject($cid);if($o['ObjectType']===1&&strpos($o['ObjectIdent'],'CAT_')===0){$this->removeEmptyCategories($cid);if(count(IPS_GetChildrenIDs($cid))===0)IPS_DeleteInstance($cid);}}
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Archivierung
    // ══════════════════════════════════════════════════════════════════════════

    private function configureArchiving(): void
    {
        $aid=$this->getArchiveId();if($aid===0)return;
        foreach(self::ARCHIVE_CONFIG as $p=>[$ident,$agg]){$vid=$this->findVariableByIdent($ident);if($vid<=0)continue;if($this->ReadPropertyBoolean($p)){AC_SetLoggingStatus($aid,$vid,true);AC_SetAggregationType($aid,$vid,$agg);}else{AC_SetLoggingStatus($aid,$vid,false);}}
        IPS_ApplyChanges($aid);
    }
    private function getArchiveId(): int { $ids=IPS_GetInstanceListByModuleID(self::ARCHIVE_GUID);return count($ids)>0?$ids[0]:0; }

    // ══════════════════════════════════════════════════════════════════════════
    // Steuerbare Variablen
    // ══════════════════════════════════════════════════════════════════════════

    private function maintainActionableVariables(): void
    {
        foreach(self::ACTIONABLE_IDENTS as $ident){
            if(!$this->isIdentEnabled($ident))continue;
            $varDef=null;foreach(self::VARIABLES as $d){if($d[0]===$ident){$varDef=$d;break;}}
            if($varDef===null)continue;
            [,$name,$type,$profile,$catPath,$position]=$varDef;

            $existingId=$this->findVariableByIdent($ident);
            $isNew=($existingId<=0);
            $savedParent=0;
            if(!$isNew){$savedParent=IPS_GetObject($existingId)['ParentID'];if($savedParent!==$this->InstanceID)IPS_SetParent($existingId,$this->InstanceID);}

            switch($type){
                case VARIABLETYPE_BOOLEAN:$this->RegisterVariableBoolean($ident,$name,$profile,0);break;
                case VARIABLETYPE_INTEGER:$this->RegisterVariableInteger($ident,$name,$profile,0);break;
                case VARIABLETYPE_FLOAT:$this->RegisterVariableFloat($ident,$name,$profile,0);break;
                case VARIABLETYPE_STRING:$this->RegisterVariableString($ident,$name,$profile,0);break;
            }
            $this->EnableAction($ident);
            $id=$this->GetIDForIdent($ident);

            if($isNew){
                $targetParent=$this->getCategoryId($catPath);
                if($targetParent>0){IPS_SetParent($id,$targetParent);IPS_SetPosition($id,$position);}
                $this->registerInMap($ident,$id);
            }else{
                if($savedParent!==$this->InstanceID&&$savedParent>0&&@IPS_ObjectExists($savedParent))IPS_SetParent($id,$savedParent);
            }
        }
    }

    private function getCategoryId(string $catPath): int
    {
        $parts=explode('.',$catPath);$parentId=$this->InstanceID;$catKey='';$depth=0;
        foreach($parts as $part){
            $catKey.='CAT_'.preg_replace('/[^a-zA-Z0-9]/','_',$part);
            $id=@IPS_GetObjectIDByIdent($catKey,$parentId);
            if($id===false||$id<=0){$id=IPS_CreateInstance(self::DUMMY_GUID);IPS_SetParent($id,$parentId);IPS_SetIdent($id,$catKey);IPS_SetName($id,$part);if($depth===0&&isset(self::CATEGORY_POSITIONS[$part]))IPS_SetPosition($id,self::CATEGORY_POSITIONS[$part]);}
            $parentId=(int)$id;$depth++;
        }
        return $parentId;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // RequestAction
    // ══════════════════════════════════════════════════════════════════════════

    public function RequestAction($Ident, $Value)
    {
        switch($Ident){
            case 'WP_frc':$this->SetForceModus((int)$Value);break;
            case 'WP_lmo':$this->SetLademodus((int)$Value);break;
            case 'WP_psm':$this->SetPhasenmodus((int)$Value);break;
            case 'WP_amp':$this->SetLadestrom((int)$Value);break;
            case 'WP_ama':$this->SetMaxCurrent((int)$Value);break;
            case 'WP_ust':$this->SetUnlockSetting((int)$Value);break;
            case 'WP_sch_week_ctrl':$this->setScheduleControl('sch_week',(int)$Value);break;
            case 'WP_sch_satur_ctrl':$this->setScheduleControl('sch_satur',(int)$Value);break;
            case 'WP_sch_sund_ctrl':$this->setScheduleControl('sch_sund',(int)$Value);break;
            default:$this->SendDebug('RequestAction',"Unbekannter Ident: $Ident",0);break;
        }
    }

    public function ReceiveData($JSONString)
    {
        $data=json_decode($JSONString,true);$buffer=json_decode($data['Buffer']??'{}',true);
        $type=$buffer['type']??'';$status=$buffer['status']??[];
        if($type==='fullStatus'||$type==='deltaStatus'){$this->SetStatus(self::STATUS_OK);$this->writeStatusToVariables($status,($type==='fullStatus'));}
        return '';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Steuerfunktionen
    // ══════════════════════════════════════════════════════════════════════════

    public function SetLademodus(int $m): bool { return $this->sendCommand('lmo',$m); }
    public function SetForceModus(int $f): bool { return $this->sendCommand('frc',$f); }
    public function SetPhasenmodus(int $p): bool { return $this->sendCommand('psm',$p); }
    public function SetMaxCurrent(int $a): bool { return $this->sendCommand('ama',max(6,min(32,$a))); }
    public function SetUnlockSetting(int $u): bool { return $this->sendCommand('ust',max(0,min(2,$u))); }
    public function StartLaden(): bool { return $this->sendCommand('frc',2); }
    public function StopLaden(): bool { return $this->sendCommand('frc',1); }
    public function SetLadestrom(int $a): bool { return $this->sendCommand('amp',max(6,min($this->getMaxAmp(),$a))); }
    public function SetWPValue(string $key,$value): bool { return $this->sendCommand($key,$value); }

    /**
     * Setzt den kompletten Zeitplan für einen Tag.
     * @param string $day 'week', 'saturday' oder 'sunday'
     * @param int $control 0–4
     * @param string $r1Start "HH:MM"
     * @param string $r1End "HH:MM"
     * @param string $r2Start "HH:MM"
     * @param string $r2End "HH:MM"
     */
    public function SetSchedule(string $day, int $control, string $r1Start, string $r1End, string $r2Start, string $r2End): bool
    {
        $keyMap=['week'=>'sch_week','saturday'=>'sch_satur','sunday'=>'sch_sund'];
        $key=$keyMap[$day]??null;if($key===null)return false;
        $schedule=['control'=>max(0,min(4,$control)),'ranges'=>[['begin'=>$this->parseTime($r1Start),'end'=>$this->parseTime($r1End)],['begin'=>$this->parseTime($r2Start),'end'=>$this->parseTime($r2End)]]];
        return $this->sendCommand($key,$schedule);
    }

    public function UpdateStatus(): void
    {
        @$this->SendDataToParent(json_encode(['DataID'=>self::SPLITTER_SEND_GUID,'Function'=>'ForceUpdate','Payload'=>'']));
    }

    private function sendCommand(string $key,$value): bool
    {
        $msg=['type'=>'setValue','requestId'=>time(),'key'=>$key,'value'=>$value];
        $result=@$this->SendDataToParent(json_encode(['DataID'=>self::SPLITTER_SEND_GUID,'Function'=>'SendToWattpilot','Payload'=>json_encode($msg)]));
        $this->SendDebug('CMD',"setValue '$key' = ".json_encode($value),0);
        if($result!==false&&$result!==''){$res=json_decode($result,true);return($res['status']??'')==='ok';}
        return false;
    }

    // ── Scheduler ────────────────────────────────────────────────────────────

    private function setScheduleControl(string $apiKey, int $ctrl): void
    {
        $schData=json_decode($this->ReadAttributeString('SchedulerData'),true)?:[];
        $current=$schData[$apiKey]??['ranges'=>[['begin'=>['hour'=>0,'minute'=>0],'end'=>['hour'=>0,'minute'=>0]],['begin'=>['hour'=>0,'minute'=>0],'end'=>['hour'=>0,'minute'=>0]]]];
        $schedule=['control'=>max(0,min(4,$ctrl)),'ranges'=>$current['ranges']];
        $this->sendCommand($apiKey,$schedule);
    }

    private function parseTime(string $t): array { $p=explode(':',$t);return['hour'=>(int)($p[0]??0),'minute'=>(int)($p[1]??0)]; }
    private function formatTime(array $t): string { return sprintf('%02d:%02d',$t['hour']??0,$t['minute']??0); }

    private function formatSlots(array $ranges): string
    {
        $s1='–';$s2='–';
        if(isset($ranges[0])){$s1=$this->formatTime($ranges[0]['begin']??[]).'–'.$this->formatTime($ranges[0]['end']??[]);}
        if(isset($ranges[1])){$s2=$this->formatTime($ranges[1]['begin']??[]).'–'.$this->formatTime($ranges[1]['end']??[]);}
        return "Slot 1: $s1 | Slot 2: $s2";
    }

    // ── Ladestrom ────────────────────────────────────────────────────────────

    private function getMaxAmp(): int { $id=$this->findByIdent('WP_var');return($id>0&&@GetValueInteger($id)===11)?16:32; }
    private function updateLadestromProfile(int $v): void { if(IPS_VariableProfileExists('WP.Ladestrom'))IPS_SetVariableProfileValues('WP.Ladestrom',6,($v===11)?16:32,1); }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen schreiben
    // ══════════════════════════════════════════════════════════════════════════

    private function writeStatusToVariables(array $s, bool $fu=false): void
    {
        $carMap=[1=>'Bereit',2=>'Lädt',3=>'Warte auf Auto',4=>'Fertig',5=>'Fehler'];
        $errMap=[0=>'Kein Fehler',1=>'FiAc',2=>'FiDc',3=>'Phase',4=>'Überspannung',5=>'Überstrom',6=>'Diode',7=>'PpInvalid',8=>'GndInvalid',9=>'ContactorStuck',10=>'ContactorMiss',11=>'FiUnknown',12=>'Unbekannt',13=>'Übertemperatur',14=>'NoComm',15=>'LockStuckOpen',16=>'LockStuckLocked'];
        $modMap=[0=>'Keine Daten',1=>'Übertemperatur',2=>'Zugang: Warten',3=>'Force Ein',4=>'Force Aus',5=>'Zeitplan',6=>'Energie-Limit',7=>'Awattar',8=>'AutoStop Test',9=>'AutoStop ZuWenigZeit',10=>'AutoStop',11=>'AutoStop KeineUhr',12=>'PV Überschuss',13=>'Fallback GoE Default',14=>'Fallback GoE Scheduler',15=>'Fallback Default',16=>'Fallback GoE Awattar',17=>'Fallback Awattar',18=>'Fallback AutoStop',19=>'KeepAlive',20=>'Pause nicht erlaubt',22=>'Simulate Unplug',23=>'Phasenwechsel',24=>'Min. Pause'];
        $lckMap=[0=>'Normal',1=>'Auto Unlock',2=>'Always Lock',3=>'Force Unlock'];
        $ffbMap=[0=>'OK',1=>'Problem Lock',2=>'Problem Unlock'];
        $cusMap=[0=>'Unbekannt',1=>'Entriegelt',2=>'Entriegeln fehlgeschlagen',3=>'Verriegelt',4=>'Verriegeln fehlgeschlagen',5=>'Stromausfall'];
        $wstMap=[0=>'Idle',1=>'No SSID',2=>'Scan done',3=>'Verbunden',4=>'Fehlgeschlagen',5=>'Verbindung verloren',6=>'Getrennt',8=>'Verbinde...',9=>'Trenne...'];
        $wsmsMap=[0=>'None',1=>'Scanning',2=>'Connecting',3=>'Connected'];
        $awcMap=[0=>'Österreich',1=>'Deutschland'];

        if(isset($s['car']))$this->setVar('WP_car',$carMap[$s['car']]??'Unbekannt',$fu);
        if(isset($s['alw']))$this->setVar('WP_alw',(bool)$s['alw'],$fu);
        if(isset($s['frc']))$this->setVar('WP_frc',(int)$s['frc'],$fu);
        if(isset($s['lmo']))$this->setVar('WP_lmo',(int)$s['lmo'],$fu);
        if(isset($s['psm']))$this->setVar('WP_psm',(int)$s['psm'],$fu);
        if(isset($s['amp']))$this->setVar('WP_amp',(int)$s['amp'],$fu);
        if(isset($s['acu']))$this->setVar('WP_acu',(int)$s['acu'],$fu);
        if(isset($s['ama']))$this->setVar('WP_ama',(int)$s['ama'],$fu);
        if(isset($s['amt']))$this->setVar('WP_amt',(int)$s['amt'],$fu);
        if(isset($s['mca']))$this->setVar('WP_mca',(int)$s['mca'],$fu);
        if(isset($s['pnp']))$this->setVar('WP_pnp',(int)$s['pnp'],$fu);
        if(isset($s['fsp']))$this->setVar('WP_fsp',(bool)$s['fsp'],$fu);
        if(isset($s['modelStatus']))$this->setVar('WP_modelStatus',$modMap[$s['modelStatus']]??'Unbekannt ('.$s['modelStatus'].')',$fu);
        if(isset($s['err']))$this->setVar('WP_err',$errMap[$s['err']]??'Unbekannt',$fu);
        if(isset($s['cbl']))$this->setVar('WP_cbl',(int)$s['cbl'],$fu);
        if(isset($s['adi']))$this->setVar('WP_adi',(bool)$s['adi'],$fu);
        if(isset($s['trx']))$this->setVar('WP_trx',(int)($s['trx']??0),$fu);
        if(isset($s['dwo']))$this->setVar('WP_dwo',(float)($s['dwo']??0),$fu);
        if(isset($s['ust']))$this->setVar('WP_ust',(int)$s['ust'],$fu);
        if(isset($s['lck']))$this->setVar('WP_lck',$lckMap[$s['lck']]??'Unbekannt',$fu);
        if(isset($s['ffb']))$this->setVar('WP_ffb',$ffbMap[$s['ffb']]??'Unbekannt',$fu);
        if(isset($s['cus']))$this->setVar('WP_cus',$cusMap[$s['cus']]??'Unbekannt',$fu);
        if(isset($s['wh']))$this->setVar('WP_wh',round((float)$s['wh'],1),$fu);
        if(isset($s['eto']))$this->setVar('WP_eto',round((float)$s['eto'],0),$fu);
        if(isset($s['etop']))$this->setVar('WP_etop',round((float)$s['etop'],0),$fu);
        if(isset($s['fhz']))$this->setVar('WP_fhz',round((float)$s['fhz'],3),$fu);
        if(isset($s['fbuf_pGrid']))$this->setVar('WP_pGrid',round((float)$s['fbuf_pGrid'],1),$fu);
        if(isset($s['fbuf_pPv']))$this->setVar('WP_pPv',round((float)$s['fbuf_pPv'],1),$fu);
        if(isset($s['fbuf_pAkku']))$this->setVar('WP_pAkku',round((float)$s['fbuf_pAkku'],1),$fu);
        if(isset($s['fbuf_akkuSOC']))$this->setVar('WP_akkuSOC',round((float)$s['fbuf_akkuSOC'],1),$fu);
        if(isset($s['pvopt_averagePGrid']))$this->setVar('WP_avgPGrid',round((float)$s['pvopt_averagePGrid'],1),$fu);
        if(isset($s['pvopt_averagePPv']))$this->setVar('WP_avgPPv',round((float)$s['pvopt_averagePPv'],1),$fu);
        if(isset($s['pvopt_averagePAkku']))$this->setVar('WP_avgPAkku',round((float)$s['pvopt_averagePAkku'],1),$fu);
        if(isset($s['fst']))$this->setVar('WP_fst',round((float)$s['fst'],0),$fu);
        if(isset($s['fup']))$this->setVar('WP_fup',(bool)$s['fup'],$fu);
        if(isset($s['po']))$this->setVar('WP_po',round((float)$s['po'],0),$fu);
        if(isset($s['sh']))$this->setVar('WP_sh',round((float)$s['sh'],0),$fu);
        if(isset($s['psh']))$this->setVar('WP_psh',round((float)$s['psh'],0),$fu);
        if(isset($s['spl3']))$this->setVar('WP_spl3',round((float)$s['spl3'],0),$fu);
        if(isset($s['awc']))$this->setVar('WP_awc',$awcMap[$s['awc']]??'Unbekannt',$fu);
        if(isset($s['awp']))$this->setVar('WP_awp',round((float)$s['awp'],2),$fu);
        if(isset($s['ful']))$this->setVar('WP_ful',(bool)$s['ful'],$fu);
        if(isset($s['fwv']))$this->setVar('WP_fwv',(string)$s['fwv'],$fu);
        if(isset($s['sse']))$this->setVar('WP_sse',(string)$s['sse'],$fu);
        if(isset($s['var'])){$this->setVar('WP_var',(int)$s['var'],$fu);$this->updateLadestromProfile((int)$s['var']);}
        if(isset($s['rbc']))$this->setVar('WP_rbc',(int)$s['rbc'],$fu);
        if(isset($s['rbt']))$this->setVar('WP_rbt',round($s['rbt']/3600000,1),$fu);
        if(isset($s['rssi']))$this->setVar('WP_rssi',(int)$s['rssi'],$fu);
        if(isset($s['wst']))$this->setVar('WP_wst',$wstMap[$s['wst']]??'Unbekannt',$fu);
        if(isset($s['wsms']))$this->setVar('WP_wsms',$wsmsMap[$s['wsms']]??'Unbekannt',$fu);
        if(isset($s['host']))$this->setVar('WP_host',(string)($s['host']??''),$fu);
        if(isset($s['fna']))$this->setVar('WP_fna',(string)$s['fna'],$fu);

        if(isset($s['pha'])){$p=$s['pha'];$this->setVar('WP_pha_l1',(bool)($p[3]??false),$fu);$this->setVar('WP_pha_l2',(bool)($p[4]??false),$fu);$this->setVar('WP_pha_l3',(bool)($p[5]??false),$fu);}
        if(isset($s['nrg'])){$n=$s['nrg'];$this->setVar('WP_nrg_ptotal',round((float)($n[11]??0),1),$fu);$this->setVar('WP_nrg_pl1',round((float)($n[7]??0),1),$fu);$this->setVar('WP_nrg_pl2',round((float)($n[8]??0),1),$fu);$this->setVar('WP_nrg_pl3',round((float)($n[9]??0),1),$fu);$this->setVar('WP_nrg_il1',round((float)($n[4]??0),2),$fu);$this->setVar('WP_nrg_il2',round((float)($n[5]??0),2),$fu);$this->setVar('WP_nrg_il3',round((float)($n[6]??0),2),$fu);$this->setVar('WP_nrg_ul1',round((float)($n[0]??0),1),$fu);$this->setVar('WP_nrg_ul2',round((float)($n[1]??0),1),$fu);$this->setVar('WP_nrg_ul3',round((float)($n[2]??0),1),$fu);$this->setVar('WP_nrg_pfl1',round((float)($n[12]??0),3),$fu);$this->setVar('WP_nrg_pfl2',round((float)($n[13]??0),3),$fu);$this->setVar('WP_nrg_pfl3',round((float)($n[14]??0),3),$fu);}
        if(isset($s['tma'])){$t=$s['tma'];foreach([0=>1,1=>2,2=>3] as $i=>$nr){if(isset($t[$i])&&$t[$i]!==null)$this->setVar("WP_tma{$nr}",round((float)$t[$i],1),$fu);}}

        // ── Scheduler: Control + zusammengefasster Slots-String ───────────
        $schData=json_decode($this->ReadAttributeString('SchedulerData'),true)?:[];
        foreach(self::SCHEDULE_MAP as $apiKey=>[$ctrlIdent,$slotsIdent]){
            if(isset($s[$apiKey])&&is_array($s[$apiKey])){
                $sch=$s[$apiKey];
                $ctrl=(int)($sch['control']??0);
                $ranges=$sch['ranges']??[];

                // Persistieren für setScheduleControl()
                $schData[$apiKey]=['control'=>$ctrl,'ranges'=>$ranges];

                $this->setVar($ctrlIdent,$ctrl,$fu);
                $this->setVar($slotsIdent,$this->formatSlots($ranges),$fu);
            }
        }
        $this->WriteAttributeString('SchedulerData',json_encode($schData));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen-Zugriff
    // ══════════════════════════════════════════════════════════════════════════

    private function setVar(string $ident,$value,bool $fu=false): void
    {
        $id=$this->findByIdent($ident);if($id<=0)return;
        if($fu&&in_array($ident,self::ALWAYS_UPDATE_ON_FULL,true)){SetValue($id,$value);return;}
        if(GetValue($id)!==$value)SetValue($id,$value);
    }

    private function findByIdent(string $ident): int
    {
        $cj=$this->GetBuffer('IdentCache');$c=$cj!==''?json_decode($cj,true):[];
        if(isset($c[$ident])){if($c[$ident]===0)return 0;if(@IPS_ObjectExists($c[$ident]))return $c[$ident];unset($c[$ident]);}
        if(!$this->isIdentEnabled($ident)){$c[$ident]=0;$this->SetBuffer('IdentCache',json_encode($c));return 0;}
        $id=$this->findVariableByIdent($ident);
        $c[$ident]=$id;$this->SetBuffer('IdentCache',json_encode($c));return $id;
    }

    private function findByIdentRecursive(string $ident, int $pid): int
    {
        foreach(IPS_GetChildrenIDs($pid) as $cid){$o=IPS_GetObject($cid);if($o['ObjectIdent']===$ident)return $cid;if($o['ObjectType']===0||$o['ObjectType']===1){$f=$this->findByIdentRecursive($ident,$cid);if($f>0)return $f;}}
        return 0;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Profile
    // ══════════════════════════════════════════════════════════════════════════

    private function createProfiles(): void
    {
        if($this->cp('WP.Lademodus',VARIABLETYPE_INTEGER)){IPS_SetVariableProfileAssociation('WP.Lademodus',3,'Standard','',0x00AA00);IPS_SetVariableProfileAssociation('WP.Lademodus',4,'ECO / PV-Überschuss','',0xFFAA00);IPS_SetVariableProfileAssociation('WP.Lademodus',5,'AutoStop','',0x0055FF);}
        if($this->cp('WP.ForceModus',VARIABLETYPE_INTEGER)){IPS_SetVariableProfileAssociation('WP.ForceModus',0,'Neutral','',0x888888);IPS_SetVariableProfileAssociation('WP.ForceModus',1,'Aus','',0xFF0000);IPS_SetVariableProfileAssociation('WP.ForceModus',2,'Ein','',0x00CC00);}
        if($this->cp('WP.Phasenmodus',VARIABLETYPE_INTEGER)){IPS_SetVariableProfileAssociation('WP.Phasenmodus',0,'Auto','',0x888888);IPS_SetVariableProfileAssociation('WP.Phasenmodus',1,'1-phasig','',0x0055FF);IPS_SetVariableProfileAssociation('WP.Phasenmodus',2,'3-phasig','',0x00AA00);}
        if($this->cp('WP.Ladestrom',VARIABLETYPE_INTEGER)){IPS_SetVariableProfileValues('WP.Ladestrom',6,32,1);IPS_SetVariableProfileText('WP.Ladestrom','',' A');}
        if($this->cp('WP.Energie',VARIABLETYPE_FLOAT)){IPS_SetVariableProfileValues('WP.Energie',0,0,0);IPS_SetVariableProfileText('WP.Energie','',' Wh');IPS_SetVariableProfileDigits('WP.Energie',1);}
        if($this->cp('WP.UnlockSetting',VARIABLETYPE_INTEGER)){IPS_SetVariableProfileAssociation('WP.UnlockSetting',0,'Normal','',0x888888);IPS_SetVariableProfileAssociation('WP.UnlockSetting',1,'Auto Entriegeln','',0x00AA00);IPS_SetVariableProfileAssociation('WP.UnlockSetting',2,'Immer verriegelt','',0xFF0000);}
        if($this->cp('WP.Prozent',VARIABLETYPE_FLOAT)){IPS_SetVariableProfileValues('WP.Prozent',0,100,0.1);IPS_SetVariableProfileText('WP.Prozent','',' %');IPS_SetVariableProfileDigits('WP.Prozent',1);}
        if($this->cp('WP.ScheduleCtrl',VARIABLETYPE_INTEGER)){
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl',0,'Deaktiviert','',0x888888);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl',1,'Laden erlauben','',0x00AA00);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl',2,'Laden sperren','',0xFF0000);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl',3,'Erlauben + PV','',0x00CCAA);
            IPS_SetVariableProfileAssociation('WP.ScheduleCtrl',4,'Sperren + PV','',0xFF8800);
        }
    }
    private function cp(string $n,int $t): bool { if(!IPS_VariableProfileExists($n)){IPS_CreateVariableProfile($n,$t);return true;}return false; }

    // ══════════════════════════════════════════════════════════════════════════
    // Variablen-Struktur (nur nicht-steuerbare)
    // ══════════════════════════════════════════════════════════════════════════

    private function createVariableStructure(): void
    {
        $cats=[];
        foreach(self::VARIABLES as[$ident,$name,$type,$profile,$catPath,$position]){
            if(in_array($ident,self::ACTIONABLE_IDENTS,true))continue;
            if(!$this->isIdentEnabled($ident))continue;
            $parts=explode('.',$catPath);$parentId=$this->InstanceID;$catKey='';$depth=0;
            foreach($parts as $part){
                $catKey.='CAT_'.preg_replace('/[^a-zA-Z0-9]/','_',$part);
                if(!array_key_exists($catKey,$cats)){
                    $id=@IPS_GetObjectIDByIdent($catKey,$parentId);
                    if($id===false||$id<=0){$id=IPS_CreateInstance(self::DUMMY_GUID);IPS_SetParent($id,$parentId);IPS_SetIdent($id,$catKey);IPS_SetName($id,$part);if($depth===0&&isset(self::CATEGORY_POSITIONS[$part]))IPS_SetPosition($id,self::CATEGORY_POSITIONS[$part]);}
                    $cats[$catKey]=(int)$id;
                }
                $parentId=$cats[$catKey];$depth++;
            }
            $this->ensureVariable($ident,$name,$type,$profile,$parentId,$position);
        }
    }

    private function ensureVariable(string $ident,string $name,int $type,string $profile,int $parentId,int $position): void
    {
        $id=$this->findVariableByIdent($ident);
        if($id>0){if(IPS_GetVariable($id)['VariableType']!==$type){IPS_DeleteVariable($id);$this->unregisterFromMap($ident);$id=0;}}
        if($id<=0){
            $id=IPS_CreateVariable($type);IPS_SetIdent($id,$ident);IPS_SetName($id,$name);
            if($profile!==''&&IPS_VariableProfileExists($profile)&&IPS_GetVariableProfile($profile)['ProfileType']===$type)IPS_SetVariableCustomProfile($id,$profile);
            IPS_SetParent($id,$parentId);IPS_SetPosition($id,$position);$this->registerInMap($ident,$id);
        }
    }
}