# 🚀 Bonilla Stack — WordPress Starter Theme

Este es un **Starter Kit** (plantilla base) para temas de WordPress personalizados. Está diseñado para desarrolladores que buscan un flujo de trabajo moderno, rápido y basado en componentes.

---

## 🏗️ El Stack (Tecnologías)

- **Backend:** [Timber](https://upstatement.com/timber/) (Twig) + PHP 8.x (PSR-4).
- **Build Tool:** [Vite](https://vitejs.dev/) (con HMR y soporte para PHP/Twig).
- **CSS:** [Tailwind CSS v3](https://tailwindcss.com/) + [Flowbite](https://flowbite.com/).
- **JS:** [Alpine.js](https://alpinejs.dev/) + [HTMX](https://htmx.org/).
- **Manejo de Datos:** [Corcel](https://github.com/jgrossi/corcel) + [Carbon](https://carbon.nesbot.com/).

---

## 📦 Instalación y Setup (Nuevo Proyecto)

Para usar este starter en un nuevo proyecto:

1. **Clona el repo** dentro de `wp-content/themes/`.
2. **Inicializa el tema:**
   ```bash
   npm run init:theme
   ```
   *Te pedirá el Nombre y el Slug de tu nuevo proyecto para renombrar todo automáticamente.*
3. **Instala dependencias:**
   ```bash
   composer install
   npm install
   ```
4. **Configura tu entorno:**
   ```bash
   cp .env.example .env
   ```
   *Asegúrate de que `WP_ENV=development` esté activo en tu local para usar Vite.*

5. **Inicia el desarrollo:**
   ```bash
   npm run dev
   ```

---

## 🚀 Despliegue (Build)

Para generar los archivos listos para producción:
```bash
npm run build
```
Esto generará la carpeta `dist/` con todos los assets minificados y versionados.

---

## 🛠️ Comandos Útiles

- `npm run init:theme`: Renombra el proyecto completo tras descargarlo.
- `npm run dev`: Desarrollo con recarga ultra rápida.
- `npm run build`: Generar assets de producción.
- `vendor/bin/pint`: Limpiar y formatear el código PHP.

---

Generado como base de alto rendimiento para WordPress.
