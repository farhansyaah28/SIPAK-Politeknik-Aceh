<?php
// SIIPAK - Tailwind Head Include
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/functions.php';

$page_title = $page_title ?? 'SIPAK Politeknik Aceh - Sistem Informasi Pengelolaan Aset';
?>
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title><?= htmlspecialchars($page_title) ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
    <!-- Material Symbols -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    "colors": {
                        "outline-variant": "#c5c5d2",
                        "surface-variant": "#d3e3ff",
                        "on-secondary-fixed": "#241a00",
                        "on-error-container": "#93000a",
                        "error-red": "#ba1a1a",
                        "error-container": "#ffdad6",
                        "secondary": "#745b00",
                        "surface-container-high": "#dde9ff",
                        "surface": "#f8f9ff",
                        "primary-fixed": "#dce1ff",
                        "background": "#f8f9ff",
                        "on-tertiary-container": "#5d9d5f",
                        "surface-bright": "#f8f9ff",
                        "tertiary-fixed-dim": "#93d693",
                        "tertiary-container": "#002f0b",
                        "on-secondary-fixed-variant": "#584400",
                        "error": "#ba1a1a",
                        "on-tertiary-fixed": "#002106",
                        "primary": "#000e3a",
                        "inverse-primary": "#b5c4ff",
                        "on-error": "#ffffff",
                        "secondary-fixed-dim": "#e5c366",
                        "surface-blue": "#f8f9ff",
                        "secondary-fixed": "#ffe08d",
                        "surface-tint": "#455aa2",
                        "on-primary": "#ffffff",
                        "outline-muted": "#c4c5d5",
                        "primary-fixed-dim": "#b5c4ff",
                        "on-tertiary-fixed-variant": "#0f521e",
                        "on-primary-fixed": "#00164e",
                        "outline": "#757682",
                        "on-primary-container": "#758bd6",
                        "inverse-surface": "#213146",
                        "surface-dim": "#cbdbf6",
                        "surface-container-low": "#eff4ff",
                        "warning-amber": "#fecb00",
                        "surface-container": "#e6eeff",
                        "on-secondary-container": "#775e03",
                        "primary-container": "#002068",
                        "on-primary-fixed-variant": "#2b4289",
                        "surface-container-highest": "#d3e3ff",
                        "on-tertiary": "#ffffff",
                        "tertiary-fixed": "#aff3ad",
                        "tertiary": "#001803",
                        "inverse-on-surface": "#ebf1ff",
                        "on-surface": "#0b1c30",
                        "secondary-container": "#fdd979",
                        "on-background": "#0b1c30",
                        "on-secondary": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "on-surface-variant": "#444651",
                        "success-green": "#45bf59"
                    },
                    "borderRadius": {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                    "spacing": {
                        "xl": "32px",
                        "container-margin": "16px",
                        "sm": "12px",
                        "gutter": "16px",
                        "touch-target": "48px",
                        "base": "4px",
                        "lg": "24px",
                        "md": "16px",
                        "xs": "8px"
                    },
                    "fontFamily": {
                        "display-md": ["Plus Jakarta Sans"],
                        "headline-md": ["Plus Jakarta Sans"],
                        "label-lg": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "display-lg": ["Plus Jakarta Sans"]
                    },
                    "fontSize": {
                        "headline-md": ["15px", {"lineHeight": "22px", "fontWeight": "700"}],
                        "headline-lg-mobile": ["20px", {"lineHeight": "26px", "fontWeight": "800"}],
                        "display-md": ["21px", {"lineHeight": "28px", "letterSpacing": "-0.01em", "fontWeight": "800"}],
                        "headline-lg": ["17px", {"lineHeight": "24px", "fontWeight": "700"}],
                        "display-lg": ["24px", {"lineHeight": "32px", "letterSpacing": "-0.02em", "fontWeight": "800"}],
                        "body-md": ["13px", {"lineHeight": "18px", "fontWeight": "400"}],
                        "label-md": ["11px", {"lineHeight": "14px", "fontWeight": "600"}],
                        "body-lg": ["14px", {"lineHeight": "20px", "fontWeight": "400"}],
                        "label-lg": ["13px", {"lineHeight": "18px", "fontWeight": "600"}]
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8f9ff; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .shadow-soft { box-shadow: 0px 4px 12px rgba(0,0,0,0.05); }
        .hero-gradient { background: linear-gradient(135deg, #002068 0%, #00164e 100%); }
    </style>
</head>
<body class="text-on-surface flex flex-col min-h-screen">
