<?php
namespace AuthVaultix;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

class NetworkAgent {
    public static function post($url, $payload) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_USERAGENT, "AuthVaultixClient/1.0");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($payload));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        if (!$response) return null;
        return json_decode($response);
    }
}

class PayloadBuilder {
    private $payload = [];
    
    public function __construct($action_type) {
        $this->payload['type'] = $action_type;
    }
    
    public function with_context($app_name, $owner_id, $session_id) {
        $this->payload['name'] = $app_name;
        $this->payload['ownerid'] = $owner_id;
        if (!empty($session_id)) {
            $this->payload['sessionid'] = $session_id;
        }
        return $this;
    }
    
    public function with_value($key, $value) {
        if ($value !== null) {
            $this->payload[$key] = $value;
        }
        return $this;
    }
    
    public function compile() {
        return $this->payload;
    }
}

class AuthVaultixCore {
    public $app_name;
    public $owner_id;
    public $secret;
    public $version;
    public $session_id;
    public $initialized = false;
    public $current_user;
    public $lastError = "";

    public function __construct($app_name, $owner_id, $secret, $version) {
        $this->app_name = $app_name;
        $this->owner_id = $owner_id;
        $this->secret = $secret;
        $this->version = $version;
        $this->session_id = $_SESSION['sessionid'] ?? null;
        if ($this->session_id) {
            $this->initialized = true;
        }
    }
    
    public function hwid() {
        // Since websites cannot access true hardware IDs, we generate a persistent browser cookie
        if (!isset($_COOKIE['authvaultix_web_hwid'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? "WEB";
            $agent = $_SERVER['HTTP_USER_AGENT'] ?? "UNKNOWN";
            $new_hwid = "WEB-" . substr(hash('sha256', $ip . $agent . time()), 0, 16);
            
            setcookie('authvaultix_web_hwid', $new_hwid, time() + (86400 * 365), "/");
            $_COOKIE['authvaultix_web_hwid'] = $new_hwid;
        }
        return $_COOKIE['authvaultix_web_hwid'];
    }

    public function ensure_ready() {
        if (!$this->initialized) {
            return $this->init();
        }
        return true;
    }

    public function init() {
        if ($this->initialized) return true;
        $payload = (new PayloadBuilder("init"))
            ->with_value("ver", $this->version)
            ->with_value("name", $this->app_name)
            ->with_value("ownerid", $this->owner_id)
            ->compile();
            
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            $this->session_id = $resp->sessionid;
            $_SESSION['sessionid'] = $this->session_id;
            $this->initialized = true;
            return true;
        }
        $this->lastError = $resp->message ?? "Unknown Init Error";
        return false;
    }

    public function authenticate_user($username, $password, $code = null) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("login"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("username", $username)
            ->with_value("pass", $password)
            ->with_value("is_web", "1")
            ->with_value("code", $code)
            ->compile();
            
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            $this->current_user = $resp->info;
            $_SESSION['user_data'] = (array)$resp->info;
            if (isset($resp->sessionid) && !empty($resp->sessionid)) {
                $this->session_id = $resp->sessionid;
                $_SESSION['sessionid'] = $this->session_id;
            }
            return true;
        }
        unset($_SESSION['sessionid']);
        $this->lastError = $resp->message ?? "Unknown Login Error";
        return false;
    }

    public function register_account($username, $password, $license, $email = "") {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("register"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("username", $username)
            ->with_value("pass", $password)
            ->with_value("key", $license)
            ->with_value("email", $email)
            ->with_value("is_web", "1")
            ->compile();
            
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            $this->current_user = $resp->info;
            $_SESSION['user_data'] = (array)$resp->info;
            if (isset($resp->sessionid) && !empty($resp->sessionid)) {
                $this->session_id = $resp->sessionid;
                $_SESSION['sessionid'] = $this->session_id;
            }
            return true;
        }
        unset($_SESSION['sessionid']);
        $this->lastError = $resp->message ?? "Unknown Register Error";
        return false;
    }
    
    public function validate_session() {
        if (!$this->ensure_ready() || !$this->session_id) return false;
        $payload = (new PayloadBuilder("check"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        return $resp && isset($resp->success) && $resp->success;
    }

    public function license_access($license, $code = null) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("license"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("key", $license)
            ->with_value("is_web", "1")
            ->with_value("code", $code)
            ->compile();
            
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            $this->current_user = $resp->info;
            $_SESSION['user_data'] = (array)$resp->info;
            if (isset($resp->sessionid) && !empty($resp->sessionid)) {
                $this->session_id = $resp->sessionid;
                $_SESSION['sessionid'] = $this->session_id;
            }
            return true;
        }
        unset($_SESSION['sessionid']);
        $this->lastError = $resp->message ?? "Unknown License Error";
        return false;
    }
    
    public function terminate_session() {
        if (!$this->ensure_ready()) return;
        $payload = (new PayloadBuilder("logout"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->compile();
        NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        unset($_SESSION['sessionid']);
        unset($_SESSION['user_data']);
        $this->session_id = null;
        $this->initialized = false;
    }
    
    public function update_username($new_username) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("changeusername"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("newUsername", $new_username)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            $this->terminate_session();
            return true;
        }
        $this->lastError = $resp->message ?? "Change Username Error";
        return false;
    }

    public function trigger_password_reset($username, $email) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("forgot"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("username", $username)
            ->with_value("email", $email)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            return true;
        }
        $this->lastError = $resp->message ?? "Forgot Password Error";
        return false;
    }

    public function apply_upgrade($username, $license) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("upgrade"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("username", $username)
            ->with_value("key", $license)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            return true;
        }
        $this->lastError = $resp->message ?? "Upgrade Error";
        return false;
    }

    public function send_log($message) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("log"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("message", $message)
            ->with_value("pcuser", $_SERVER['REMOTE_ADDR'] ?? 'Unknown')
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        return $resp && isset($resp->success) && $resp->success;
    }
    
    public function retrieve_file($fileid) {
        if (!$this->ensure_ready()) return null;
        $payload = (new PayloadBuilder("file"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("fileid", $fileid)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) {
            return base64_decode($resp->contents);
        }
        $this->lastError = $resp->message ?? "File Error";
        return null;
    }
    
    public function fetch_global_variable($var_id) {
        if (!$this->ensure_ready()) return null;
        $payload = (new PayloadBuilder("var"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("varid", $var_id)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return $resp->message;
        $this->lastError = $resp->message ?? "Var Error";
        return null;
    }

    public function fetch_user_variable($var_name) {
        if (!$this->ensure_ready()) return null;
        $payload = (new PayloadBuilder("getvar"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("var", $var_name)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return $resp->response;
        $this->lastError = $resp->message ?? "User Var Error";
        return null;
    }

    public function update_user_variable($var_name, $value) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("setvar"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("var", $var_name)
            ->with_value("data", $value)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return true;
        $this->lastError = $resp->message ?? "User Var Error";
        return false;
    }
    
    public function transmit_chat_message($message, $channel) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("chatsend"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("message", $message)
            ->with_value("channel", $channel)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return true;
        $this->lastError = $resp->message ?? "Chat Send Error";
        return false;
    }

    public function retrieve_chat_history($channel) {
        if (!$this->ensure_ready()) return null;
        $payload = (new PayloadBuilder("chatfetch"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("channel", $channel)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return $resp->messages;
        $this->lastError = $resp->message ?? "Chat Fetch Error";
        return null;
    }
    
    public function get_online_clients() {
        if (!$this->ensure_ready()) return null;
        $payload = (new PayloadBuilder("fetchonline"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return $resp->users;
        $this->lastError = $resp->message ?? "Fetch Online Error";
        return null;
    }

    public function enforce_ban($reason) {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("ban"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->with_value("reason", $reason)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return true;
        $this->lastError = $resp->message ?? "Ban Error";
        return false;
    }
    
    public function verify_blacklist() {
        if (!$this->ensure_ready()) return false;
        $payload = (new PayloadBuilder("checkblacklist"))
            ->with_context($this->app_name, $this->owner_id, $this->session_id)
            ->compile();
        $resp = NetworkAgent::post("https://authvaultix.com/api/1.0/", $payload);
        if ($resp && isset($resp->success) && $resp->success) return true;
        $this->lastError = $resp->message ?? "Blacklist Check Error";
        return false;
    }
}

class AuthVaultixClient {
    public $core;
    public $lastError = "";
    
    public function __construct($app_name, $owner_id, $secret, $version) {
        $this->core = new AuthVaultixCore($app_name, $owner_id, $secret, $version);
    }
    
    private function mapResponse($res) {
        $this->lastError = $this->core->lastError;
        return $res;
    }

    public function init() { return $this->mapResponse($this->core->init()); }
    public function login($u, $p, $code = null) { return $this->mapResponse($this->core->authenticate_user($u, $p, $code)); }
    public function check() { return $this->mapResponse($this->core->validate_session()); }
    public function register($u, $p, $l, $e = "") { return $this->mapResponse($this->core->register_account($u, $p, $l, $e)); }
    public function license($l, $code = null) { return $this->mapResponse($this->core->license_access($l, $code)); }
    public function log($m) { return $this->mapResponse($this->core->send_log($m)); }
    public function download($f) { return $this->mapResponse($this->core->retrieve_file($f)); }
    public function fetch_online() { return $this->mapResponse($this->core->get_online_clients()); }
    public function ban($r) { return $this->mapResponse($this->core->enforce_ban($r)); }
    public function logout() { $this->core->terminate_session(); }
    public function change_username($u) { return $this->mapResponse($this->core->update_username($u)); }
    public function check_blacklist() { return $this->mapResponse($this->core->verify_blacklist()); }
    public function upgrade($u, $l) { return $this->mapResponse($this->core->apply_upgrade($u, $l)); }
    public function forgot_password($u, $e) { return $this->mapResponse($this->core->trigger_password_reset($u, $e)); }
    public function get_global_var($v) { return $this->mapResponse($this->core->fetch_global_variable($v)); }
    public function get_var($v) { return $this->mapResponse($this->core->fetch_user_variable($v)); }
    public function set_var($v, $d) { return $this->mapResponse($this->core->update_user_variable($v, $d)); }
    public function chat_send($m, $c) { return $this->mapResponse($this->core->transmit_chat_message($m, $c)); }
    public function chat_fetch($c) { return $this->mapResponse($this->core->retrieve_chat_history($c)); }
}
?>
