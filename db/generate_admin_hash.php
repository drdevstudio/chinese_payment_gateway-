<?php
/**
 * Run this script once to generate the admin password hash.
 * Usage: php generate_admin_hash.php
 * Then paste the output into firebase_seed.json and import to Firebase.
 */
$password = 'YOUR_ADMIN_PASSWORD_HERE'; // <-- Change this!
echo "Password hash for '$password':\n";
echo password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
echo "\n\nPaste this as the 'password' value in the admins/admin node in Firebase.\n";
