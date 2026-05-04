<div align="center">

<img src="https://api.authvaultix.com/assets/img/logo.webp" alt="AuthVaultix Logo" width="80" height="80" />

# AuthVaultix PHP Example

**A complete, ready-to-use PHP integration example for the [AuthVaultix](https://authvaultix.com) authentication platform.**

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![AuthVaultix](https://img.shields.io/badge/AuthVaultix-API%201.0-6366F1?style=for-the-badge)](https://authvaultix.com)
[![License](https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge)](LICENSE)
[![Discord](https://img.shields.io/badge/Discord-Join%20Us-5865F2?style=for-the-badge&logo=discord&logoColor=white)](https://discord.gg/muHy3qxcub)

</div>

---

## 📖 Overview

This repository provides a **plug-and-play PHP example** demonstrating how to integrate the [AuthVaultix](https://authvaultix.com) authentication API into your PHP web application. It includes:

- 🔐 **Login** — Authenticate users with username & password (+ optional 2FA)
- 📝 **Register** — Create new accounts with a license key
- 🔑 **License Login** — Access the app using only a license key
- 📊 **Dashboard** — A protected page that displays user session info, subscriptions & expiry dates
- 🚪 **Logout** — Securely terminate sessions via the API

> Built with a modern, responsive UI using **Tailwind CSS** and **Inter** font — looks great on all screen sizes.

---

## 🗂️ Project Structure

```
authvaultix-php-example/
├── index.php           # Login / Register / License page (public)
├── authvaultix.php     # AuthVaultix PHP API wrapper (core library)
├── credentials.php     # Your app credentials (name, ownerid, secret, version)
└── dashboard/
    └── index.php       # Protected dashboard (requires authentication)
```

---

## ⚡ Quick Start

### 1. Prerequisites

- PHP **7.4 or higher**
- `curl` PHP extension enabled
- A web server (Apache / Nginx) or PHP's built-in server
- An **AuthVaultix** account → [Register here](https://authvaultix.com)

### 2. Clone the Repository

```bash
git clone https://github.com/AuthVaultix/AuthVaultix-PHP-Example.git
cd AuthVaultix-PHP-Example
```

### 3. Configure Your Credentials

Open `credentials.php` and fill in your application details from the [AuthVaultix Dashboard](https://authvaultix.com):

```php
<?php
$name    = "YourAppName";      // Application name
$ownerid = "your_owner_id";   // Your Owner ID
$secret  = "your_secret";     // Application Secret
$version = "1.0";             // Application version
```

> ⚠️ **Never commit real credentials to a public repository.**  
> Add `credentials.php` to your `.gitignore` file.

### 4. Run Locally

```bash
php -S localhost:8080
```

Then open [http://localhost:8080](http://localhost:8080) in your browser. & you can use XAMP server too. XAMP server location is `C:/xampp/htdocs`. Copy the project folder to the htdocs folder & run `http://localhost/authvaultix-php-example/` in your browser.

---

## 🧩 API Wrapper Usage

The `authvaultix.php` file contains the `AuthVaultix\api` class. Here's how to use it in your own project:

### Initialize

```php
<?php
include 'authvaultix.php';
include 'credentials.php';

$app = new AuthVaultix\api($name, $ownerid, $secret, $version);
$app->init(); // Must be called before any other method
```

### Login

```php
$success = $app->login($username, $password);

// With 2FA code:
$success = $app->login($username, $password, $twoFactorCode);

if ($success) {
    // $_SESSION['user_data'] is now populated
    header("Location: dashboard/");
}
```

### Register

```php
$success = $app->register($username, $password, $licenseKey);

if (!$success) {
    echo $app->lastError; // Display the error message
}
```

### License Login

```php
$success = $app->license($licenseKey);

// With 2FA:
$success = $app->license($licenseKey, $twoFactorCode);
```

### Logout

```php
$app->logout();
session_destroy();
header("Location: ../");
```

### Check Subscription

```php
function findSubscription($name, $subscriptions) {
    foreach ($subscriptions as $sub) {
        if ($sub->subscription === $name) return true;
    }
    return false;
}

$subs = $_SESSION['user_data']['subscriptions'];

if (findSubscription("premium", $subs)) {
    echo "User has Premium access!";
}
```

---

## 🖥️ Screenshots

### Login Page

> Split-layout design with a dark branding panel and a glassmorphism form card with animated gradient orbs.

### Dashboard

> Dark-themed protected dashboard showing username, IP address (blur-to-reveal), account creation date, last login time, and active subscriptions with expiry dates.

---

## 🔒 Security Notes

| Concern           | Recommendation                                                          |
| ----------------- | ----------------------------------------------------------------------- |
| `credentials.php` | Add to `.gitignore` — never expose secrets                              |
| SSL Verification  | Enable `CURLOPT_SSL_VERIFYPEER` in production                           |
| Session Handling  | Use HTTPS in production to protect session cookies                      |
| Error Display     | Disable `display_errors` in production (`ini_set('display_errors', 0)`) |

---

## 🛠️ Customization

- **Branding**: Replace the logo URL in `index.php` and `dashboard/index.php` with your own asset
- **Styling**: The UI uses Tailwind CSS via CDN — swap for a local build for production
- **Subscription Tiers**: Edit the `findSubscription()` call in `dashboard/index.php` to check for your specific subscription level names

---

## 📦 Dependencies

| Library                                               | Version      | Purpose        |
| ----------------------------------------------------- | ------------ | -------------- |
| [Tailwind CSS](https://tailwindcss.com/)              | CDN          | UI styling     |
| [Inter Font](https://fonts.google.com/specimen/Inter) | Google Fonts | Typography     |
| [Bootstrap Icons](https://icons.getbootstrap.com/)    | 1.11.1       | Icons          |
| [AuthVaultix API](https://authvaultix.com)            | 1.0          | Authentication |

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

1. Fork the repository
2. Create a new branch: `git checkout -b feature/my-feature`
3. Commit your changes: `git commit -m 'Add my feature'`
4. Push to the branch: `git push origin feature/my-feature`
5. Open a Pull Request

---

## 💬 Support

- 📖 [AuthVaultix Documentation](https://authvaultix.com)
- 💬 [Discord Community](https://discord.gg/muHy3qxcub)
- 🐛 [Open an Issue](https://github.com/YOUR_USERNAME/authvaultix-php-example/issues)

---

## 📄 License

This project is licensed under the **MIT License** — feel free to use, modify, and distribute it.

---

<div align="center">

Made with ❤️ using [AuthVaultix](https://authvaultix.com)

</div>
