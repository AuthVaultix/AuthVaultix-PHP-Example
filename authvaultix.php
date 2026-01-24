<?php
namespace AuthVaultix;

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

class api
{
    public $name;
    public $ownerid;
    public $secret;
    public $version;

    public function __construct(string $name, string $ownerid, string $secret, string $version)
    {
        $this->name = $name;
        $this->ownerid = $ownerid;
        $this->secret = $secret;     
        $this->version = $version;   
    }


        function init()
        {
            $data = array(
                "type" => "init",
                "name" => $this->name,
                "ownerid" => $this->ownerid,
                "secret" => $this->secret,
                "version" => $this->version
            );

            $json = $this->req($data);

            if ($json === null)
                die("API Response NULL — Check Internet / API URL");

            if (!$json->success)
                die($json->message);

            $_SESSION['sessionid'] = $json->sessionid;
        }

        function logout(){
            $data = array(
                "type" => "logout",
                "sessionid" => $_SESSION['sessionid'],
                "name" => $this->name,
                "ownerid" => $this->ownerid
            );

            $response = $this->req($data);
        }

        function login($username, $password, $code = null)
        {
            $data = array(
                "type" => "login",
                "username" => $username,
                "pass" => $password,
                "sessionid" => $_SESSION['sessionid'],
                "name" => $this->name,
                "ownerid" => $this->ownerid
            );

            if (!is_null($code)) {
                $data["code"] = $code;
            }

            $json = $this->req($data); // ✅ Already decoded object

            if (!$json->success) {
                unset($_SESSION['sessionid']);
                $this->error($json->message);
            } else {
                $_SESSION["user_data"] = (array)$json->info;
            }

            return $json->success;
        }


        function register($username, $password, $license)
        {
            $data = array(
                "type" => "register",
                "username" => $username,
                "pass" => $password,
                "key" => $license,
                "sessionid" => $_SESSION['sessionid'],
                "name" => $this->name,
                "ownerid" => $this->ownerid
            );

            $json = $this->req($data); // ✅ No json_decode here

            if (!$json->success) {
                unset($_SESSION['sessionid']);
                $this->error($json->message);
            } else {
                $_SESSION["user_data"] = (array)$json->info;
            }

            return $json->success;
        }



        function license($license, $code = null)
        {
            $data = array(
                "type" => "license",
                "key" => $license,
                "sessionid" => $_SESSION['sessionid'],
                "name" => $this->name,
                "ownerid" => $this->ownerid
            );

            if (!is_null($code)) {
                $data["code"] = $code;
            }

            $json = $this->req($data); // ✅ No json_decode here

            if (!$json->success) {
                unset($_SESSION['sessionid']);
                $this->error($json->message);
            } else {
                $_SESSION["user_data"] = (array)$json->info;
            }

            return $json->success;
        }


        private function req($data)
        {
            $curl = curl_init("https://api.authvaultix.com/api/1.2/");
            curl_setopt($curl, CURLOPT_USERAGENT, "AUTHECURE PHP API");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

            $response = curl_exec($curl);

            curl_close($curl);

            return json_decode($response); 
        }


            public function error($msg)
            {
                echo '
                        <script type=\'text/javascript\'>
                        
                        const notyf = new Notyf();
                        notyf
                        .error({
                            message: \'' . addslashes($msg) . '\',
                            duration: 3500,
                            dismissible: true
                        });                
                        
                        </script>
                        ';
            }

            public function success($msg)
            {
                echo '
                        <script type=\'text/javascript\'>
                        
                        const notyf = new Notyf();
                        notyf
                        .success({
                            message: \'' . addslashes($msg) . '\',
                            duration: 3500,
                            dismissible: true
                        });                
                        
                        </script>
                        ';
            }
        }
?>
