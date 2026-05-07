<?php
declare(strict_types=1);

class WattpilotSplitter extends IPSModule
{
    private const STATUS_OK         = 102;
    private const STATUS_OFFLINE    = 201;
    private const STATUS_ERROR      = 202;
    private const STATUS_AUTH_ERROR = 203;

    private const STATE_WAIT_HELLO  = 0;
    private const STATE_WAIT_AUTH   = 1;
    private const STATE_CONNECTED   = 2;

    private const WS_CLIENT_GUID  = '{D68FD31F-0E90-7019-F16C-1949BD3079EF}';
    private const WS_SEND_GUID    = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
    private const CHILD_DATA_GUID = '{2F5B8A4C-9D3E-4F6A-AB7C-8D9E0F1A2B3C}';

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyString('Password', '');
        $this->RegisterPropertyInteger('ReconnectInterval', 60);
        $this->RegisterPropertyInteger('UpdateInterval', 60);

        $this->RegisterTimer('WP_ReconnectTimer', 0, 'WPSP_CheckConnection($_IPS["TARGET"]);');
        $this->RegisterTimer('WP_UpdateTimer', 0, 'WPSP_PushUpdate($_IPS["TARGET"]);');

        $this->SetBuffer('State', (string)self::STATE_WAIT_HELLO);
        $this->SetBuffer('Serial', '');
        $this->SetBuffer('Secured', '0');
        $this->SetBuffer('HashedPassword', '');
        $this->SetBuffer('BcryptHash', '');
        $this->SetBuffer('FullStatus', '{}');
        $this->SetBuffer('DeltaBuffer', '{}');

        $this->RequireParent(self::WS_CLIENT_GUID);
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->SetBuffer('State', (string)self::STATE_WAIT_HELLO);
        $this->SetBuffer('FullStatus', '{}');
        $this->SetBuffer('DeltaBuffer', '{}');

        $cID = $this->GetConnectionID();
        if ($cID > 0 && @IPS_InstanceExists($cID)) {
            $this->RegisterMessage($cID, IM_CHANGESTATUS);
        }

        $host = $this->ReadPropertyString('Host');
        $pw   = $this->ReadPropertyString('Password');

        if ($host === '' || $pw === '') {
            $this->SetStatus(self::STATUS_OFFLINE);
            $this->SetTimerInterval('WP_ReconnectTimer', 0);
            $this->SetTimerInterval('WP_UpdateTimer', 0);
            return;
        }

        $this->UpdateConfigurationForParent();

        $interval = $this->ReadPropertyInteger('ReconnectInterval');
        $this->SetTimerInterval('WP_ReconnectTimer', $interval * 1000);

        $updateInterval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetTimerInterval('WP_UpdateTimer', $updateInterval * 1000);

        $this->SetStatus(self::STATUS_ERROR);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        parent::MessageSink($TimeStamp, $SenderID, $Message, $Data);

        if ($Message == IPS_KERNELMESSAGE && $Data[0] == KR_READY) {
            $interval = $this->ReadPropertyInteger('ReconnectInterval');
            $this->SetTimerInterval('WP_ReconnectTimer', $interval * 1000);
            $updateInterval = $this->ReadPropertyInteger('UpdateInterval');
            $this->SetTimerInterval('WP_UpdateTimer', $updateInterval * 1000);
        }

        if ($Message == IM_CHANGESTATUS && $SenderID == $this->GetConnectionID()) {
            $newStatus = $Data[0];
            $this->SendDebug('MessageSink', "Parent Status → $newStatus", 0);

            if ($newStatus >= 200) {
                $this->SetBuffer('State', (string)self::STATE_WAIT_HELLO);
                $this->SetStatus(self::STATUS_ERROR);
                $this->SetTimerInterval('WP_ReconnectTimer', 10000);
            } elseif ($newStatus == 102) {
                $interval = $this->ReadPropertyInteger('ReconnectInterval');
                $this->SetTimerInterval('WP_ReconnectTimer', $interval * 1000);
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Timer: Gebündelte Messwerte pushen
    // ══════════════════════════════════════════════════════════════════════════

    public function PushUpdate(): void
    {
        if ((int)$this->GetBuffer('State') !== self::STATE_CONNECTED) {
            return;
        }

        $deltaJson = $this->GetBuffer('DeltaBuffer');
        if ($deltaJson === '{}' || $deltaJson === '') {
            return;
        }

        $delta = json_decode($deltaJson, true) ?: [];
        if (empty($delta)) {
            return;
        }

        // Buffer leeren BEVOR gesendet wird
        $this->SetBuffer('DeltaBuffer', '{}');
        $this->SendToChildren('deltaStatus', $delta);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Parent-Konfiguration
    // ══════════════════════════════════════════════════════════════════════════

    public function GetConfigurationForParent()
    {
        $host = $this->ReadPropertyString('Host');
        if ($host === '') {
            return json_encode(['URL' => 'ws://127.0.0.1/ws', 'Active' => false]);
        }
        return json_encode(['URL' => "ws://{$host}/ws", 'Active' => true]);
    }

    private function UpdateConfigurationForParent(): void
    {
        $cID = $this->GetConnectionID();
        if ($cID == 0 || !@IPS_InstanceExists($cID)) return;

        if (IPS_GetInstance($cID)['InstanceStatus'] >= 200) {
            if (@IPS_GetProperty($cID, 'Active') == true) {
                IPS_SetProperty($cID, 'Active', false);
                @IPS_ApplyChanges($cID);
            }
        }

        $old_cfg = IPS_GetConfiguration($cID);
        $new_cfg = $this->GetConfigurationForParent();

        if ($old_cfg != $new_cfg) {
            IPS_SetConfiguration($cID, $new_cfg);
            @IPS_ApplyChanges($cID);
        } else {
            if (!@IPS_GetProperty($cID, 'Active')) {
                IPS_SetProperty($cID, 'Active', true);
                @IPS_ApplyChanges($cID);
            }
        }
    }

    private function GetConnectionID(): int
    {
        $instance = @IPS_GetInstance($this->InstanceID);
        if ($instance === false) return 0;
        return (int)($instance['ConnectionID'] ?? 0);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Datenempfang vom WebSocket Client
    // ══════════════════════════════════════════════════════════════════════════

    public function ReceiveData($JSONString)
    {
        $data    = json_decode($JSONString, true);
        $payload = $data['Buffer'] ?? '';
        if ($payload === '') return '';

        $this->SendDebug('RX', $payload, 0);

        $msg = @json_decode($payload, true);
        if (!is_array($msg)) return '';

        $this->handleMessage($msg);
        return '';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // ForwardData von Child
    // ══════════════════════════════════════════════════════════════════════════

    public function ForwardData($JSONString)
    {
        $data     = json_decode($JSONString, true);
        $function = $data['Function'] ?? '';

        switch ($function) {
            case 'SendToWattpilot':
                $message = json_decode($data['Payload'], true);
                if ((int)$this->GetBuffer('State') !== self::STATE_CONNECTED) {
                    return json_encode(['status' => 'error', 'message' => 'not connected']);
                }
                if ($this->GetBuffer('Secured') === '1') {
                    $message = $this->buildSecuredMsg($message);
                }
                $this->sendToWattpilot($message);
                return json_encode(['status' => 'ok']);

            case 'GetFullStatus':
                return $this->GetBuffer('FullStatus');

            case 'ForceUpdate':
                $currentStatus = json_decode($this->GetBuffer('FullStatus'), true) ?: [];
                if (!empty($currentStatus)) {
                    $this->SendToChildren('fullStatus', $currentStatus);
                    $this->SetBuffer('DeltaBuffer', '{}');
                }
                return json_encode(['status' => 'ok']);

            default:
                return '';
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Nachrichten-Handler
    // ══════════════════════════════════════════════════════════════════════════

    private function handleMessage(array $msg): void
    {
        $type = $msg['type'] ?? '';

        switch ($type) {
            case 'hello':
                $this->handleHello($msg);
                break;
            case 'authRequired':
                $this->handleAuthRequired($msg);
                break;
            case 'authSuccess':
                $this->SendDebug('Auth', 'Authentifizierung erfolgreich!', 0);
                break;
            case 'authError':
                $this->SendDebug('Auth', 'FEHLER: ' . ($msg['message'] ?? 'unbekannt'), 0);
                $this->SetBuffer('State', (string)self::STATE_WAIT_HELLO);
                $this->SetStatus(self::STATUS_AUTH_ERROR);
                break;
            case 'fullStatus':
                $this->handleFullStatus($msg);
                break;
            case 'deltaStatus':
                $this->handleDeltaStatus($msg);
                break;
            case 'response':
                $this->handleResponse($msg);
                break;
            default:
                $this->SendDebug('RX', "Typ: $type (ignoriert)", 0);
                break;
        }
    }

    private function handleHello(array $msg): void
    {
        $serial  = $msg['serial'] ?? '';
        $secured = (bool)($msg['secured'] ?? false);

        $this->SetBuffer('Serial', $serial);
        $this->SetBuffer('Secured', $secured ? '1' : '0');
        $this->SetBuffer('State', (string)self::STATE_WAIT_AUTH);

        $this->SendDebug('WS', "Hello – Serial: $serial, Secured: " . ($secured ? 'ja' : 'nein'), 0);
    }

    private function handleAuthRequired(array $msg): void
    {
        $this->performAuth($msg['token1'] ?? '', $msg['token2'] ?? '');
    }

    private function handleFullStatus(array $msg): void
    {
        $statusData    = $msg['status'] ?? [];
        $partial       = $msg['partial'] ?? false;
        $currentStatus = json_decode($this->GetBuffer('FullStatus'), true) ?: [];
        $currentStatus = array_merge($currentStatus, $statusData);
        $this->SetBuffer('FullStatus', json_encode($currentStatus));

        if (!$partial) {
            $this->SetBuffer('State', (string)self::STATE_CONNECTED);
            $this->SetStatus(self::STATUS_OK);
            $this->SendDebug('WS', 'FullStatus komplett (' . count($currentStatus) . ' Keys) – VERBUNDEN', 0);

            $host   = $this->ReadPropertyString('Host');
            $serial = $this->GetBuffer('Serial');
            $this->SetSummary($host . ' (#' . $serial . ')');

            // fullStatus sofort an Children
            $this->SendToChildren('fullStatus', $currentStatus);
            $this->SetBuffer('DeltaBuffer', '{}');
        }
    }

    private function handleDeltaStatus(array $msg): void
    {
        $statusData = $msg['status'] ?? [];

        // FullStatus aktuell halten
        $currentStatus = json_decode($this->GetBuffer('FullStatus'), true) ?: [];
        $currentStatus = array_merge($currentStatus, $statusData);
        $this->SetBuffer('FullStatus', json_encode($currentStatus));

        if ((int)$this->GetBuffer('State') === self::STATE_CONNECTED) {
            // In DeltaBuffer sammeln – Timer pusht gebündelt
            $deltaBuffer = json_decode($this->GetBuffer('DeltaBuffer'), true) ?: [];
            $deltaBuffer = array_merge($deltaBuffer, $statusData);
            $this->SetBuffer('DeltaBuffer', json_encode($deltaBuffer));
        }
    }

    private function handleResponse(array $msg): void
    {
        $success = $msg['success'] ?? false;
        $this->SendDebug('CMD', 'Response: ' . ($success ? 'OK' : 'FEHLER'), 0);

        if (isset($msg['status'])) {
            $currentStatus = json_decode($this->GetBuffer('FullStatus'), true) ?: [];
            $currentStatus = array_merge($currentStatus, $msg['status']);
            $this->SetBuffer('FullStatus', json_encode($currentStatus));

            if ((int)$this->GetBuffer('State') === self::STATE_CONNECTED) {
                // Response auf Steuerbefehl → SOFORT pushen
                $this->SendToChildren('deltaStatus', $msg['status']);
            }
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Daten an Children
    // ══════════════════════════════════════════════════════════════════════════

    private function SendToChildren(string $type, array $status): void
    {
        $this->SendDataToChildren(json_encode([
            'DataID' => self::CHILD_DATA_GUID,
            'Buffer' => json_encode([
                'type'   => $type,
                'status' => $status,
            ]),
        ]));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Authentifizierung
    // ══════════════════════════════════════════════════════════════════════════

    private function performAuth(string $token1, string $token2): void
    {
        $serial   = $this->GetBuffer('Serial');
        $password = $this->ReadPropertyString('Password');
        $token3   = substr(bin2hex(random_bytes(32)), 0, 32);

        $cachedHash = $this->GetBuffer('BcryptHash');
        if ($cachedHash !== '') {
            $hashedPassword = $cachedHash;
        } else {
            $hashedPassword = $this->bcryptHashPassword($password, $serial);
            $this->SetBuffer('BcryptHash', $hashedPassword);
        }
        $this->SetBuffer('HashedPassword', $hashedPassword);

        $hash1 = hash('sha256', $token1 . $hashedPassword);
        $hash  = hash('sha256', $token3 . $token2 . $hash1);

        $this->sendToWattpilot([
            'type'   => 'auth',
            'token3' => $token3,
            'hash'   => $hash,
        ]);

        $this->SendDebug('Auth', 'Auth gesendet', 0);
    }

    private function bcryptjsBase64Encode(array $b, int $length): string
    {
        $BASE64 = str_split('./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
        $off = 0;
        $rs  = [];
        while ($off < $length) {
            $c1   = $b[$off] & 0xff; $off++;
            $rs[] = $BASE64[($c1 >> 2) & 0x3f];
            $c1   = ($c1 & 0x03) << 4;
            if ($off >= $length) { $rs[] = $BASE64[$c1 & 0x3f]; break; }
            $c2   = $b[$off] & 0xff; $off++;
            $c1  |= ($c2 >> 4) & 0x0f;
            $rs[] = $BASE64[$c1 & 0x3f];
            $c1   = ($c2 & 0x0f) << 2;
            if ($off >= $length) { $rs[] = $BASE64[$c1 & 0x3f]; break; }
            $c2   = $b[$off] & 0xff; $off++;
            $c1  |= ($c2 >> 6) & 0x03;
            $rs[] = $BASE64[$c1 & 0x3f];
            $rs[] = $BASE64[$c2 & 0x3f];
        }
        return implode('', $rs);
    }

    private function encodeSerial(string $serial, int $length): string
    {
        $vals = array_map('intval', str_split($serial));
        $pad  = array_fill(0, $length - count($vals), 0);
        return $this->bcryptjsBase64Encode(array_merge($pad, $vals), $length);
    }

    private function bcryptHashPassword(string $password, string $serial, int $cost = 8): string
    {
        $pwHash    = hash('sha256', $password);
        $serialB64 = $this->encodeSerial($serial, 16);
        $saltStr   = '$2a$' . str_pad((string)$cost, 2, '0', STR_PAD_LEFT) . '$' . $serialB64;
        return substr(crypt($pwHash, $saltStr), strlen($saltStr));
    }

    private function buildSecuredMsg(array $message): array
    {
        $payload        = json_encode($message);
        $hashedPassword = $this->GetBuffer('HashedPassword');
        return [
            'type'      => 'securedMsg',
            'data'      => $payload,
            'requestId' => ($message['requestId'] ?? '0') . 'sm',
            'hmac'      => hash_hmac('sha256', $payload, $hashedPassword),
        ];
    }

    private function sendToWattpilot(array $data): void
    {
        $payload = json_encode($data);
        $this->SendDebug('TX', $payload, 0);
        $this->SendDataToParent(json_encode([
            'DataID' => self::WS_SEND_GUID,
            'Buffer' => $payload,
        ]));
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Öffentliche Funktionen
    // ══════════════════════════════════════════════════════════════════════════

    public function Reconnect(): void
    {
        $this->SendDebug('WS', 'Reconnect', 0);
        $this->SetBuffer('BcryptHash', '');
        $this->SetBuffer('State', (string)self::STATE_WAIT_HELLO);
        $this->SetBuffer('FullStatus', '{}');
        $this->SetBuffer('DeltaBuffer', '{}');

        $cID = $this->GetConnectionID();
        if ($cID > 0 && @IPS_InstanceExists($cID)) {
            if (@IPS_GetProperty($cID, 'Active')) {
                IPS_SetProperty($cID, 'Active', false);
                @IPS_ApplyChanges($cID);
            }
            IPS_Sleep(1000);
            $this->UpdateConfigurationForParent();
        }
    }

    public function CheckConnection(): void
    {
        $state = (int)$this->GetBuffer('State');
        if ($state === self::STATE_CONNECTED) return;
        $this->SendDebug('WS', "State=$state – Reconnect", 0);
        $this->Reconnect();
    }

    public function TestConnection(): void
    {
        $state = (int)$this->GetBuffer('State');
        if ($state === self::STATE_CONNECTED) {
            echo "✅ Verbindung zum Wattpilot steht! (Serial: " . $this->GetBuffer('Serial') . ")";
        } else {
            echo "❌ Nicht verbunden (State: $state). Prüfe IP-Adresse und Passwort.";
        }
    }
}