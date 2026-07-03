<?php
/**
 * Head HTML partagé : Tailwind config + polices + design tokens
 * À inclure dans toutes les pages
 */
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="<?= e(APP_TAGLINE) ?>">
<link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/images/favicon.svg">

<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

<script>
tailwind.config = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                'primary': '#b7102a',
                'on-primary': '#ffffff',
                'primary-container': '#db313f',
                'on-primary-container': '#fffbff',
                'primary-fixed': '#ffdad8',
                'primary-fixed-dim': '#ffb3b1',
                'on-primary-fixed': '#410007',
                'on-primary-fixed-variant': '#92001c',
                'inverse-primary': '#ffb3b1',
                'secondary': '#8e4e14',
                'on-secondary': '#ffffff',
                'secondary-container': '#ffab69',
                'on-secondary-container': '#783d01',
                'secondary-fixed': '#ffdcc4',
                'secondary-fixed-dim': '#ffb780',
                'on-secondary-fixed': '#2f1400',
                'on-secondary-fixed-variant': '#6f3800',
                'tertiary': '#00685d',
                'on-tertiary': '#ffffff',
                'tertiary-container': '#008376',
                'on-tertiary-container': '#f4fffb',
                'tertiary-fixed': '#8cf5e4',
                'tertiary-fixed-dim': '#6fd8c8',
                'on-tertiary-fixed': '#00201c',
                'on-tertiary-fixed-variant': '#005048',
                'error': '#ba1a1a',
                'on-error': '#ffffff',
                'error-container': '#ffdad6',
                'on-error-container': '#93000a',
                'surface': '#f8f9fa',
                'surface-dim': '#d9dadb',
                'surface-bright': '#f8f9fa',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#f3f4f5',
                'surface-container': '#edeeef',
                'surface-container-high': '#e7e8e9',
                'surface-container-highest': '#e1e3e4',
                'on-surface': '#191c1d',
                'on-surface-variant': '#5b403f',
                'inverse-surface': '#2e3132',
                'inverse-on-surface': '#f0f1f2',
                'outline': '#8f6f6e',
                'outline-variant': '#e4bebc',
                'background': '#f8f9fa',
                'on-background': '#191c1d',
            },
            fontFamily: {
                'display': ['Poppins', 'sans-serif'],
                'body': ['Inter', 'sans-serif'],
            },
            borderRadius: {
                'DEFAULT': '0.5rem',
                'sm': '0.25rem',
                'md': '0.75rem',
                'lg': '1rem',
                'xl': '1.5rem',
            },
            boxShadow: {
                'soft': '0 2px 8px rgba(0,0,0,0.04)',
                'card': '0 4px 16px rgba(0,0,0,0.06)',
                'hover': '0 8px 24px rgba(183,16,42,0.15)',
            },
            animation: {
                'fade-in': 'fadeIn 0.5s ease-out',
                'slide-up': 'slideUp 0.6s ease-out',
                'pulse-slow': 'pulse 3s ease-in-out infinite',
            },
            keyframes: {
                fadeIn: { '0%': { opacity: 0 }, '100%': { opacity: 1 } },
                slideUp: { '0%': { opacity: 0, transform: 'translateY(20px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
            },
        }
    }
};
</script>

<style>
    * { font-family: 'Inter', sans-serif; }
    h1, h2, h3, h4, h5, .font-display { font-family: 'Poppins', sans-serif; }
    .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    .icon-fill { font-variation-settings: 'FILL' 1; }
    .glass {
        background: rgba(255, 255, 255, 0.75);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
    }
    .glass-dark {
        background: rgba(25, 28, 29, 0.6);
        backdrop-filter: blur(14px);
    }
    body { background: #f8f9fa; color: #191c1d; }
    ::selection { background: #b7102a; color: white; }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    /* Loader overlay */
    .page-loader {
        position: fixed; inset: 0; background: white; z-index: 9999;
        display: flex; align-items: center; justify-content: center;
        animation: fadeOut 0.4s ease-in 0.3s forwards;
        pointer-events: none;
    }
    @keyframes fadeOut { to { opacity: 0; visibility: hidden; } }
</style>
