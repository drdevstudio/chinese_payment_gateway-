package com.ghorapay.smscapture;

import android.content.Context;
import android.provider.Settings;
import android.util.Log;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.util.UUID;

/**
 * DeviceUtils — helper class for device identification.
 *
 * Security: We never send raw ANDROID_ID to the server.
 * Instead, we SHA-256 hash it with a fixed salt, so the server gets a
 * stable unique identifier without being able to recover the real ID.
 */
public class DeviceUtils {

    private static final String TAG = "GhoraPayDevice";
    private static final String HASH_SALT = "ghorapay_device_2024";

    /** Returns a stable, hashed device identifier (16 hex chars) */
    public static String getDeviceId(Context context) {
        try {
            String androidId = Settings.Secure.getString(
                context.getContentResolver(), Settings.Secure.ANDROID_ID
            );
            if (androidId != null && !androidId.isEmpty() && !"9774d56d682e549c".equals(androidId)) {
                return sha256Short(HASH_SALT + androidId);
            }
        } catch (Exception e) {
            Log.w(TAG, "Could not get ANDROID_ID: " + e.getMessage());
        }
        // Fallback: use a UUID stored in SharedPreferences
        return getFallbackId(context);
    }

    /** Generates a random 16-character transaction ID */
    public static String randomTxnId() {
        String uuid = UUID.randomUUID().toString().replace("-", "").toUpperCase();
        return uuid.substring(0, 16);
    }

    private static String sha256Short(String input) {
        try {
            MessageDigest digest = MessageDigest.getInstance("SHA-256");
            byte[] hash = digest.digest(input.getBytes(StandardCharsets.UTF_8));
            StringBuilder hex = new StringBuilder();
            for (byte b : hash) hex.append(String.format("%02x", b));
            return hex.toString().substring(0, 32); // 32 hex chars
        } catch (Exception e) {
            return input.substring(0, Math.min(16, input.length()));
        }
    }

    private static String getFallbackId(Context ctx) {
        android.content.SharedPreferences prefs = ctx.getSharedPreferences("ghorapay", Context.MODE_PRIVATE);
        String id = prefs.getString("device_id", null);
        if (id == null) {
            id = UUID.randomUUID().toString().replace("-", "").substring(0, 32);
            prefs.edit().putString("device_id", id).apply();
        }
        return id;
    }
}
