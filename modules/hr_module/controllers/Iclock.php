<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Public, unauthenticated ADMS push receiver for ZKTeco F18 devices.
// No staff/CRM session is required or expected here - the caller is
// hardware, not a logged-in user - so this deliberately extends
// App_Controller (not AdminController), same as the payment gateway
// webhook controllers (Ideal, Paypal).
class Iclock extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Zkteco_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    // GET /iclock/cdata?SN=...&options=all      -> handshake
    // POST /iclock/cdata?SN=...&table=ATTLOG    -> attendance log push
    public function cdata()
    {
        $device = $this->_authorize_device();
        if (!$device) {
            return;
        }

        $is_post = strtoupper((string) $this->input->server('REQUEST_METHOD')) === 'POST';
        $table   = strtoupper((string) $this->input->get('table'));

        if (!$is_post && $this->input->get('options') === 'all') {
            $this->_handshake($device);
            return;
        }

        if ($is_post && $table === 'ATTLOG') {
            $this->_attlog($device);
            return;
        }

        // The device also pushes other table types (e.g. OPERLOG - its own
        // admin/operation-action log). We only need attendance data, but
        // rejecting these makes the device retry them forever. Drain and
        // acknowledge so it moves on.
        if ($is_post && $table !== '') {
            file_get_contents('php://input');
            $this->Zkteco_model->record_contact($device->id);
            $this->_plain('OK: 0');
            return;
        }

        $this->_plain('Unsupported request', 400);
    }

    // GET /iclock/getrequest?SN=... -> heartbeat / command poll
    public function getrequest()
    {
        $device = $this->_authorize_device();
        if (!$device) {
            return;
        }

        $this->Zkteco_model->record_contact($device->id);
        $this->_plain('OK');
    }

    // POST/GET /iclock/devicecmd?SN=... -> command execution result ack
    public function devicecmd()
    {
        $device = $this->_authorize_device();
        if (!$device) {
            return;
        }

        // Minimal ack-only implementation: no command queue is maintained,
        // so there's nothing to correlate the ack against yet.
        file_get_contents('php://input');
        $this->Zkteco_model->record_contact($device->id);
        $this->_plain('OK');
    }

    // Delay/ErrorDelay/TransInterval are fixed per readme.md's own handshake
    // example - the F18's Cloud Server Setting screen has no field for these,
    // so there's nothing an admin can configure here; Realtime=1 is what
    // actually makes punches push immediately.
    private function _handshake($device)
    {
        $this->Zkteco_model->record_contact($device->id);
        $this->Zkteco_model->log_push($device->id, 0, 0, 'handshake');

        $body = 'GET OPTION FROM: ' . $device->serial_number . "\n"
            . "Stamp=9999\n"
            . "OpStamp=9999\n"
            . "ErrorDelay=60\n"
            . "Delay=30\n"
            . "TransTimes=00:00;23:59\n"
            . "TransInterval=1\n"
            . "TransFlag=1111111111\n"
            . "TimeZone=6\n"
            . "Realtime=1\n"
            . "Encrypt=0\n";

        $this->_plain($body);
    }

    private function _attlog($device)
    {
        $raw   = (string) file_get_contents('php://input');
        $lines = array_values(array_filter(
            preg_split('/\r\n|\r|\n/', trim($raw)),
            function ($line) { return trim($line) !== ''; }
        ));

        $saved = $this->Zkteco_model->save_attlog_batch($device->id, $lines);

        $this->Zkteco_model->record_contact($device->id);
        $this->Zkteco_model->log_push($device->id, count($lines), $saved, 'success');

        $this->_plain('OK: ' . $saved);
    }

    // Validates SN whitelist, integration enabled flag, User-Agent signature
    // and a per-device rate limit, per readme.md "Production Security
    // Requirements". Writes the plain-text error response itself and
    // returns null on any failure so callers can just `return` on null.
    private function _authorize_device()
    {
        $sn = $this->input->get('SN');
        if (empty($sn)) {
            $this->_plain('SN required', 403);
            return null;
        }

        if ($this->Hr_module_model->get_setting('zkteco_enabled') != '1') {
            $this->_plain('ZKTeco integration disabled', 403);
            return null;
        }

        $device = $this->Zkteco_model->find_device_by_serial($sn);
        if (!$device || (int) $device->status !== 1) {
            $this->_plain('Unknown device', 403);
            return null;
        }

        $ua = (string) $this->input->server('HTTP_USER_AGENT');
        if (stripos($ua, 'iClock') === false && stripos($ua, 'ZKTeco') === false) {
            $this->_plain('Invalid client', 403);
            return null;
        }

        if (!$this->Zkteco_model->rate_limit_ok($device->id)) {
            $this->_plain('Rate limit exceeded', 429);
            return null;
        }

        return $device;
    }

    // Perfex's core bootstrap (App_Controller) unconditionally starts a
    // session and a CSRF cookie on every request, and adds browser-oriented
    // cache-busting headers on top - none of which a minimal ADMS device
    // HTTP client expects or necessarily tolerates. Strip all of that so the
    // device sees a bare, spec-shaped response: just Content-Type + body.
    private function _plain($body, $status = 200)
    {
        header_remove('Set-Cookie');
        header_remove('Cache-Control');
        header_remove('Pragma');
        header_remove('Expires');

        // This device ignores the pushed "TimeZone=6" handshake option and
        // instead sets its clock straight from this Date header plus a
        // hardcoded +8h (China default) baked into its firmware - confirmed
        // both by comparing a known-correct GMT header against what the
        // device then displayed (it was consistently +8h, not +6h, from
        // that header) and by a direct on/off test (device holds correct
        // time with the Cloud Server Setting pointed at 0.0.0.0, and drifts
        // +2h within one heartbeat cycle once pointed at this server).
        // There's no on-device or SDK-exposed setting to change that
        // offset, so the header is pre-shifted back by the (8 - 6) = 2h
        // difference here, making the device's wrong +8h land on the
        // correct BD +6h.
        header('Date: ' . gmdate('D, d M Y H:i:s', time() - 2 * 3600) . ' GMT');

        $this->output
            ->set_status_header($status)
            ->set_content_type('text/plain', 'UTF-8')
            ->set_output($body);
    }
}
