import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    // Dot.Pulse is a committed-dark product (welcome page, dashboard shell, and
    // every other custom surface are hardcoded dark regardless of OS preference).
    // Force Jetstream's stock dark: classes to match instead of leaving them to
    // fall back to prefers-color-scheme, which silently reverts the login/register/
    // forgot-password shell and the profile settings cards to Tailwind's stock
    // light theme for any visitor whose OS reports a light preference.
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Dot.Pulse's real brand gold (sampled from the official logo,
                // Downloads/Dot.logos/dot.pulse.png) — overrides Tailwind's default
                // indigo scale so every existing indigo-* utility class already used
                // across the app (buttons, links, focus rings, badges) picks up the
                // real brand color instead of stock Jetstream indigo.
                indigo: {
                    50: '#fdf8e8',
                    100: '#faedc3',
                    200: '#f6dd8f',
                    300: '#f3ce61',
                    400: '#f2c845',
                    500: '#f1c62e',
                    600: '#cda223',
                    700: '#a37f19',
                    800: '#7a5d10',
                    900: '#4a3907',
                },
                // Dot.Pulse's real brand navy (also from the official logo) — the
                // chevron / wordmark color, used as ink on gold surfaces and as a
                // secondary accent.
                'pulse-navy': {
                    DEFAULT: '#08354f',
                    50: '#e8eef1',
                    100: '#c5d5db',
                    300: '#4d7f92',
                    500: '#08354f',
                    700: '#062940',
                    900: '#041a2b',
                },
            },
        },
    },

    plugins: [forms, typography],
};
