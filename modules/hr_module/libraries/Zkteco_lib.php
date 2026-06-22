<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * ZKTeco Biometric Device Library
 * Communicates with ZKTeco devices via TCP on port 4370
 * using the ZKTeco binary protocol.
 */
class Zkteco_lib
{
    private $socket   = null;
    private $session  = 0;
    private $reply    = 0;
    private $timeout  = 10;

    // Protocol constants
    const CMD_CONNECT      = 1000;
    const CMD_DISCONNECT   = 1001;
    const CMD_GETTIMEINFO  = 201;
    const CMD_ATTLOG_RRQ   = 13;
    const CMD_FREE_DATA    = 1502;
    const CMD_READ_FILE    = 1504;
    const CMD_ACK_OK       = 2000;
    const CMD_ACK_ERROR    = 2001;
    const USHRT_MAX        = 65535;

    /**
     * Fetch attendance logs from device.
     * Returns ['success'=>bool, 'records'=>array, 'message'=>string]
     */
    public function fetch_attendance($ip, $port = 4370)
    {
        try {
            if (!$this->_connect($ip, $port)) {
                return ['success' => false, 'records' => [], 'message' => 'Failed to connect to device.'];
            }

            $records = $this->_get_attendance_logs();
            $this->_disconnect();

            return [
                'success' => true,
                'records' => $records,
                'message' => count($records) . ' records fetched.',
            ];
        } catch (Exception $e) {
            $this->_disconnect();
            return ['success' => false, 'records' => [], 'message' => $e->getMessage()];
        }
    }

    private function _connect($ip, $port)
    {
        $this->socket = @fsockopen($ip, $port, $errno, $errstr, $this->timeout);
        if (!$this->socket) return false;
        stream_set_timeout($this->socket, $this->timeout);

        // Send CMD_CONNECT
        $this->session = 0;
        $this->reply   = 0;
        $buf = $this->_build_packet(self::CMD_CONNECT, '');
        fwrite($this->socket, $buf);
        $response = fread($this->socket, 1024);
        if (!$response) return false;

        $cmd = $this->_parse_cmd($response);
        if ($cmd !== self::CMD_ACK_OK) return false;

        $this->session = $this->_parse_session($response);
        return true;
    }

    private function _disconnect()
    {
        if ($this->socket) {
            $buf = $this->_build_packet(self::CMD_DISCONNECT, '');
            @fwrite($this->socket, $buf);
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    private function _get_attendance_logs()
    {
        $records = [];

        // Request free buffer
        $buf = $this->_build_packet(self::CMD_FREE_DATA, '');
        fwrite($this->socket, $buf);
        fread($this->socket, 1024);

        // Request attendance log file
        $payload = pack('a8', 'ATTLOG.DAT');
        $buf = $this->_build_packet(self::CMD_READ_FILE, $payload);
        fwrite($this->socket, $buf);

        $data = '';
        $size_buf = fread($this->socket, 1024);
        if (!$size_buf) return $records;

        $cmd = $this->_parse_cmd($size_buf);
        if ($cmd === self::CMD_ACK_OK) {
            // Single-packet response
            $data = substr($size_buf, 8);
        } else {
            // Multi-packet: first 4 bytes after header = total size
            $total = unpack('V', substr($size_buf, 8, 4))[1] ?? 0;
            if ($total <= 0) return $records;

            $received = 0;
            while ($received < $total) {
                $chunk = fread($this->socket, 65536);
                if ($chunk === false) break;
                // Strip 8-byte header from data packets
                $data .= (strlen($chunk) > 8) ? substr($chunk, 8) : $chunk;
                $received += strlen($chunk);
            }
        }

        // Each attendance record is 40 bytes
        $rec_size = 40;
        $count = intdiv(strlen($data), $rec_size);
        for ($i = 0; $i < $count; $i++) {
            $rec = substr($data, $i * $rec_size, $rec_size);
            if (strlen($rec) < $rec_size) continue;

            $parsed = unpack('a24user_id/vtimestamp_raw/vtype/vunknown', $rec);
            $user_id   = rtrim($parsed['user_id'], "\x00");
            $ts_raw    = $parsed['timestamp_raw'];

            // ZKTeco timestamp encoding: seconds since 2000-01-01
            $timestamp = mktime(0, 0, 0, 1, 1, 2000) + $ts_raw;
            $records[] = [
                'user_id'   => $user_id,
                'timestamp' => date('Y-m-d H:i:s', $timestamp),
                'type'      => $parsed['type'], // 0=check-in, 1=check-out, 4=manual, etc.
            ];
        }

        return $records;
    }

    private function _build_packet($cmd, $data)
    {
        $this->reply = ($this->reply + 1) % self::USHRT_MAX;
        $len = 8 + strlen($data);
        return pack('vvvv', $cmd, 0, $this->session, $this->reply) . $data;
    }

    private function _parse_cmd($buf)
    {
        if (strlen($buf) < 2) return 0;
        $r = unpack('v', substr($buf, 0, 2));
        return $r[1] ?? 0;
    }

    private function _parse_session($buf)
    {
        if (strlen($buf) < 6) return 0;
        $r = unpack('v', substr($buf, 4, 2));
        return $r[1] ?? 0;
    }
}
