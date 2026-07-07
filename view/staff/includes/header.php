<?php
/**
 * Staff Page Header - Includes common head elements and sidebar
 * 
 * @var array $permissions - Array of permission flags
 * @var string $pageTitle - Page title
 * @var string $activePage - Current page for sidebar highlighting
 * @var string $userName - Current user's name
 * @var string $userRole - Current user's role
 */

// No need for require_once here as it's already loaded in the main file

$pageTitle = $pageTitle ?? 'Staff Panel - Foodie';
$activePage = $activePage ?? 'dashboard';
$permissions = $permissions ?? [];
$userName = $userName ?? $_SESSION['user_name'] ?? 'Staff';
$userRole = $userRole ?? $_SESSION['user_role'] ?? 'staff';
$isAdmin = $userRole === 'admin';
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
    <?php if (isset($customCss)): ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($customCss); ?>">
    <?php endif; ?>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-[#F8FAFC] flex h-screen text-slate-800 antialiased overflow-hidden">