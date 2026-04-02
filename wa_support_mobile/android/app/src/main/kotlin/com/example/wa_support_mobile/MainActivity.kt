package com.example.wa_support_mobile

import android.app.NotificationChannel
import android.app.NotificationManager
import android.os.Build
import android.os.Bundle
import io.flutter.embedding.android.FlutterActivity

class MainActivity : FlutterActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        ensureSupportAlertChannel()
        super.onCreate(savedInstanceState)
    }

    private fun ensureSupportAlertChannel() {
        if (Build.VERSION.SDK_INT < Build.VERSION_CODES.O) {
            return
        }
        val nm = getSystemService(NotificationManager::class.java) ?: return
        val ch = NotificationChannel(
            CHANNEL_ID,
            "Support alerts",
            NotificationManager.IMPORTANCE_HIGH,
        ).apply {
            description = "New customer WhatsApp messages"
            enableVibration(true)
            setShowBadge(true)
        }
        nm.createNotificationChannel(ch)
    }

    companion object {
        private const val CHANNEL_ID = "wa_support_alerts"
    }
}
