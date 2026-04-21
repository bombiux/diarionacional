/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    "./**/*.php",
    "./views/**/*.twig",
    "./src/**/*.js",
    "./node_modules/flowbite/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        "primary": "#355da8",
        "primary-dim": "#27519b",
        "secondary": "#006880",
        "secondary-fixed-dim": "#006880", // Using secondary for now
        "tertiary": "#b52900",
        "tertiary-fixed": "#b52900", // Using tertiary for now
        "on-surface": "#203256",
        "surface": "#ffffff",
        "surface-container-lowest": "#ffffff",
        "surface-container-low": "#f3f4f5", // Adjusting slightly
        "surface-container": "rgba(243, 244, 245, 0.8)", // For glassmorphism
        "surface-container-high": "#e7e8e9",
        "surface-container-highest": "#e1e3e4",
        "outline-variant": "rgba(146, 110, 108, 0.15)",
      },
      fontFamily: {
        "sans": ["'Inter'", "sans-serif"],
        "headline": ["'Manrope'", "sans-serif"],
        "display": ["'Manrope'", "sans-serif"],
      },
      borderRadius: {
        "DEFAULT": "0.5rem",
        "lg": "1rem",
        "xl": "1.5rem",
        "2xl": "2rem",
        "3xl": "3rem",
        "full": "9999px"
      }
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
}