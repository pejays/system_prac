<?php
$hash = '$2y$10$O37i7lZLsGObnnie.dwrMuEPNK2A/J35rO0ZboCZxXMEwu9OQYZcKE';

if (password_verify('password123', $hash)) {
    echo "✅ MATCH — the hash IS password123";
} else {
    echo "❌ NO MATCH — the hash is for a different password";
}

echo "<br><br>New valid hash for 'password123':<br>";
echo password_hash('password123', PASSWORD_DEFAULT);