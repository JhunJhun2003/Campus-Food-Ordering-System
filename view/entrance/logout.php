<?php
session_start();

require_once __DIR__ . '/../../vendor/autoload.php';

use App\User\Presentation\Http\Controllers\UserController;

$controller = new UserController();
$controller->logout();

// The logout method handles redirect, but just in case:
header('Location: login.php');
exit();