<?php
require '../authvaultix.php';
require '../credentials.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['user_data'])) // if user not logged in
{
    header("Location: ../");
    exit();
}

$AuthVaultixApp = new AuthVaultix\api($name, $ownerid, $version, $secret);

function findSubscription($name, $list)
{
    for ($i = 0; $i < count($list); $i++) {
        if ($list[$i]->subscription == $name) {
            return true;
        }
    }
    return false;
}

$username = $_SESSION["user_data"]["username"];
$subscriptions = $_SESSION["user_data"]["subscriptions"];
$subscription = $_SESSION["user_data"]["subscriptions"][0]->subscription;
$expiry = $_SESSION["user_data"]["subscriptions"][0]->expiry;
$ip = $_SESSION["user_data"]["ip"];
$creationDate = $_SESSION["user_data"]["createdate"];
$lastLogin = $_SESSION["user_data"]["lastlogin"];

if (isset($_POST['logout'])) {
    $AuthVaultixApp->logout();
    session_destroy();
    header("Location: ../");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <title>Dashboard</title>
            <nav class="border-gray-200 px-4 lg:px-6 py-2.5 mb-14">
                <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl">
                    <a href="../" class="flex items-center">
                        <img src="https://api.authvaultix.com/assets/img/logo.webp"
                            class="mr-3 mt-2"
                            style="height:65px; width:auto;"
                            alt="AuthVaultix Logo" />
                    </a>
                    <div class="flex items-center lg:order-2">
                        <a href="https://github.com/AuthVaultix" target="_blank"
                            class="text-white focus:ring-0 font-medium rounded-lg text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 hover:opacity-60 transition duration-200 focus:outline-none focus:ring-gray-800">
                            Github idate
                        </a>
                        <a href="https://t.me/authvaultix" target="_blank"
                            class="text-white focus:ring-0 font-medium rounded-lg text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 bg-blue-600 hover:opacity-80 focus:outline-none focus:ring-blue-700 transition duration-200">
                            Telegram
                        </a>
                              <form method="post">
                                <button name="logout"class="bg-red-600 hover:bg-red-700 transition px-4 py-2 rounded-lg text-sm font-medium shadow-sm">Logout</button>
                              </form>
                    </div>
                </div>
            </nav>
    <script src="https://api.authvaultix.com/assets/css/unixtolocal.js"></script>
    <link rel="stylesheet" href="https://api.authvaultix.com/assets/css/output.css">
</head>
<body class="min-h-screen bg-[#0e0e13] text-white flex flex-col">
  <!-- Top Navigation -->
  <header class="backdrop-blur-lg py-4 mb-8">
    <div class="container mx-auto px-6 flex justify-between items-center">
      <h1 class="text-xl font-semibold tracking-wide text-white/90">User Dashboard</h1>
    </div>
  </header>
  <!-- Main Content -->
  <main class="container mx-auto px-6 py-10 max-w-4xl">
    <div class="bg-[#15151c] border border-white/10 rounded-xl p-8 shadow-xl">
      <h2 class="text-2xl font-semibold mb-6 text-white/90">Account Information</h2>
      <div class="space-y-3 text-white/80 text-sm">
        <p><span class="font-semibold text-white/90">Username:</span> <?= $username; ?></p>
        <p>
          <span class="font-semibold text-white/90">IP Address:</span>
          <span class="blur-sm hover:blur-none transition"><?= $ip; ?></span>
        </p>
          <p><span class="font-semibold text-white/90">Account Created:</span> <?= date('Y-m-d H:i:s', (int)$creationDate);?></p>
          <p><span class="font-semibold text-white/90">Last Login:</span> <?= date('Y-m-d H:i:s', (int)$lastLogin);?></p>
        <p>
          <span class="font-semibold text-white/90">Subscription Check:</span>
          <code class="text-blue-400 bg-blue-400/10 px-1.5 py-0.5 rounded-md">default</code>
          = <?= findSubscription("default", $subscriptions) ? '<span class="text-green-400">Active</span>' : '<span class="text-red-400">Not Active</span>'; ?>
        </p>
      </div>
      <hr class="border-white/10 my-6">
      <h3 class="text-lg font-semibold text-white/90 mb-3">Your Subscriptions</h3>
      <?php foreach ($subscriptions as $index => $sub): ?>
        <div class="bg-white/5 p-4 rounded-lg border border-white/10 mb-3">
          <p class="text-white/80 text-sm">#<?= $index+1; ?> <span class="font-semibold text-white/90"><?= $sub->subscription; ?></span></p>
          <p class="text-gray-400 text-sm">Expires: <span class="text-blue-400"><script>document.write(convertTimestamp(<?= $sub->expiry; ?>))</script></span></p>
        </div>
      <?php endforeach; ?>
    </div>
  </main>
</body>
</html>