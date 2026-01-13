<?php
require __DIR__ . '/helpers.php';
require_login();
header('Location: /profile.php#notifications');
exit;
