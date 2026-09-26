<?php
$req = $_GET['req'] ?? '';
if ($req === 'update') {
    include __DIR__ . '/edit_users_credential.php';
} else {
    include __DIR__ . '/new_users_credential.php';
}
?>
