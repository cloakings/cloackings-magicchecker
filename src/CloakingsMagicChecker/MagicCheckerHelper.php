<?php

/** @noinspection OneTimeUseVariablesInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection PhpUnnecessaryLocalVariableInspection */

namespace Cloakings\CloakingsMagicChecker;

use Gupalo\Json\Json;
use stdClass;

// TODO: think why it may be needed
class MagicCheckerHelper
{
    private const REQUEST_LIVE_TIME = 3600;

    public function __construct(
        private string $campaignId,
        private string $encryptionKey,
        private string $paramName = '_hfsess',
    ) {
    }

    public function run(): void
    {
        if (isset($_POST['click'])) {
            $this->runClick();
        } elseif (isset($_GET[$this->paramName])) {
            $this->runParamName();
        } else {
            $result = $this->isBlocked();
            if ($result->hasResponce && !isset($result->error_message)) {
                if (!$result->isBlocked && isset($result->js)) {
                    $clickdata = $this->generate_click_id($result);
                    $insert_script = implode('', [
                        '<noscript>',
                        '<style>html,body{visibility:hidden;background-color:#ffffff!important;}</style>',
                        '<meta http-equiv="refresh" content="0; url='.$this->_redirectPage($result->sp, $result->safeUrlType, true).'">',
                        '</noscript>',
                        '<script type="text/javascript">',
                        'window.click_id="'.$clickdata[0].'";window.qt14="'.$clickdata[1].'";window.fh76="'.$clickdata[2].'";'.$result->js,
                        '</script>',
                    ]);
                    if (
                        ($result->show_first == 1) && ($result->safeUrlType === 'redirect') ||
                        ($result->show_first == 2) && ($result->moneyUrlType === 'redirect')
                    ) {
                        print '<html><head><title></title><meta charset="UTF-8">'.$insert_script.'</head><body></body></html>';
                    } else {
                        $include_file = ($result->show_first == 1) ? $result->sp : $result->mp;
                        $include_file = file_get_contents(dirname(__FILE__).'/'.$this->_includeFileName($include_file));
                        if (str_contains($include_file, '<head>')) {
                            $include_file = str_ireplace('<head>', '<head>'.$insert_script, $include_file);
                        } else {
                            $include_file = str_ireplace('<body', '<head>'.$insert_script.'</head><body', $include_file);
                        }
                        if (str_contains($include_file, '<?')) {
                            eval('?>'.$include_file.'<?php ');
                        } else {
                            print $include_file;
                        }
                    }
                } elseif ($result->urlType === 'redirect') {
                    $this->_redirectPage($result->url, $result->send_params);
                } else {
                    include $this->_includeFileName($result->url);
                }
            } else {
                die('Error: '.$result->errorMessage);
            }
        }
    }

    private function runClick(): void
    {
        $click_id = $this->decrypt($_POST['click']);
        if (str_contains($click_id, '||')) {
            $cdata = explode('||', $click_id);
            if ((int)$cdata[3] + self::REQUEST_LIVE_TIME >= time()) {
                $update_data = [];
                $tp = isset($_POST['tp']) ? trim($_POST['tp']) : null;
                $plr = isset($_POST['plr']) ? trim($_POST['plr']) : null;
                $lls = isset($_POST['lls']) ? (int)$_POST['lls'] : null;
                if ($cdata[5] !== 'N' && $plr != $cdata[12]) {
                    $update_data['r'] = 'pn';
                } elseif ($cdata[6] !== 'N' && $lls === 1) {
                    $update_data['r'] = 'lls';
                } elseif ($tp && $cdata[4] !== 'N' && $cdata[4]) {
                    $tpz = explode('&', $cdata[4]);
                    if (!($tp >= $tpz[0] && $tp <= $tpz[1])) {
                        $update_data['r'] = 'tp';
                    }
                }

                if (isset($update_data['r'])) {
                    $update_data['click_id'] = $cdata[0];
                    if ($tp) {
                        $update_data['tp'] = $tp;
                    }
                    if (isset($_POST['pn'])) {
                        $update_data['pn'] = $_POST['pn'];
                    }
                    if (isset($_POST['or'])) {
                        $update_data['or'] = $_POST['or'];
                    }
                    if (isset($_POST['rn'])) {
                        $update_data['rn'] = $_POST['rn'];
                    }
                    $this->sendRequest($update_data, 'update');

                    if (
                        $cdata[10] == 1 ||
                        (
                            $cdata[10] == 2 &&
                            (
                                $cdata[7] == 2 ||
                                (
                                    $cdata[7] == 1 &&
                                    $cdata[8]
                                )
                            )
                        )
                    ) {
                        print "<script>location.href=\"".$this->rebuildParams($cdata, 2)."\";</script>";
                    }
                } else {
                    if (($cdata[9] == 1) || (($cdata[9] == 2) && (($cdata[7] == 1) || (($cdata[7] == 2) && $cdata[8])))) {
                        print "<script>location.href=\"".$this->rebuildParams($cdata)."\";</script>";
                    }
                }
            } else {
                if (($cdata[10] == 1) || (($cdata[10] == 2) && (($cdata[7] == 2) || (($cdata[7] == 1) && $cdata[8])))) {
                    print "<script>location.href=\"".$this->rebuildParams($cdata, 2)."\";</script>";
                }
            }
        }
    }

    private function runParamName(): void
    {
        $encdata = $this->decrypt($_GET[$this->paramName]);
        $show_404 = true;
        if (str_contains($encdata, '||')) {
            $cdata = explode('||', $encdata);
            if (
                count($cdata) === 3 &&
                $_SERVER['REMOTE_ADDR'] === $cdata[1] &&
                (int)$cdata[0] + self::REQUEST_LIVE_TIME >= time()
            ) {
                include($this->_includeFileName($cdata[2]));
                $show_404 = false;
            }
        }
        if ($show_404) {
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?: 'HTTP/1.1';
            header($protocol." 404 Not Found");
            print '<h1>Page not found</h1>';
            die();
        }
    }

    private function encrypt($encrypt): string
    {
        $plaintext = $encrypt;
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt(data: $plaintext, cipher_algo: $cipher, passphrase: $this->encryptionKey, options: OPENSSL_RAW_DATA, iv: $iv);
        $hmac = hash_hmac(algo: 'sha256', data: $ciphertext_raw, key: $this->encryptionKey, binary: true);
        $ciphertext = base64_encode($iv.$hmac.$ciphertext_raw);

        return $ciphertext;
    }

    private function decrypt($decrypt): string
    {
        $ciphertext = $decrypt;
        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $sha2len = 32;
        $hmac = substr(string: $c, offset: $ivlen, length: $sha2len);
        $ciphertext_raw = substr(string: $c, offset: $ivlen + $sha2len);
        $plaintext = openssl_decrypt(data: $ciphertext_raw, cipher_algo: $cipher, passphrase: $this->encryptionKey, options: OPENSSL_RAW_DATA, iv: $iv);
        $calcmac = hash_hmac(algo: 'sha256', data: $ciphertext_raw, key: $this->encryptionKey, binary: true);

        return hash_equals($hmac, $calcmac) ? $plaintext : '';
    }

    private function generate_click_id($result): array
    {
        $p = microtime();
        $r = md5(str_shuffle($this->encryptionKey.$p.$this->campaignId));
        $v1 = substr($r, 0, 16);
        $v2 = substr($r, 16, 31);

        return [
            $this->encrypt(
                $result->click_id.'||'.
                (($result->moneyUrlType === 'redirect') ? $this->_redirectPage($result->mp, $result->moneySendParams, true) : $result->mp).'||'.
                (($result->safeUrlType === 'redirect') ? $this->_redirectPage($result->sp, $result->safeSendParams, true) : $result->sp).'||'.
                time().'||'.
                ($result->tp ?? 'N').'||'.
                ($result->mms ?? 'N').'||'.
                ($result->lls ?? 'N').'||'.
                $result->show_first.'||'.
                $result->hide_script.'||'.
                ($result->moneyUrlType === 'redirect' ? 1 : 2).'||'.
                ($result->safeUrlType === 'redirect' ? 1 : 2).'||'.
                $v1.'||'.
                $v2
            ),
            $v1,
            $v2,
        ];
    }

    private function rebuildParams(array $data, int $page = 1)
    {
        if (
            ($page === 1 && (int)$data[9] === 2) ||
            ($page === 2 && (int)$data[10] === 2)
        ) {
            $params = [time(), $_SERVER['REMOTE_ADDR'], $data[$page]];
            $encoded = $this->encrypt(implode('||', $params));

            return $_SERVER['REQUEST_URI'].(str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?').$this->paramName.'='.urlencode($encoded);
        }

        return $data[$page];
    }

    private function sendRequest($data, $path = 'index')
    {
        $headers = ['adapi' => '2.2'];
        $data_to_post = ["cmp" => $this->campaignId, "headers" => $data, "adapi" => '2.2', "sv" => '13997.3'];

        $ch = curl_init("http://check.magicchecker.com/v2.2/".$path.'.php');
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data_to_post));
        $output = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ((int)$info['http_code'] !== 200 || (string)$output === '') {
            $curl_err_num = curl_errno($ch);
            curl_close($ch);

            if ($curl_err_num) {
                header($_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable');
                print 'cURL error '.$curl_err_num.': '.match ($curl_err_num) {
                        2 => "Can't init curl.",
                        6 => "Can't resolve server's DNS of our domain. Please contact your hosting provider and tell them about this issue.",
                        7 => "Can't connect to the server.",
                        28 => "Operation timeout. Check you DNS setting.",
                        default => "Error code: $curl_err_num . Check if php cURL library installed and enabled on your server."
                    };
            } elseif ((int)$info['http_code'] === 500) {
                header($_SERVER['SERVER_PROTOCOL'].' 503 Service Unavailable');
                print '<h1>503 Service Unavailable</h1>';
            } else {
                header($_SERVER['SERVER_PROTOCOL'].' '.$info['http_code']);
                print '<h1>Error '.$info['http_code'].'</h1>';
            }
            die();
        }
        curl_close($ch);

        return $output;
    }

    private function isBlocked(): stdClass
    {
        $result = new stdClass();
        $result->hasResponce = false;
        $result->isBlocked = false;
        $result->errorMessage = '';
        $data_headers = [];

        foreach ($_SERVER as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $data_headers[$name] = (strlen($value) < 1024 || in_array($name, ['HTTP_REFERER', 'QUERY_STRING', 'REQUEST_URI', 'HTTP_USER_AGENT'], true))
                ? $value
                : 'TRIMMED: '.substr($value, 0, 1024);
        }

        $output = $this->sendRequest($data_headers);
        if ($output) {
            $result->hasResponce = true;
            $answer = Json::toArray($output);
            if (isset($answer['ban']) && ((int)$answer['ban'] === 1)) {
                die();
            }

            if ((int)$answer['success'] === 1) {
                foreach ($answer as $ak => $av) {
                    $result->{$ak} = $av;
                }
            } else {
                $result->errorMessage = $answer['errorMessage'];
            }
        }

        return $result;
    }

    private function _redirectPage($url, $send_params, $return_url = false): string
    {
        if ($send_params && $_SERVER['QUERY_STRING'] !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?').$_SERVER['QUERY_STRING'];
        }

        if ($return_url) {
            return $url;
        }

        header("Location: $url", true, 302);

        return '';
    }

    private function _includeFileName($url)
    {
        if (str_contains($url, '/')) {
            $url = ltrim(strrchr($url, '/'), '/');
        }
        if (str_contains($url, '?')) {
            $url = explode('?', $url);
            $url = $url[0];
        }

        return $url;
    }
}
