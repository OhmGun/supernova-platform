<?php
class RouterosAPI {
    public $debug = false;
    public $connected = false;
    public $ssl = false;
    public $lastError = '';
    private $port = 8728;
    private $timeout = 2;
    private $socket = '';
    private $error_no;
    private $error_str;
    private $loggingIn = false;

    public function connect($ip, $login, $password, $port = 0) {
        if ($port > 0 && $port <= 65535) {
            $this->port = $port;
        } else {
            $this->port = 8728;
        }
        $this->lastError = '';
        for ($i = 1; $i <= 2; $i++) {
            $this->connected = false;
            if ($this->ssl) {
                $ctx = stream_context_create(['ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]]);
                $this->socket = @stream_socket_client('ssl://' . $ip . ':' . $this->port, $this->error_no, $this->error_str, $this->timeout, STREAM_CLIENT_CONNECT, $ctx);
            } else {
                $this->socket = @fsockopen($ip, $this->port, $this->error_no, $this->error_str, $this->timeout);
            }
            if ($this->socket) {
                $this->loggingIn = true;
                @stream_set_timeout($this->socket, $this->timeout);
                $this->write('/login');
                $this->write('=name=' . $login, false);
                $this->write('=password=' . $password);
                $resp = $this->readLoginShort();

                if (isset($resp['ret']) && $resp['ret'] !== '') {
                    // RouterOS 3.22+ (handshake md5 challenge)
                    $md = md5(chr(0) . $password . pack('H*', $resp['ret']));
                    $this->write('/login', false);
                    $this->write('=name=' . $login, false);
                    $this->write('=response=00' . $md);
                    $auth = $this->readLoginShort();
                    $this->connected = empty($auth['trap']);
                    if (!$this->connected) {
                        $this->lastError = 'Login API ditolak (username/password/perizinan API salah atau sesi terkunci).';
                    }
                } else {
                    // RouterOS lama / tanpa challenge
                    $this->connected = empty($resp['trap']);
                    if (!$this->connected) {
                        $this->lastError = 'Login API ditolak (cek kredensial / perizinan API).';
                    }
                }

                if ($this->connected) {
                    break;
                }
                @fclose($this->socket);
            } else {
                $this->lastError = 'Tidak bisa membuka koneksi TCP ke ' . $ip . ':' . $this->port . ' (' . (int)$this->error_no . ') ' . $this->error_str;
            }
            usleep(200000);
        }
        $this->loggingIn = false;
        if ($this->connected) {
            $this->lastError = '';
        }
        return $this->connected;
    }

    // Membaca respons login singkat: kata atribut sampai !done/!trap
    private function readLoginShort() {
        $attrs = array();
        while (true) {
            $word = $this->readWord();
            if ($word === false) break;
            if (strpos($word, '=') === 0) {
                $eq = strpos($word, '=', 1);
                $key = ($eq === false) ? substr($word, 1) : substr($word, 1, $eq - 1);
                $val = ($eq === false) ? '' : substr($word, $eq + 1);
                $attrs[$key] = $val;
            } elseif ($word === '!done') {
                break;
            } elseif ($word === '!trap' || $word === '!fatal') {
                $attrs['trap'] = true;
                break;
            }
        }
        return $attrs;
    }

    public function disconnect() {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->connected = false;
    }

    public function write($com, $arg1 = true) {
        if (!$this->connected && !$this->loggingIn) return false;
        $ret = $this->sendWord($com);
        if ($arg1) {
            if (is_array($arg1)) {
                foreach ($arg1 as $key => $value) {
                    $ret &= $this->sendWord("$key=$value");
                }
            } else {
                $ret &= $this->sendWord($arg1);
            }
        }
        $ret &= $this->sendWord('');
        return $ret;
    }

    public function read($parse = true) {
        $response = array();
        $current = array();
        while (true) {
            $word = $this->readWord();
            if ($word === false) break;
            if (strpos($word, '!') === 0) {
                if ($word === '!done') {
                    if (!empty($current)) {
                        $response[] = $current;
                    }
                    break;
                }
                if ($word === '!re') {
                    if (!empty($current)) {
                        $response[] = $current;
                    }
                    $current = array();
                } elseif ($word === '!trap' || $word === '!fatal') {
                    $response['error'] = $word;
                    if (!empty($current)) {
                        $response[] = $current;
                    }
                    break;
                }
            } elseif (strpos($word, '=') === 0) {
                $eq = strpos($word, '=', 1);
                $key = ($eq === false) ? substr($word, 1) : substr($word, 1, $eq - 1);
                $val = ($eq === false) ? '' : substr($word, $eq + 1);
                $current[$key] = $val;
            }
        }
        return $response;
    }

    private function sendWord($word) {
        if (!$this->socket) return false;
        fwrite($this->socket, $this->encodeLength(strlen($word)) . $word);
        return true;
    }

    private function readWord() {
        if (!$this->socket) return false;
        $length = $this->decodeLength();
        if ($length > 0) {
            $data = '';
            $bytes = 0;
            while ($bytes < $length) {
                $buffer = fread($this->socket, $length - $bytes);
                $bytes += strlen($buffer);
                $data .= $buffer;
            }
            return $data;
        } else {
            return '';
        }
    }

    private function parseResponse($response) {
        if (is_array($response)) return $response;
        $result = array();
        if ($response != '') {
            $chunks = explode('=', $response);
            if ($chunks[0] == '!fatal') {
                return '!fatal';
            }
            $result[0] = $chunks[0];
            if (isset($chunks[1])) {
                $value = explode('=', $chunks[1]);
                $result[1] = $value[0];
                if (isset($value[1])) {
                    $result[2] = $value[1];
                }
            }
        }
        return $result;
    }

    private function decodeLength() {
        $byte = ord(fread($this->socket, 1));
        if ($byte & 128) {
            if (($byte & 192) == 128) {
                $byte &= ~192;
                $byte = ($byte << 8) + ord(fread($this->socket, 1));
            } else if (($byte & 224) == 192) {
                $byte &= ~224;
                $byte = ($byte << 8) + ord(fread($this->socket, 1));
                $byte = ($byte << 8) + ord(fread($this->socket, 1));
            } else if (($byte & 240) == 224) {
                $byte &= ~240;
                $byte = ($byte << 8) + ord(fread($this->socket, 1));
                $byte = ($byte << 8) + ord(fread($this->socket, 1));
                $byte = ($byte << 8) + ord(fread($this->socket, 1));
            }
        }
        return $byte;
    }

    private function encodeLength($length) {
        if ($length < 0x80) return chr($length);
        if ($length < 0x4000) return chr(($length >> 8) | 0x80) . chr($length & 0xFF);
        if ($length < 0x200000) return chr(($length >> 16) | 0xc0) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        if ($length < 0x10000000) return chr(($length >> 24) | 0xe0) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        return chr(0xf0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
    }
}
?>