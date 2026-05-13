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
├── credentials.php     # Parses .env and sets up credentials
├── .env                # Your app credentials (name, ownerid, secret, version)
├── logo.webp           # Local app logo to bypass hotlink protection
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

Create a `.env` file in the root directory and fill in your application details from the [AuthVaultix Dashboard](https://authvaultix.com):

```env
APP_NAME=YourAppName
OWNER_ID=your_owner_id
SECRET=your_secret
VERSION=your_version
```

> ⚠️ **Never commit real credentials to a public repository.**  
> Add `.env` to your `.gitignore` file.

### 4. Run Locally

```bash
php -S localhost/[Project-name]
```

Then open [http://localhost/[Project-name]](http://localhost/[Project-name]) in your browser. & you can use XAMP server too. XAMP server location is `C:/xampp/htdocs`. Copy the project folder to the htdocs folder & run `http://localhost/[Project-name]/` in your browser.

---

## 🧩 API Wrapper Usage

The `authvaultix.php` file contains the `AuthVaultix\api` class. Here's how to use it in your own project:

### Initialize

```php
<?php
include 'authvaultix.php';
include 'credentials.php';

$app = new AuthVaultix\AuthVaultixClient($name, $ownerid, $secret, $version);
$app->init(); // Must be called before any other method
```

### Login

```php
$success = $app->login($username, $password);


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

## 📖 API Reference

### Authentication & Session
| Method | Description |
| :--- | :--- |
| `init()` | Initializes the session with the API. |
| `login($user, $pass, $code?)` | Authenticates a user. `$code` is for optional 2FA. |
| `register($user, $pass, $key, $email?)` | Registers a new user. |
| `license($key, $code?)` | Authenticates directly via license key. |
| `check()` | Validates the current session. |
| `logout()` | Terminates session and destroys user data. |

### Account Management
| Method | Description |
| :--- | :--- |
| `upgrade($user, $license)` | Upgrades user's subscription. |
| `forgot_password($user, $email)` | Triggers a password reset. |
| `change_username($new_username)` | Changes the current user's username. |

### Security & Blacklist
| Method | Description |
| :--- | :--- |
| `ban($reason)` | Bans the currently authenticated user. |
| `check_blacklist()` | Checks if the current machine is blacklisted. |
| `log($message)` | Sends a log message to the dashboard. |

### Variables & Data
| Method | Description |
| :--- | :--- |
| `get_global_var($var_key)` | Fetches a global server variable. |
| `get_var($var_name)` | Fetches a user-specific variable. |
| `set_var($var_name, $value)` | Sets a user-specific variable. |
| `download($fileid)` | Securely downloads a file (returns base64 decoded). |

### Communication
| Method | Description |
| :--- | :--- |
| `fetch_online()` | Retrieves a list of online clients. |
| `chat_send($msg, $channel)` | Sends a chat message. |
| `chat_fetch($channel)` | Fetches chat history for a channel. |

> **Note:** For any method that fails, check the `$app->lastError` property for the error message.

---

## 🖥️ Screenshots

### Login Page

> Split-layout design with a dark branding panel and a glassmorphism form card with animated gradient orbs.

### Dashboard

> Dark-themed protected dashboard showing username, IP address (blur-to-reveal), account creation date, last login time, and active subscriptions with expiry dates.

---

## 🔒 Security Notes

| Concern          | Recommendation                                                          |
| ---------------- | ----------------------------------------------------------------------- |
| `.env` file      | Add to `.gitignore` — never expose `.env` secrets                       |
| SSL Verification | Enable `CURLOPT_SSL_VERIFYPEER` in production                           |
| Session Handling | Use HTTPS in production to protect session cookies                      |
| Error Display    | Disable `display_errors` in production (`ini_set('display_errors', 0)`) |

---

## 🛠️ Customization

- **Branding**: Replace the `logo.webp` file with your own logo asset
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
