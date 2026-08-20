<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * WAHA (WhatsApp HTTP API) Client Library.
 * Stateless - base URL/session/API key are passed into each call since they
 * come from admin-configurable Settings rather than a fixed constructor config.
 */
class Waha_lib
{
    // A WhatsApp group JID (...@g.us) or an already-formed contact JID
    // (...@c.us) passes through unchanged; anything else is treated as a raw
    // phone number and formatted into a Bangladeshi contact JID.
    public function format_target($target)
    {
        $target = trim($target);
        if (preg_match('/@(g|c)\.us$/', $target)) {
            return $target;
        }
        $clean = preg_replace('/[^0-9]/', '', $target);
        if (strpos($clean, '01') === 0) {
            $clean = '88' . $clean;
        }
        return $clean . '@c.us';
    }

    // Sends a plain-text WhatsApp message. Returns ['success' => bool, 'message' => string].
    // Never throws - callers rely on that (see Hr_module_model::send_whatsapp_announcement()).
    public function send_text($base_url, $session, $api_key, $target, $text)
    {
        $payload = [
            'session' => $session,
            'chatId'  => $this->format_target($target),
            'text'    => $text,
        ];
        return $this->_request($base_url, $api_key, '/api/sendText', $payload);
    }

    private function _request($base_url, $api_key, $endpoint, $payload)
    {
        $url = rtrim($base_url, '/') . $endpoint;
        $ch  = curl_init($url);
        if ($ch === false) {
            return ['success' => false, 'message' => 'Could not initialize HTTP request.'];
        }

        $headers = ['Content-Type: application/json'];
        if (!empty($api_key)) {
            $headers[] = 'X-Api-Key: ' . $api_key;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error     = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'message' => $error];
        }

        $ok = $http_code >= 200 && $http_code < 300;
        return [
            'success' => $ok,
            'message' => $ok ? 'Sent successfully.' : ('WAHA returned HTTP ' . $http_code . ($response ? ': ' . mb_strimwidth($response, 0, 200, '...') : '')),
        ];
    }
}
