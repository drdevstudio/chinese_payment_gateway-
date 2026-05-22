package com.ghorapay.smscapture;

import android.content.BroadcastReceiver;
import android.content.Context;
import android.content.Intent;
import android.os.Bundle;
import android.telephony.SmsMessage;
import android.util.Log;

import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * SmsReceiver — listens for all incoming SMS, filters for payment confirmations.
 *
 * Security notes:
 *  - Only parses; never stores raw messages to disk.
 *  - UTR and amount are validated with strict regex before dispatch.
 *  - All network I/O is in SmsUploadService (background thread).
 */
public class SmsReceiver extends BroadcastReceiver {

    private static final String TAG = "GhoraPaySMS";

    // Matches 12-digit UTR / Reference numbers used by Indian banks
    // Common prefixes: UPI Ref No, UTR, Ref No, Reference Number
    private static final Pattern UTR_PATTERN = Pattern.compile(
        "(?:UPI\\s*Ref\\.?\\s*No\\.?|UTR|Ref\\.?\\s*No\\.?|Reference\\s*(?:No\\.?|Number))[:\\s]*([0-9]{12})",
        Pattern.CASE_INSENSITIVE
    );

    // Matches payment amounts like Rs.500, INR 1,500.00, Rs 500.00
    private static final Pattern AMOUNT_PATTERN = Pattern.compile(
        "(?:Rs\\.?|INR|₹)\\s*([0-9,]+(?:\\.[0-9]{1,2})?)",
        Pattern.CASE_INSENSITIVE
    );

    // Banks and payment services that send UPI credit SMS
    private static final String[] TRUSTED_SENDERS = {
        "SBIUPI", "HDFCBK", "ICICIB", "AXISBK", "KOTAKB", "PNBSMS",
        "BOIIND", "CANBNK", "UNIONB", "CENTBK", "IOBSMS", "SYNDBK",
        "PAYTMB", "YESBNK", "IDFCBK", "RBLBNK", "FEDERL", "INDUSB",
        "SCBNKI", "CITIBK", "DENABNK", "VIJBNK", "CORPBK", "ORINBK",
        "GPAY", "PHONEPE", "PAYTM", "BHIM", "MIUPI"
    };

    @Override
    public void onReceive(Context context, Intent intent) {
        if (intent == null || !"android.provider.Telephony.SMS_RECEIVED".equals(intent.getAction())) {
            return;
        }

        Bundle bundle = intent.getExtras();
        if (bundle == null) return;

        Object[] pdus = (Object[]) bundle.get("pdus");
        String format = bundle.getString("format");
        if (pdus == null || pdus.length == 0) return;

        StringBuilder fullMessage = new StringBuilder();
        String sender = null;
        long timestamp = System.currentTimeMillis();

        for (Object pdu : pdus) {
            SmsMessage msg;
            if (format != null) {
                msg = SmsMessage.createFromPdu((byte[]) pdu, format);
            } else {
                msg = SmsMessage.createFromPdu((byte[]) pdu);
            }
            if (msg != null) {
                if (sender == null) sender = msg.getOriginatingAddress();
                fullMessage.append(msg.getMessageBody());
                if (msg.getTimestampMillis() > 0) timestamp = msg.getTimestampMillis();
            }
        }

        String body = fullMessage.toString();
        if (sender == null || body.isEmpty()) return;

        Log.d(TAG, "SMS from: " + sender);

        // Only process from trusted bank/payment senders
        if (!isTrustedSender(sender)) {
            Log.d(TAG, "Ignoring untrusted sender: " + sender);
            return;
        }

        // Must contain credit keywords
        if (!isPaymentCredit(body)) {
            Log.d(TAG, "Not a payment credit SMS");
            return;
        }

        String utr = extractUtr(body);
        String amount = extractAmount(body);

        if (utr == null || amount == null) {
            Log.w(TAG, "Could not extract UTR or amount from: " + body.substring(0, Math.min(80, body.length())));
            return;
        }

        Log.i(TAG, "Payment detected — UTR: " + utr + " | Amount: " + amount + " | Sender: " + sender);

        // Dispatch to background upload service
        Intent uploadIntent = new Intent(context, SmsUploadService.class);
        uploadIntent.putExtra("utr", utr);
        uploadIntent.putExtra("amount", amount);
        uploadIntent.putExtra("sender_id", sender);
        uploadIntent.putExtra("raw_message", body);
        uploadIntent.putExtra("timestamp", timestamp);
        context.startForegroundService(uploadIntent);
    }

    private boolean isTrustedSender(String sender) {
        if (sender == null) return false;
        String upper = sender.toUpperCase();
        for (String s : TRUSTED_SENDERS) {
            if (upper.contains(s)) return true;
        }
        // Also accept numeric short codes (e.g. bank SMS from 6-digit numbers)
        return sender.matches("^[0-9]{5,6}$");
    }

    private boolean isPaymentCredit(String body) {
        String lower = body.toLowerCase();
        return (lower.contains("credited") || lower.contains("received") || lower.contains("credit"))
            && (lower.contains("upi") || lower.contains("neft") || lower.contains("imps") || lower.contains("rtgs"))
            && !lower.contains("debited") && !lower.contains("debit");
    }

    private String extractUtr(String body) {
        Matcher m = UTR_PATTERN.matcher(body);
        if (m.find()) return m.group(1);
        // Fallback: look for any standalone 12-digit number
        Matcher fallback = Pattern.compile("\\b([0-9]{12})\\b").matcher(body);
        if (fallback.find()) return fallback.group(1);
        return null;
    }

    private String extractAmount(String body) {
        Matcher m = AMOUNT_PATTERN.matcher(body);
        if (m.find()) {
            // Remove commas, return clean decimal
            return m.group(1).replace(",", "");
        }
        return null;
    }
}
