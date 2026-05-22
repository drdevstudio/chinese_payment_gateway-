package com.ghorapay.smscapture;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.net.Uri;
import android.os.Build;
import android.os.Bundle;
import android.os.PowerManager;
import android.provider.Settings;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

import java.util.ArrayList;
import java.util.List;

/**
 * MainActivity — permission setup screen.
 *
 * UX flow:
 *  1. App opens → checks required permissions.
 *  2. Shows status for each permission (green tick / red X).
 *  3. "Grant Permissions" button requests missing ones.
 *  4. Once all granted, shows "Gateway Active" + starts SmsUploadService.
 *  5. App can be closed — service stays alive (foreground).
 */
public class MainActivity extends AppCompatActivity {

    private static final int PERM_REQUEST_CODE = 100;

    private static final String[] REQUIRED_PERMISSIONS = {
        Manifest.permission.RECEIVE_SMS,
        Manifest.permission.READ_SMS,
        Manifest.permission.INTERNET,
    };

    private TextView tvSmsStatus;
    private TextView tvServiceStatus;
    private Button btnGrant;
    private Button btnIgnoreBattery;
    private View statusSms;
    private View statusService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        tvSmsStatus      = findViewById(R.id.tvSmsStatus);
        tvServiceStatus  = findViewById(R.id.tvServiceStatus);
        btnGrant         = findViewById(R.id.btnGrant);
        btnIgnoreBattery = findViewById(R.id.btnIgnoreBattery);
        statusSms        = findViewById(R.id.statusSms);
        statusService    = findViewById(R.id.statusService);

        btnGrant.setOnClickListener(v -> requestMissingPermissions());
        btnIgnoreBattery.setOnClickListener(v -> requestBatteryOptimization());

        updateUI();
    }

    @Override
    protected void onResume() {
        super.onResume();
        updateUI();
    }

    private void updateUI() {
        boolean allGranted = allPermissionsGranted();

        if (allGranted) {
            tvSmsStatus.setText("✅ SMS permission active");
            tvSmsStatus.setTextColor(getColor(android.R.color.holo_green_dark));
            statusSms.setBackgroundColor(getColor(android.R.color.holo_green_light));
            btnGrant.setVisibility(View.GONE);

            // Start foreground service
            startForegroundService(new Intent(this, SmsUploadService.class));

            tvServiceStatus.setText("🟢 Ghora Pay Gateway is ACTIVE");
            tvServiceStatus.setTextColor(getColor(android.R.color.holo_green_dark));
            statusService.setBackgroundColor(getColor(android.R.color.holo_green_light));
        } else {
            tvSmsStatus.setText("❌ SMS permission required");
            tvSmsStatus.setTextColor(getColor(android.R.color.holo_red_dark));
            statusSms.setBackgroundColor(getColor(android.R.color.holo_red_light));
            btnGrant.setVisibility(View.VISIBLE);

            tvServiceStatus.setText("⛔ Gateway not running — grant permissions");
            tvServiceStatus.setTextColor(getColor(android.R.color.holo_red_dark));
            statusService.setBackgroundColor(getColor(android.R.color.holo_red_light));
        }

        // Battery optimization
        if (isIgnoringBatteryOptimizations()) {
            btnIgnoreBattery.setText("✅ Battery optimization disabled");
            btnIgnoreBattery.setEnabled(false);
        } else {
            btnIgnoreBattery.setText("⚡ Disable Battery Optimization (Recommended)");
            btnIgnoreBattery.setEnabled(true);
        }
    }

    private boolean allPermissionsGranted() {
        for (String perm : REQUIRED_PERMISSIONS) {
            if (ContextCompat.checkSelfPermission(this, perm) != PackageManager.PERMISSION_GRANTED) {
                return false;
            }
        }
        // Android 13+ notification permission
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS)
                    != PackageManager.PERMISSION_GRANTED) {
                return false;
            }
        }
        return true;
    }

    private void requestMissingPermissions() {
        List<String> missing = new ArrayList<>();
        for (String perm : REQUIRED_PERMISSIONS) {
            if (ContextCompat.checkSelfPermission(this, perm) != PackageManager.PERMISSION_GRANTED) {
                missing.add(perm);
            }
        }
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            if (ContextCompat.checkSelfPermission(this, Manifest.permission.POST_NOTIFICATIONS)
                    != PackageManager.PERMISSION_GRANTED) {
                missing.add(Manifest.permission.POST_NOTIFICATIONS);
            }
        }
        if (!missing.isEmpty()) {
            ActivityCompat.requestPermissions(this, missing.toArray(new String[0]), PERM_REQUEST_CODE);
        }
    }

    private void requestBatteryOptimization() {
        new AlertDialog.Builder(this)
            .setTitle("Disable Battery Optimization")
            .setMessage("To ensure SMS payments are captured even when the app is in the background, " +
                "please disable battery optimization for Ghora Pay.")
            .setPositiveButton("Open Settings", (d, w) -> {
                Intent intent = new Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS);
                intent.setData(Uri.parse("package:" + getPackageName()));
                startActivity(intent);
            })
            .setNegativeButton("Later", null)
            .show();
    }

    private boolean isIgnoringBatteryOptimizations() {
        PowerManager pm = (PowerManager) getSystemService(POWER_SERVICE);
        return pm != null && pm.isIgnoringBatteryOptimizations(getPackageName());
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions,
                                           @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == PERM_REQUEST_CODE) {
            updateUI();
        }
    }
}
