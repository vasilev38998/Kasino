<?php
require __DIR__ . '/../helpers.php';
$db = db();
$db->prepare('DELETE FROM bonuses WHERE expires_at IS NOT NULL AND expires_at < NOW()')->execute();
