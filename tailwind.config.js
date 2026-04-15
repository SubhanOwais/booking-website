import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                // Original color theme:
                // primary: "#5e0009",
                // secondary: "#b0956b",
                // accent: "#F59E0B",
                ink: "#0F172A",

                // New Qater Airlines color theme:
                // primary:   "#8e2157",  // main brand color
                // primary:   "#6f1843",  // main brand color
                // secondary: "#981d58", // lighter glow
                // accent:    "#310219", // darker depth

                // New color theme: Tech Zone Solutions color scheme
                primary: "#0d2b3e",   // Dark navy/teal — logo "SOLUTIONS" banner
                secondary: "#4cbb2f", // Bold green — logo gear & "TECH" text
                accent: "#6dd230",    // Bright lime-green — logo "AND / DESIGNS" bar
                // primary: "#4cbb2f",   // Dark navy/teal — logo "SOLUTIONS" banner
                // secondary: "#0d2b3e", // Bold green — logo gear & "TECH" text
                // accent: "#6dd230",    // Bright lime-green — logo "AND / DESIGNS" bar
            },
            boxShadow: {
                card: "0 18px 55px rgba(2, 8, 23, 0.12)",
                soft: "0 12px 30px rgba(2, 8, 23, 0.10)",
            },
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
