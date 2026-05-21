const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    blue: '#0a1f44',
                    'blue-light': '#1c355d',
                    'blue-mid': '#152a4d',
                    gold: '#f5c242',
                    'gold-dark': '#d4a017',
                }
            }
        },
    },

    plugins: [require('@tailwindcss/forms')],
};
