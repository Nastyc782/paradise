<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['accountant', 'admin']);
$accountantReadOnly = true;
require_once __DIR__ . '/../admin/payments.php';
