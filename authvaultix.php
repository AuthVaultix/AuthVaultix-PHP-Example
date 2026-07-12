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
        if ($response === false) {
            $err = curl_error($curl);
            curl_close($curl);
            return (object)[
                'success' => false,
                'message' => "cURL Error: " . $err
            ];
        }
        curl_close($curl);
        
        $decoded = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return (object)[
                'success' => false,
                'message' => "JSON Decode Error: " . json_last_error_msg() . ". Response was: " . substr($response, 0, 100)
            ];
        }
        return $decoded;
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

class SystemInfoCollector {
    private static function is_windows() {
        return defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Windows' : strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    private static function is_darwin() {
        return defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Darwin' : strtoupper(substr(PHP_OS, 0, 3)) === 'DAR';
    }

    private static function is_linux() {
        return defined('PHP_OS_FAMILY') ? PHP_OS_FAMILY === 'Linux' : strtoupper(substr(PHP_OS, 0, 3)) === 'LIN';
    }

    public static function get_os_version() {
        if (self::is_windows()) {
            $caption = @shell_exec('powershell -Command "(Get-CimInstance Win32_OperatingSystem).Caption"');
            $caption = trim($caption);
            if (strpos($caption, "Microsoft ") === 0) {
                $caption = substr($caption, 10);
            }
            $version = @shell_exec('powershell -Command "(Get-CimInstance Win32_OperatingSystem).Version"');
            $version = trim($version);
            if (!empty($caption) && !empty($version)) {
                return "$caption ($version)";
            } elseif (!empty($caption)) {
                return $caption;
            }
            return "Windows";
        } elseif (self::is_darwin()) {
            $version = @shell_exec('sw_vers -productVersion');
            $version = trim($version);
            return !empty($version) ? "macOS ($version)" : "macOS";
        } elseif (self::is_linux()) {
            $version = @shell_exec('uname -sr');
            $version = trim($version);
            return !empty($version) ? $version : "Linux";
        }
        return "Unknown OS";
    }

    public static function get_platform() {
        return "native";
    }

    public static function get_device_type() {
        return "Desktop";
    }

    public static function get_architecture() {
        if (self::is_windows()) {
            return strtoupper(getenv('PROCESSOR_ARCHITECTURE') ?: 'X64');
        } else {
            $arch = @shell_exec('uname -m');
            $arch = trim($arch);
            return !empty($arch) ? strtoupper($arch) : "X64";
        }
    }

    public static function get_cpu_cores() {
        if (self::is_windows()) {
            $physical_cores = @shell_exec('powershell -Command "(Get-CimInstance Win32_Processor).NumberOfCores"');
            $physical_cores = trim($physical_cores);
            $logical_processors = getenv('NUMBER_OF_PROCESSORS') ?: "2";
            $cores = empty($physical_cores) ? $logical_processors : $physical_cores;
            return "$cores Cores / $logical_processors Threads";
        } else {
            $logical = "2";
            if (self::is_darwin()) {
                $logical = @shell_exec('sysctl -n hw.ncpu');
            } else {
                $logical = @shell_exec('nproc');
            }
            $logical = trim($logical);
            if (empty($logical)) $logical = "2";
            return "$logical Cores / $logical Threads";
        }
    }

    public static function get_ram_gb() {
        if (self::is_windows()) {
            $ram = @shell_exec('powershell -Command "[Math]::Round((Get-CimInstance Win32_ComputerSystem).TotalPhysicalMemory / 1GB)"');
            $ram = trim($ram);
            return !empty($ram) ? $ram : "0";
        } elseif (self::is_darwin()) {
            $bytes = @shell_exec('sysctl -n hw.memsize');
            $bytes = trim($bytes);
            if (ctype_digit($bytes) && $bytes > 0) {
                return strval(intval($bytes / (1024 * 1024 * 1024)));
            }
            return "0";
        } else {
            $kb = @shell_exec("grep MemTotal /proc/meminfo | awk '{print \$2}'");
            $kb = trim($kb);
            if (ctype_digit($kb) && $kb > 0) {
                return strval(intval($kb / (1024 * 1024)));
            }
            return "0";
        }
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
            ->with_value("hwid", $this->hwid())
            ->with_value("os", SystemInfoCollector::get_os_version())
            ->with_value("platform", SystemInfoCollector::get_platform())
            ->with_value("device", SystemInfoCollector::get_device_type())
            ->with_value("architecture", SystemInfoCollector::get_architecture())
            ->with_value("cpu_cores", SystemInfoCollector::get_cpu_cores())
            ->with_value("ram", SystemInfoCollector::get_ram_gb())
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
