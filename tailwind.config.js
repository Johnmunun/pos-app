import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    darkMode: 'class', // Active le dark mode basé sur la classe

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                float: {
                    '0%, 100%': { transform: 'translateY(0px) rotate(0deg)' },
                    '50%': { transform: 'translateY(-20px) rotate(5deg)' },
                },
                'float-slow': {
                    '0%, 100%': { transform: 'translateY(0px) rotate(0deg)' },
                    '50%': { transform: 'translateY(-30px) rotate(-5deg)' },
                },
                'float-gentle': {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
            },
            animation: {
                float: 'float 6s ease-in-out infinite',
                'float-slow': 'float-slow 8s ease-in-out infinite',
                'float-gentle': 'float-gentle 5s ease-in-out infinite',
            },
            boxShadow: {
                'landing-soft':
                    '0 22px 50px -12px rgba(15, 23, 42, 0.08), 0 12px 24px -10px rgba(15, 23, 42, 0.06)',
                'landing-soft-lg':
                    '0 32px 64px -16px rgba(15, 23, 42, 0.12), 0 16px 32px -12px rgba(15, 23, 42, 0.08)',
                'landing-inner-glow': 'inset 0 1px 0 0 rgba(255, 255, 255, 0.55)',
            },
        },
    },

    plugins: [forms],
};
