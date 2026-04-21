// Importar estilos (Sass o CSS donde importas Tailwind)
import './style.css'; 

// Importar tipografías e iconos locales (ahora cargados vía CDN en base.twig)

// Importar librerías JS
import 'flowbite';
import htmx from 'htmx.org';
import Alpine from 'alpinejs';

// Inicializar Alpine
window.Alpine = Alpine;
Alpine.start();

// HTMX ya se inicializa solo al importarlo
window.htmx = htmx;

// Firma en consola
console.log(
    `%c
  ___ ____   _    ___    _    ____    ____   ___  _   _ ___ _     _        _    
 |_ _/ ___| / \\  |_ _|  / \\  / ___|  | __ ) / _ \\| \\ | |_ _| |   | |      / \\   
  | |\\___ \\/ _ \\  | |  / _ \\ \\___ \\  |  _ \\| | | |  \\| || || |   | |     / _ \\  
  | | ___) / ___ \\ | | / ___ \\ ___) | | |_) | |_| | |\\  || || |___| |___ / ___ \\ 
 |___|____/_/   \\_\\___/_/   \\_\\____/  |____/ \\___/|_| \\_|___|_____|_____/_/   \\_\\

    👋 ¡Hola! Soy Isaías Bonilla.
    Desarrollador Web (Software Engineer / Webmaster)
    Arquitecto detrás de este ecosistema (WordPress Hybrid Atomic, Node.js).
    Me apasiona el código limpio, el rendimiento web y la analítica (GA4).
    🔗 GitHub: bombiux
    `,
    "color: #d90429; font-weight: bold; font-family: monospace;"
);