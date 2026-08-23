<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Public, unauthenticated push receiver for TIMY AiFace terminals (e.g. the
// AI07F), using TIMY's documented "BS Communication Protocol" - a single
// JSON-over-HTTP endpoint where the device POSTs a {"cmd": "..."} object to
// the bare root of whatever Server IP/DomainNm+Port it's configured with
// (there is no per-command sub-path like ZKTeco's ADMS /iclock/cdata, and no
// dedicated subdomain either - it uses the same domain as the CRM site). A
// request-signature check in the root index.php (POST + root path + JSON
// content-type - something a real browser never produces) redirects that
// bare-root request here before CodeIgniter's own routing ever sees it.
//
// Deliberately extends App_Controller (no login), same as Iclock.php - the
// caller is hardware, not a logged-in staff member. Kept fully independent
// of Iclock.php/the ZKTeco push path; nothing here is shared beyond the
// already brand-agnostic Zkteco_model methods (find_device_by_serial,
// resolve_employee, rate_limit_ok, record_contact, log_push).
class Aiface extends App_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_module/Zkteco_model');
        $this->load->model('hr_module/Hr_module_model');
    }

    // POST /hr_module/aiface/receive - every AiFace command arrives here;
    // the JSON body's "cmd" field is what distinguishes reg/checklive/sendlog
    // etc, not the URL (per TIMY's own doc: "no HTTP method routing").
    public function receive()
    {
        $raw    = (string) file_get_contents('php://input');
        $packet = json_decode($raw, true);

        if (!is_array($packet) || empty($packet['cmd'])) {
            $this->_json(['ret' => '', 'result' => false, 'reason' => 1, 'msg' => 'invalid request'], 400);
            return;
        }

        $sn     = (string) ($packet['sn'] ?? '');
        $device = $this->_authorize($sn);
        if (!$device) {
            // _authorize() already wrote the response for 'reg'-shaped
            // rejections; every other command just gets a generic failure.
            return;
        }

        switch ($packet['cmd']) {
            case 'reg':
                $this->Zkteco_model->record_contact($device->id);
                $this->Zkteco_model->log_push($device->id, 0, 0, 'handshake');
                $this->_json([
                    'ret'         => 'reg',
                    'tryseconds'  => 300,
                    'result'      => true,
                    'cloudtime'   => date('Y-m-d H:i:s'),
                    'nosenduser'  => true,
                    'nosendlog'   => false,
                    'nosendimage' => true,
                ]);
                return;

            case 'checklive':
                $this->Zkteco_model->record_contact($device->id);
                $this->_json(['ret' => 'checklive', 'cloudtime' => date('Y-m-d H:i:s')]);
                return;

            case 'sendlog':
                $records = is_array($packet['record'] ?? null) ? $packet['record'] : [];
                $saved   = $this->Zkteco_model->save_aiface_log_batch($device->id, $records);

                $this->Zkteco_model->record_contact($device->id);
                $this->Zkteco_model->log_push($device->id, count($records), $saved, 'success');

                $this->_json([
                    'ret'      => 'sendlog',
                    'result'   => true,
                    'count'    => $packet['count'] ?? count($records),
                    'logindex' => $packet['logindex'] ?? 0,
                    'mark'     => true,
                    'cloudtime' => date('Y-m-d H:i:s'),
                ]);
                return;

            default:
                // Devices also actively report senduser/sendqrcode/sendpin/
                // sendgps/otacheck etc - we only need attendance data, but
                // rejecting these makes the device retry forever. Ack and
                // move on, same "drain and acknowledge" precedent as
                // Iclock::cdata()'s handling of non-ATTLOG table pushes.
                $this->Zkteco_model->record_contact($device->id);
                $this->_json(['ret' => $packet['cmd'], 'result' => true, 'cloudtime' => date('Y-m-d H:i:s')]);
                return;
        }
    }

    // Validates SN whitelist, integration enabled flag, and a per-device
    // rate limit - the same building blocks Iclock::_authorize_device()
    // uses, re-implemented here because "sn" arrives in the JSON body on
    // this protocol, not a GET query param. Writes the JSON error response
    // itself and returns null on any failure so callers can just `return`.
    private function _authorize($sn)
    {
        if ($sn === '') {
            $this->_json(['ret' => '', 'result' => false, 'reason' => 1, 'msg' => 'sn required'], 403);
            return null;
        }

        if ($this->Hr_module_model->get_setting('aiface_enabled') != '1') {
            $this->_json(['ret' => 'reg', 'tryseconds' => 30, 'result' => false, 'reason' => 'AiFace integration disabled'], 403);
            return null;
        }

        $device = $this->Zkteco_model->find_device_by_serial($sn);
        if (!$device || (int) $device->status !== 1) {
            $this->_json(['ret' => 'reg', 'tryseconds' => 30, 'result' => false, 'reason' => 'Device not authorized'], 403);
            return null;
        }

        if (!$this->Zkteco_model->rate_limit_ok($device->id)) {
            $this->_json(['ret' => 'reg', 'tryseconds' => 60, 'result' => false, 'reason' => 'Rate limit exceeded'], 429);
            return null;
        }

        return $device;
    }

    // Same reasoning as Iclock::_plain() - Perfex's App_Controller bootstrap
    // starts a session/CSRF cookie and adds browser cache-busting headers on
    // every request, none of which an embedded device HTTP client (the doc
    // references "mg_close_conn" - a Mongoose embedded server) expects.
    // Strip all of that so the device sees a bare JSON response.
    private function _json($data, $status = 200)
    {
        header_remove('Set-Cookie');
        header_remove('Cache-Control');
        header_remove('Pragma');
        header_remove('Expires');

        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'UTF-8')
            ->set_output(json_encode($data));
    }
}
