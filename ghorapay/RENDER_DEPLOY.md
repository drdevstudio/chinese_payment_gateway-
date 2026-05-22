# 🚀 Ghora Pay — Render.com Deployment Guide

## Overview

Render Free Tier gives you:
- ✅ Docker-based deployment (PHP 8.2 + Apache)
- ✅ Auto-deploy on every `git push`
- ✅ Free TLS/HTTPS certificate
- ✅ Custom domain support
- ⚠️  **Spins down after 15 min of inactivity** (free plan) — first request after sleep takes ~30s

---

## Step 1 — Push to GitHub

```bash
cd ghorapay/
git init
git add .
git commit -m "Initial commit — Ghora Pay Firebase edition"
git remote add origin https://github.com/YOURNAME/ghorapay.git
git push -u origin main
```

---

## Step 2 — Create Render Web Service

1. Go to **https://render.com** → Sign up / Log in
2. Click **New +** → **Web Service**
3. Connect your **GitHub** account → Select your `ghorapay` repo
4. Render detects the `Dockerfile` automatically

**Settings to fill in:**

| Field | Value |
|---|---|
| Name | `ghorapay` (becomes `ghorapay.onrender.com`) |
| Region | Singapore (closest to India) |
| Branch | `main` |
| Runtime | **Docker** (auto-detected) |
| Plan | **Free** |

---

## Step 3 — Set Environment Variables

In Render → Your Service → **Environment** tab, add:

| Variable | Value | Notes |
|---|---|---|
| `FIREBASE_URL` | `https://YOUR-PROJECT-default-rtdb.firebaseio.com` | From Firebase Console |
| `SITE_URL` | `https://ghorapay.onrender.com` | Your Render URL (or custom domain) |
| `API_KEY` | *(click Generate)* | Copy this to Android app |
| `CRON_SECRET` | *(click Generate)* | Copy this to cron-job.org |

> **Tip:** Click **Generate** next to API_KEY and CRON_SECRET — Render creates a secure random value for you.

---

## Step 4 — Deploy

Click **Create Web Service** — Render builds your Docker image and deploys.

Build takes ~2–3 minutes. Watch the logs tab.

Once deployed, visit:
```
https://ghorapay.onrender.com
```

---

## Step 5 — Set Up Cron Job (cron-job.org)

### What the cron does:
Every 10 minutes, it calls `/cronjob.php` which finds pending transactions
older than **15 minutes** and marks them as **expired**.

### Setup on cron-job.org:

1. Go to **https://cron-job.org** → Sign up free → **Create Cronjob**

2. Fill in:

   | Field | Value |
   |---|---|
   | Title | Ghora Pay — Expire Payments |
   | URL | `https://ghorapay.onrender.com/cronjob.php` |
   | Schedule | Every **10 minutes** |
   | Request method | GET |

3. Under **Advanced** → **Headers**, add:

   | Header Name | Header Value |
   |---|---|
   | `X-Cron-Secret` | `<your CRON_SECRET from Render>` |

4. Click **Create** → the cron runs every 10 minutes automatically.

### Test it manually:
```bash
curl -H "X-Cron-Secret: YOUR_CRON_SECRET" \
  https://ghorapay.onrender.com/cronjob.php
```

**Expected response:**
```json
{
  "success": true,
  "ran_at": "2025-01-15 10:30:00",
  "cutoff": "2025-01-15 10:15:00",
  "expire_after": "15 minutes",
  "total_scanned": 12,
  "expired": 3,
  "skipped": 9,
  "duration": "0.412s",
  "expired_ids": ["TXN001", "TXN002", "TXN003"]
}
```

---

## Step 6 — Update Firebase Rules

The full `db/firebase_rules.json` (with indexes) must be in Firebase Console → Rules:

```json
{
  "rules": {
    ".read": true,
    ".write": true,
    "transactions": {
      ".indexOn": ["merchant_id", "device_id", "status", "created_at"]
    },
    "withdrawals": {
      ".indexOn": ["merchant_id", "status", "created_at"]
    },
    "upi_ids": {
      ".indexOn": ["status", "upi_address"]
    },
    "sms_logs": {
      ".indexOn": ["device_id", "txn_id"]
    }
  }
}
```

---

## Step 7 — Custom Domain (Optional)

1. Render → Your Service → **Settings** → **Custom Domain** → Add domain
2. Add a CNAME record at your DNS provider:
   ```
   CNAME  @  ghorapay.onrender.com
   ```
3. Update `SITE_URL` env var to your custom domain
4. Render auto-provisions TLS certificate

---

## Troubleshooting

### "Application error" on first load
- Check **Logs** tab in Render dashboard
- Ensure all environment variables are set
- Verify `FIREBASE_URL` is correct (no trailing slash)

### Payments not being found by cron
- Make sure `transactions` index is set in Firebase rules
- Test cron manually with `curl` first
- Check cron-job.org execution history for HTTP response codes

### "Unauthorized" from cronjob.php
- Confirm the `X-Cron-Secret` header value matches `CRON_SECRET` env var in Render exactly
- No extra spaces or newlines in the header value

### Slow first request (cold start)
- Free Render services spin down after 15 min inactivity
- First request after sleep takes 30–60 seconds
- Consider using cron-job.org to also ping `https://your-app.onrender.com/` every 14 minutes as a keep-alive (run a second cron)

### Keep-Alive Cron (prevents sleep):
Add a second cron on cron-job.org:
- URL: `https://ghorapay.onrender.com/api/get_settings.php`
- Schedule: every **14 minutes**
- No auth header needed (it's a public endpoint)

---

## Android App Config

After Render deploy, update `android/SmsUploadService.java`:
```java
private static final String API_URL = "https://ghorapay.onrender.com/api/receive_sms.php";
private static final String API_KEY  = "<API_KEY value from Render environment>";
```

---

## File Layout on Server

```
/var/www/html/          ← web root
├── config.php          ← reads from env vars
├── pay.php
├── login.php
├── index.php
├── cronjob.php         ← called by cron-job.org every 10 min
├── api/
├── merchant/
├── admin/
└── includes/
    └── firebase.php    ← all DB calls via Firebase REST
```

> `android/`, `docker/`, `db/generate_admin_hash.php` are removed
> from the web root by the Dockerfile for security.
