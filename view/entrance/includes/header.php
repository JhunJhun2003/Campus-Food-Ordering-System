<?php
/**
 * Entrance Page Header
 * 
 * @var string $pageTitle - Page title
 * @var string $customCss - Custom CSS file path
 * @var string $error - Error message
 * @var string $success - Success message
 */

$pageTitle = $pageTitle ?? 'Foodie - Login & Register';
$customCss = $customCss ?? 'css/login.css';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($customCss); ?>">
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 md:p-8"></body>