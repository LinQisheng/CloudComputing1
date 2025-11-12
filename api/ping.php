<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true, 'host' => gethostname()]);