<?php
require_once __DIR__ . '/includes/config.php';
requireAuth();
header('Location: pages/dashboard.php');
exit;
