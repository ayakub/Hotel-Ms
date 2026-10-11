<?php
// Run this once from CLI or browser to generate a bcrypt hash,
// then paste it into the Users table with an UPDATE statement.
// Usage: php generate_password.php password123
$plain = $argv[1] ?? 'password123';
echo "Hash for '$plain':\n" . password_hash($plain, PASSWORD_DEFAULT) . "\n";
