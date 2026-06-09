import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // Class strategy: <html class="dark"> flips the dark-variant
    // utilities. The class is set by the inline theme bootstrap in
    // <head> based on the `theme_preference` cookie (auto|light|dark),
    // with `auto` falling back to the OS `prefers-color-scheme`.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Models/MangoVariety.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
