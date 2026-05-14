<?php
session_start();
session_unset();
session_destroy();
require_once 'db.php';
echo json_encode(['success' => true, 'message' => 'Logged out.']);
