package com.ghorapay.smscapture;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.util.Log;

/**
 * BootReceiver — restarts the foreground service after device reboot.
 * Declared with BOOT_COMPLETED and QUICKBOOT_POWERON for MIUI/Xiaomi compatibility.
 */
public class BootReceiver extends BroadcastReceiver {

    private static final String TAG = "GhoraPayBoot";

    @Override
    public void onReceive(Context context, Intent intent) {
        String action = intent.getAction();
        if (action == null) return;

        if (Intent.ACTION_BOOT_COMPLETED.equals(action)
            || "android.intent.action.QUICKBOOT_POWERON".equals(action)
            || "com.htc.intent.action.QUICKBOOT_POWERON".equals(action)) {

            Log.i(TAG, "Boot completed — starting Ghora Pay service");

            // Start the main activity so the user can see it started,
            // then the service will be started from there as well.
            Intent serviceIntent = new Intent(context, SmsUploadService.class);
            context.startForegroundService(serviceIntent);
        }
    }
}
