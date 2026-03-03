<?php
include 'authvaultix.php';
include 'credentials.php';

if (isset($_SESSION['user_data'])) {
    header("Location: dashboard/");
    exit();
}

$AuthVaultixApp = new AuthVaultix\api($name, $ownerid, $secret, $version);

if (!isset($_SESSION['sessionid'])) {
    $AuthVaultixApp->init();
}

$actionError = "";

// Process form submissions before rendering HTML
if (isset($_POST['login'])) {
    $code = !empty($_POST['tfa']) ? $_POST['tfa'] : null;
    if ($AuthVaultixApp->login($_POST['username'], $_POST['password'], $code)) {
        header("Location: dashboard/");
        exit();
    } else {
        $actionError = $AuthVaultixApp->lastError;
    }
}

if (isset($_POST['register'])) {
    if ($AuthVaultixApp->register($_POST['username'], $_POST['password'], $_POST['key'])) {
        header("Location: dashboard/");
        exit();
    } else {
        $actionError = $AuthVaultixApp->lastError;
    }
}

if (isset($_POST['license'])) {
    $code = !empty($_POST['tfa']) ? $_POST['tfa'] : null;
    if ($AuthVaultixApp->license($_POST['key'], $code)) {
        header("Location: dashboard/");
        exit();
    } else {
        $actionError = $AuthVaultixApp->lastError;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AuthVaultix PHP Example</title>
    <link rel="shortcut icon" href="https://api.authvaultix.com/assets/img/logo.webp" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            /* overflow-y: auto; */
        }
    </style>
    <script>
        window.addEventListener('load', () => {
            document.body.style.visibility = 'visible';
        });
    </script>
</head>

<body class="bg-white min-h-screen" style="visibility: hidden;">

    <div class="flex min-h-screen w-full">

        <!-- LEFT PANEL (Dark / Branding) -->
        <div class="hidden lg:flex lg:w-1/2 bg-black text-white flex-col relative px-16 py-12 justify-between z-10">
            <!-- Logo area -->
            <div class="flex items-center gap-3">
                <div class="w-auto h-12 rounded-lg flex items-center justify-center text-black font-bold text-xl">
                    <img src="https://api.authvaultix.com/assets/img/logo.webp" alt="A"
                        class="w-full h-full object-contain p-1 invert brightness-0">
                </div>
                <span class="text-xl font-bold tracking-wide">AuthVaultix</span>
            </div>

            <!-- Hero Text -->
            <div class="max-w-lg mb-20">
                <h1 class="text-6xl font-bold tracking-tight mb-6 leading-[1.1]">
                    AuthVaultix PHP Example
                </h1>
                <p class="text-xl text-gray-400">
                    The best authentication platform for your software.
                </p>
            </div>

            <!-- Footer / Copyright -->
            <div class="text-gray-600 text-sm">
                &copy; <?php echo date("Y"); ?> AuthVaultix Inc.
            </div>

            <!-- Decorative Glow -->
            <div
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-indigo-900/20 blur-[100px] rounded-full pointer-events-none -z-10">
            </div>
        </div>

        <!-- RIGHT PANEL (Form) -->
        <div
            class="w-full lg:w-1/2 relative flex items-center justify-center px-4 sm:px-8 py-12 bg-white overflow-hidden min-h-screen">

            <!-- Deep & Vibrant Background -->
            <!-- Base Gradient -->
            <div class="absolute inset-0 bg-slate-50"></div>

            <!-- 1. Intense Deep Blue Orb (Top Left) -->
            <div
                class="absolute -top-[20%] -left-[10%] w-[80%] h-[80%] bg-[#4338ca] opacity-40 blur-[140px] rounded-full pointer-events-none mix-blend-multiply animate-pulse">
            </div>

            <!-- 2. Vibrant Violet/Pink Splash (Bottom Right) -->
            <div
                class="absolute -bottom-[20%] -right-[10%] w-[80%] h-[80%] bg-[#c026d3] opacity-30 blur-[140px] rounded-full pointer-events-none mix-blend-multiply">
            </div>

            <!-- 3. Bright Cyan Accent (Center Right) -->
            <div
                class="absolute top-[30%] -right-[20%] w-[60%] h-[60%] bg-[#06b6d4] opacity-30 blur-[120px] rounded-full pointer-events-none mix-blend-multiply">
            </div>

            <!-- 4. Deep Indigo Depth (Bottom Left) -->
            <div
                class="absolute -bottom-[10%] -left-[20%] w-[70%] h-[70%] bg-[#312e81] opacity-50 blur-[130px] rounded-full pointer-events-none">
            </div>

            <!-- Card -->
            <div
                class="w-full max-w-[420px] bg-white/90 backdrop-blur-xl rounded-2xl shadow-[0_8px_30px_rgb(0,0,0,0.06)] border border-white/50 p-8 sm:p-10 relative z-20">

                <h2 class="text-3xl font-bold text-gray-900 text-center mb-8">Access App</h2>

                <?php if (!empty($actionError)): ?>
                    <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-red-600 text-sm font-medium flex gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span><?= htmlspecialchars($actionError) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username" id="username" autocomplete="username"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-600 outline-none"
                            placeholder="Username" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="password" autocomplete="current-password"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-600 outline-none"
                            placeholder="••••••••" />
                    </div>

                    <div>
                        <label for="key" class="block text-sm font-medium text-gray-700 mb-1">License</label>
                        <input type="text" name="key" id="key"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-600 outline-none"
                            placeholder="License Key" />
                    </div>

                    <div class="pt-2">
                        <button type="submit" name="login"
                            class="w-full bg-blue-600 text-white font-bold py-3.5 rounded-lg hover:bg-blue-700 transition duration-200 shadow-sm hover:shadow-md mb-3">
                            Login
                        </button>

                        <div class="flex gap-3">
                            <button type="submit" name="register"
                                class="w-1/2 bg-gray-800 text-white font-bold py-3 rounded-lg hover:bg-gray-900 transition duration-200 shadow-sm hover:shadow-md text-sm">
                                Register
                            </button>

                            <button type="submit" name="license"
                                class="w-1/2 bg-indigo-600 text-white font-bold py-3 rounded-lg hover:bg-indigo-700 transition duration-200 shadow-sm hover:shadow-md text-sm">
                                License Login
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>

</html>