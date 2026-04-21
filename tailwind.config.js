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
      "colors": {
        "tertiary-dim": "#a02300",
        "on-background": "#203256",
        "background": "#faf9ff",
        "surface-container-lowest": "#ffffff",
        "secondary-container": "#b7eaff",
        "secondary-fixed-dim": "#8ae0ff",
        "primary-dim": "#27519b",
        "outline": "#697aa3",
        "on-primary-fixed": "#00255b",
        "on-tertiary-fixed-variant": "#7d1900",
        "secondary": "#006880",
        "tertiary-fixed-dim": "#ff8b6e",
        "outline-variant": "#a0b2dd",
        "surface-tint": "#355da8",
        "surface-bright": "#faf9ff",
        "on-secondary": "#f1faff",
        "surface-dim": "#ccdaff",
        "surface-container-high": "#e1e8ff",
        "tertiary-container": "#ffa089",
        "on-secondary-fixed-variant": "#00647b",
        "on-secondary-fixed": "#004657",
        "tertiary": "#b52900",
        "primary-fixed-dim": "#90b2ff",
        "error": "#ac3434",
        "error-container": "#f56965",
        "secondary-dim": "#005b70",
        "surface-variant": "#d9e2ff",
        "surface-container-low": "#f1f3ff",
        "primary-fixed": "#a6c0ff",
        "primary-container": "#a6c0ff",
        "primary": "#355da8",
        "on-error": "#fff7f6",
        "inverse-primary": "#85aafb",
        "on-surface-variant": "#4d5f86",
        "on-surface": "#203256",
        "inverse-on-surface": "#949db5",
        "on-primary-container": "#003983",
        "on-secondary-container": "#00596f",
        "on-primary": "#f9f8ff",
        "on-tertiary-fixed": "#450900",
        "surface-container-highest": "#d9e2ff",
        "secondary-fixed": "#b7eaff",
        "tertiary-fixed": "#ffa089",
        "on-error-container": "#65000b",
        "inverse-surface": "#050e20",
        "surface": "#faf9ff",
        "on-tertiary-container": "#6d1500",
        "on-tertiary": "#fff7f6",
        "surface-container": "#e9edff",
        "error-dim": "#70030f",
        "on-primary-fixed-variant": "#13428d"
      },
      "borderRadius": {
        "DEFAULT": "0.25rem",
        "lg": "0.5rem",
        "xl": "0.75rem",
        "full": "9999px"
      },
      "fontFamily": {
        "headline": ["Manrope", "sans-serif"],
        "body": ["Inter", "sans-serif"],
        "label": ["Inter", "sans-serif"]
      }
    },
  },
  plugins: [
    require('flowbite/plugin')
  ],
}