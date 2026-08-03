<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Notification Preferences</title>

<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100">

<div class="max-w-2xl mx-auto mt-10 bg-white rounded-lg shadow p-6">

<h2 class="text-2xl font-bold mb-6">
Notification Preferences
</h2>

<form id="notificationPreferencesForm">

<input
type="hidden"
name="csrf_token"
value="<?= $_SESSION['csrf_token'] ?>"
>

<div class="space-y-4">

<label class="flex items-center justify-between">

<span>Email Notifications</span>

<input
type="checkbox"
name="email_notifications"
id="email_notifications">

</label>

<label class="flex items-center justify-between">

<span>In-App Notifications</span>

<input
type="checkbox"
name="in_app_notifications"
id="in_app_notifications">

</label>

<label class="flex items-center justify-between">

<span>Privacy Incident Alerts</span>

<input
type="checkbox"
name="privacy_incident_alerts"
id="privacy_incident_alerts">

</label>

<label class="flex items-center justify-between">

<span>Consent Updates</span>

<input
type="checkbox"
name="consent_updates"
id="consent_updates">

</label>

<label class="flex items-center justify-between">

<span>Assessment Reminders</span>

<input
type="checkbox"
name="assessment_reminders"
id="assessment_reminders">

</label>

<label class="flex items-center justify-between">

<span>Risk Alerts</span>

<input
type="checkbox"
name="risk_alerts"
id="risk_alerts">

</label>

<label class="flex items-center justify-between">

<span>System Announcements</span>

<input
type="checkbox"
name="system_announcements"
id="system_announcements">

</label>

</div>

<div
id="message"
class="mt-5">
</div>

<button
type="submit"
class="mt-6 bg-blue-600 text-white px-6 py-2 rounded">

Save Preferences

</button>

</form>

</div>

<script src="../assets/js/notification-preferences.js"></script>

</body>

</html>