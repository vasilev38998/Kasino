<?php
require __DIR__ . '/../helpers.php';
$db = db();
$db->prepare('DELETE FROM audit_log WHERE created_at < NOW() - INTERVAL 90 DAY')->execute();
$db->prepare('DELETE FROM antifraud_events WHERE created_at < NOW() - INTERVAL 90 DAY')->execute();
