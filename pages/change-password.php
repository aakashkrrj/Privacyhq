<?php
session_start();
?>



<div class="max-w-xl mx-auto mt-10 bg-white rounded-lg shadow p-6">

<h2 class="text-2xl font-bold mb-6">
Change Password
</h2>

<form id="changePasswordForm">

<input
type="hidden"
name="csrf_token"
id="csrf_token"
value="<?=$_SESSION['csrf_token']?>"
>

<div class="mb-4">

<label class="block mb-2">
Current Password
</label>

<input
type="password"
name="current_password"
class="w-full border rounded px-3 py-2"
required>

</div>

<div class="mb-4">

<label class="block mb-2">
New Password
</label>

<input
type="password"
name="new_password"
class="w-full border rounded px-3 py-2"
required>

</div>

<div class="mb-6">

<label class="block mb-2">
Confirm Password
</label>

<input
type="password"
name="confirm_password"
class="w-full border rounded px-3 py-2"
required>

</div>

<div id="message"></div>

<button
type="submit"
class="bg-blue-600 text-white px-5 py-2 rounded">

Change Password

</button>

</form>

</div>

<script src="../assets/js/change-password.js"></script>