# Ghora Pay — Firebase Realtime Database Setup

## Step 1 — Create Firebase Project

1. Go to https://console.firebase.google.com
2. Click **Add project** → name it (e.g. `ghorapay`)
3. Disable Google Analytics (not needed) → **Create project**

## Step 2 — Create Realtime Database

1. Left sidebar → **Build** → **Realtime Database**
2. Click **Create Database**
3. Choose your region (e.g. `us-central1`)
4. Start in **test mode** (we'll apply rules next)
5. Copy your Database URL — looks like:
   `https://ghorapay-default-rtdb.firebaseio.com`

## Step 3 — Set Database Rules

1. In Realtime Database → **Rules** tab
2. Paste the contents of `db/firebase_rules.json`:
```json
{
  "rules": {
    ".read": true,
    ".write": true
  }
}
```
3. Click **Publish**

## Step 4 — Import Seed Data

1. Generate admin password hash:
   ```bash
   php db/generate_admin_hash.php
   ```
2. Edit `db/firebase_seed.json` — replace `$2y$12$REPLACE_WITH_YOUR_BCRYPT_HASH` with your hash
3. In Firebase Console → Realtime Database → (three-dot menu) → **Import JSON**
4. Select the edited `firebase_seed.json`

## Step 5 — Configure `config.php`

Edit `config.php` and set:
```php
define('FIREBASE_URL', 'https://YOUR-PROJECT-default-rtdb.firebaseio.com');
define('SITE_URL',    'https://yourdomain.com');
define('API_KEY', 'your_32_byte_hex_secret'); // openssl rand -hex 32
```

## Step 6 — Configure Android App

In `android/SmsUploadService.java` set:
```java
private static final String API_URL = "https://yourdomain.com/api/receive_sms.php";
private static final String API_KEY  = "same_key_as_in_config.php";
```

## Step 7 — Deploy to Server

Upload all PHP files to your web server.  
Ensure **PHP cURL extension** is enabled (for Firebase REST calls).

## Database Structure

```
/settings            → min/max payment amounts
/commissions         → pay_in %, pay_out %
/admins/{username}   → admin accounts
/merchants/{id}      → merchant records
/transactions/{id}   → all payment transactions
/withdrawals/{key}   → withdrawal requests
/upi_ids/{device_id} → UPI accounts from Android devices
/sms_logs/{utr}      → raw SMS records (UTR as key, prevents duplicates)
```

## No API Key / Service Account Needed

This project uses **Firebase REST API with open rules** (`read: true, write: true`).  
No `service.json`, no Firebase Admin SDK, no API key in code.  
All database access goes through your `FIREBASE_URL` with standard cURL.

## Security Note

Open rules are fine for a closed, server-side system where only your PHP server  
talks to Firebase. If you want extra security later, add Firebase Auth or  
restrict by IP in your server's firewall — not in Firebase rules.

## Indexes (Important for Query Performance)

The `firebase_rules.json` already includes `.indexOn` rules for all queried fields.
These are required for Firebase `orderBy` queries to work without warnings.
Paste the full contents of `db/firebase_rules.json` (not just the basic read/write rules).

The rules in the file handle both:
1. Open access (`".read": true, ".write": true`)
2. Query indexes for `merchant_id`, `device_id`, `status`, `created_at` on all collections
