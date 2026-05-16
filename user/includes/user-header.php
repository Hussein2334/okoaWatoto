<?php
// user/includes/user-header.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication for user
if (!isset($_SESSION['user_id'])) {
    header("Location: /okoaWatoto/login.php");
    exit();
}

$page_title = $page_title ?? 'User Dashboard';
?>
<!DOCTYPE html>
<html class="light" lang="sw">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>OkoaWatoto - <?php echo $page_title; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <!-- Chart.js for Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&amp;display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    
    <style>
        body { font-family: 'Public Sans', sans-serif; background-color: #f7fafc; }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            display: inline-block;
            vertical-align: middle;
        }
        .leaflet-container {
            border-radius: 0.75rem;
            z-index: 1;
        }
    </style>
    
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#002045",
                        "secondary": "#0a6c44",
                        "error": "#ba1a1a",
                        "surface": "#f7fafc",
                        "on-surface": "#181c1e",
                        "on-surface-variant": "#43474e",
                        "outline": "#74777f",
                        "outline-variant": "#c4c6cf",
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-surface text-on-surface">

<?php require_once __DIR__ . '/user-sidebar.php'; ?>