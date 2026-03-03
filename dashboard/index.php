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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AuthVaultix Example</title>
    <!-- Tailwind CSS (via CDN for example dashboard) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://api.authvaultix.com/assets/css/unixtolocal.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #09090b;
            /* Very dark background */
            color: #f4f4f5;
        }

        /* Nav Styling */
        .glass-nav {
            background: rgba(9, 9, 11, 0.7);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Card container */
        .glass-card {
            background: linear-gradient(180deg, rgba(24, 24, 27, 0.5) 0%, rgba(24, 24, 27, 0.7) 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.5);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        }

        /* Stat Blocks */
        .stat-block {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 0.75rem;

            transition: all 0.2s ease;
        }

        .stat-block:hover {
            background: rgba(255, 255, 255, 0.04);

        }

        .stat-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        /* Custom Button */
        .btn-logout {
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.2);
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2);
            color: #fee2e2;
            border-color: rgba(239, 68, 68, 0.4);
        }

        /* Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
    </style>
</head>

<body class="antialiased min-h-screen flex flex-col pt-16">

    <!-- Navigation -->
    <nav class="glass-nav fixed top-0 w-full z-50">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-3">
                    <img class="h-8 w-auto" src="https://api.authvaultix.com/assets/img/logo.webp"
                        alt="AuthVaultix Logo">
                    <span class="font-semibold text-lg tracking-wide hidden sm:block">AuthVaultix</span>
                </div>

                <!-- Right Nav -->
                <div class="flex items-center gap-4">
                    <a href="https://github.com/AuthVaultix" target="_blank"
                        class="text-sm text-gray-400 hover:text-white transition-colors duration-200 hidden sm:flex items-center gap-2">
                        <i class="bi bi-github"></i> GitHub
                    </a>
                    <a href="https://discord.gg/muHy3qxcub" target="_blank"
                        class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors duration-200 hidden sm:flex items-center gap-2 mr-2">
                        <i class="bi bi-discord"></i> Discord
                    </a>

                    <form method="post" class="m-0">
                        <button type="submit" name="logout"
                            class="btn-logout flex items-center gap-2 px-4 py-1.5 rounded-lg text-sm font-medium">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8 max-w-6xl">

        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white mb-2">Welcome back,
                <?= htmlspecialchars($username) ?>.
            </h1>
            <p class="text-gray-400 text-sm">Here is the overview of your example application session.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Left Column: Session Details -->
            <div class="lg:col-span-2 flex flex-col gap-6">

                <div class="glass-card">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <i class="bi bi-shield-check text-blue-500"></i> Session Details
                    </h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="stat-block">
                            <div class="stat-icon bg-zinc-800 text-zinc-400">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Username</p>
                                <p class="text-base font-semibold text-white mt-1 truncate">
                                    <?= htmlspecialchars($username) ?>
                                </p>
                            </div>
                        </div>

                        <div class="stat-block">
                            <div class="stat-icon bg-zinc-800 text-zinc-400">
                                <i class="bi bi-hdd-network"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">IP Address</p>
                                <p class="text-base font-mono text-gray-300 mt-1 blur-sm hover:blur-none transition-all duration-300 cursor-pointer"
                                    title="Hover to reveal"><?= htmlspecialchars($ip) ?></p>
                            </div>
                        </div>

                        <div class="stat-block">
                            <div class="stat-icon bg-zinc-800 text-zinc-400">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Account Created
                                </p>
                                <p class="text-sm font-medium text-gray-300 mt-1">
                                    <?= date('M j, Y g:i A', (int) $creationDate) ?>
                                </p>
                            </div>
                        </div>

                        <div class="stat-block">
                            <div class="stat-icon bg-zinc-800 text-zinc-400">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 font-medium uppercase tracking-wider">Last Login</p>
                                <p class="text-sm font-medium text-gray-300 mt-1">
                                    <?= date('M j, Y g:i A', (int) $lastLogin) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- API Check Example -->
                <div class="glass-card">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <i class="bi bi-code-square text-purple-500"></i> Subscription Check Example
                    </h2>
                    <p class="text-sm text-gray-400 mb-4">
                        This demonstrates how to verify if the currently logged-in user holds a specific subscription
                        level on your application.
                    </p>

                    <div class="bg-zinc-900/50 rounded-lg p-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Checking for level:</p>
                            <code
                                class="text-sm text-purple-400 font-mono bg-purple-500/10 px-2 py-1 rounded">default</code>
                        </div>

                        <div>
                            <?php if (findSubscription("default", $subscriptions)): ?>
                                <span class="status-badge status-active"><i class="bi bi-check-circle mr-1"></i>
                                    Active</span>
                            <?php else: ?>
                                <span class="status-badge status-inactive"><i class="bi bi-x-circle mr-1"></i> Not
                                    Found</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Subscriptions List -->
            <div class="lg:col-span-1">
                <div class="glass-card h-full">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <i class="bi bi-box-seam text-emerald-500"></i> Active Subscriptions
                    </h2>

                    <?php if (empty($subscriptions)): ?>
                        <div class="flex flex-col items-center justify-center py-10 text-gray-500">
                            <i class="bi bi-inbox text-4xl mb-2 opacity-50"></i>
                            <p class="text-sm">No active subscriptions found.</p>
                        </div>
                    <?php else: ?>
                        <div class="flex flex-col gap-3">
                            <?php foreach ($subscriptions as $index => $sub): ?>
                                <div class="bg-zinc-900/50 rounded-lg p-4 hover:bg-zinc-800/50 transition-colors">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-xs font-semibold text-emerald-500 bg-emerald-500/10 px-2 py-0.5 rounded-md">#<?= $index + 1 ?></span>
                                            <span class="font-semibold text-white truncate max-w-[120px]"
                                                title="<?= htmlspecialchars($sub->subscription) ?>">
                                                <?= htmlspecialchars($sub->subscription) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-xs text-gray-400 flex items-center gap-1.5 mt-3">
                                        <i class="bi bi-hourglass-split"></i>
                                        <span>Expires:
                                            <script>document.write(convertTimestamp(<?= $sub->expiry; ?>))</script>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-white/5 py-6 text-center">
        <p class="text-xs text-gray-600 font-medium">&copy; <?= date('Y') ?> AuthVaultix API Example.</p>
    </footer>

</body>

</html>