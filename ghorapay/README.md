# 🐴 Ghora Pay — UPI Payment Gateway (Firebase Edition)

A complete self-hosted UPI payment gateway backed by **Firebase Realtime Database**.  
No MySQL. No service accounts. No API keys in config — just your Firebase project URL.

## Features
- **Android SMS Capture App** — auto-captures bank payment confirmations
- **Merchant Panel** — login, 2FA (TOTP), dashboard, withdrawals, API credentials
- **Admin Panel** — manage merchants, UPI IDs, commissions, withdrawals
- **Payment Checkout** — QR code, all UPI deep links (PhonePe, GPay, Paytm), UTR verify
- **Firebase REST** — all DB calls via cURL, open read/write rules, zero config overhead

---

## 🚀 Quick Setup

### 1. Firebase Project
See **`FIREBASE_SETUP.md`** for the full step-by-step guide.

**TL;DR:**
1. Create Firebase project → Enable Realtime Database
2. Set rules: `{ "rules": { ".read": true, ".write": true } }`
3. Run `php db/generate_admin_hash.php` → edit `db/firebase_seed.json` → Import JSON
4. Copy your database URL: `https://YOUR-PROJECT-default-rtdb.firebaseio.com`

### 2. Configure `config.php`
```php
define('FIREBASE_URL', 'https://YOUR-PROJECT-default-rtdb.firebaseio.com');
define('SITE_URL',     'https://yourdomain.com');
define('API_KEY',      'your_32_byte_hex');  // openssl rand -hex 32
```

### 3. Web Server (Nginx example)
```nginx
server {
    listen 443 ssl;
    server_name yourdomain.com;
    root /var/www/ghorapay;
    index index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\. { deny all; }
}
```

### 4. PHP Requirements
- PHP 8.1+
- `php-curl` extension (for Firebase REST calls)
- No PDO, no MySQL driver needed

### 5. Android App
Edit `android/SmsUploadService.java`:
```java
private static final String API_URL = "https://yourdomain.com/api/receive_sms.php";
private static final String API_KEY  = "same_key_as_in_config.php";
```

---

## 📁 Project Structure

```
ghorapay/
├── config.php                    ← Set FIREBASE_URL here
├── pay.php                       ← Customer payment page
├── login.php                     ← Merchant login
├── index.php                     ← Landing page
├── includes/
│   ├── firebase.php              ← Firebase REST helper (fbGet, fbPut, etc.)
│   ├── auth.php                  ← Session auth guards
│   ├── totp.php                  ← 2FA (Google Authenticator)
│   ├── admin_layout.php          ← Admin sidebar/topbar layout
│   └── merchant_layout.php       ← Merchant sidebar/topbar layout
├── api/
│   ├── create_payment.php        ← POST: create transaction (merchant → your server)
│   ├── receive_sms.php           ← POST: Android app posts captured SMS
│   ├── check_payment.php         ← GET: poll payment status
│   ├── verify_utr.php            ← POST: customer manually enters UTR
│   ├── get_settings.php          ← GET: min/max amounts
│   ├── merchant/auth.php         ← Merchant login, 2FA, withdraw, key regen
│   └── admin/
│       ├── merchants.php         ← Merchant CRUD
│       ├── upi.php               ← UPI ID management
│       ├── withdrawals.php       ← Approve/reject withdrawals
│       └── commissions.php       ← Pay-in/out %, amount limits
├── merchant/
│   ├── dashboard.php
│   ├── transactions.php
│   ├── withdraw.php
│   ├── create-link.php
│   └── profile.php
├── admin/
│   ├── login.php
│   ├── dashboard.php
│   ├── merchants.php
│   ├── upi.php
│   ├── withdrawals.php
│   └── commissions.php
├── android/
│   ├── SmsReceiver.java          ← BroadcastReceiver: captures SMS
│   ├── SmsUploadService.java     ← Foreground service: posts to API
│   ├── MainActivity.java
│   └── DeviceUtils.java
└── db/
    ├── firebase_rules.json       ← Paste into Firebase Console > Rules
    ├── firebase_seed.json        ← Import into Firebase Console
    └── generate_admin_hash.php   ← Run once to create admin password hash
```

---

## 🔑 API Reference

### Create Payment
```
POST /api/create_payment.php
Content-Type: application/x-www-form-urlencoded

merchant_id       = M00000001
amount            = 500.00
signature         = md5(merchant_id + amount + merchant_order_no + api_key + redirect_url)
merchant_order_no = ORDER_001  (optional)
redirect_url      = https://yoursite.com/callback  (optional)
```

**Response:**
```json
{
  "success": true,
  "txn_id": "A1B2C3D4E5F6G7H8",
  "pay_url": "https://yourdomain.com/pay.php?txn=A1B2...",
  "upi": "merchant@upi",
  "amount": 500.00
}
```

### Check Payment Status
```
GET /api/check_payment.php?txn_id=A1B2C3D4E5F6G7H8
```

### Android SMS Upload
```
POST /api/receive_sms.php
X-API-Key: your_api_key
Content-Type: application/x-www-form-urlencoded

device_id = sha256_of_android_id
utr       = 123456789012
amount    = 500.00
sender_id = SBIINB
message   = raw SMS text
```

---

## 🔐 Security Features

| Feature | Implementation |
|---|---|
| 2FA | TOTP (RFC 6238) — Google Authenticator compatible |
| Withdrawal | Requires withdrawal password + TOTP every time |
| CSRF | Token in every form, verified server-side |
| Rate Limiting | Session-based, per-action limits |
| Signature | MD5 of key fields prevents unauthorized payment creation |
| Domain Whitelist | Origin checked against merchant's registered domain |
| Session | Regenerated on login, HttpOnly, SameSite=Strict |
| API Key | Sent as HTTP header (not logged in URL access logs) |

---

## 🗄️ Firebase Data Structure

```
/settings            → { min_amount, max_amount }
/commissions         → { pay_in, pay_out }
/admins/{username}   → { password_hash, created_at }
/merchants/{id}      → { name, password, api_key, balance, status, totp_*, ... }
/transactions/{id}   → { txn_id, merchant_id, device_id, amount, utr, status, ... }
/withdrawals/{key}   → { merchant_id, amount, upi_address, status, note, ... }
/upi_ids/{device_id} → { upi_address, holder_name, daily_limit, today_received, ... }
/sms_logs/{utr}      → { utr, device_id, amount, sender_id, txn_id, raw_message }
```

> `sms_logs` uses UTR as the Firebase key — guarantees each UTR is only processed once,
> even if the Android app sends it multiple times (idempotent).
