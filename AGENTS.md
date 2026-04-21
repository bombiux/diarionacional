# AGENTS.md — Diario Nacional WordPress Theme

> Guía de referencia para agentes de IA (Codex, Gemini, Qwen, Claude, etc.) que trabajen sobre este repositorio.
> Léela completa antes de hacer cambios.

---

## 1. ¿Qué es este proyecto?

**Diario Nacional** es el tema de WordPress personalizado de [diarionacional.net](https://diarionacional.net), un periódico digital de alto impacto. Está construido sobre el stack "Bonilla Stack":

- **Backend:** PHP 8.x + Timber (Twig templating) con PSR-4 (`App\` namespace en `app/`)
- **Frontend:** Vite + Tailwind CSS v3 + Alpine.js + HTMX + Flowbite
- **Entorno:** WordPress + phpdotenv para variables de entorno

---

## 2. Árbol de directorios

```
diarionacional/
├── app/                        # PHP — toda la lógica del tema (PSR-4: App\)
│   └── Setup/
│       ├── Theme.php           # Inicializa Timber, soportes del tema, caché Twig
│       ├── Security.php        # Hardening: headers, XML-RPC, emojis, pingbacks
│       └── Vite.php            # Encola assets: dev → servidor Vite, prod → dist/manifest
├── src/                        # Frontend fuente (punto de entrada Vite)
│   ├── main.js                 # Importa: style.css, Flowbite, HTMX, Alpine.js
│   └── style.css               # @tailwind base/components/utilities + custom layers
├── views/                      # Plantillas Twig (Timber)
│   ├── base.twig               # Shell principal: PillNav, SearchModal, <main>, footer
│   ├── index.twig              # Homepage — Hero, Pulse Grid, Historia, etc.
│   ├── archive.twig            # Categorías/Tags — breadcrumbs, grid y paginación
│   └── partials/
│       ├── card-editorial.twig # Tarjeta principal (Arquitectura Pulse)
│       └── mobile-menu.twig    # Navegación móvil consistente
├── dist/                       # Assets de producción generados por Vite (gitignored)
├── vendor/                     # Dependencias PHP (Composer, gitignored)
├── node_modules/               # Dependencias JS (NPM, gitignored)
├── functions.php               # Bootstrapper: autoload Composer + dotenv + init Setup
├── index.php                   # Controlador homepage: queries Timber → index.twig
├── archive.php                 # Controlador categorías: paginación Timber → archive.twig
├── style.css                   # Cabecera del tema WordPress (solo metadatos, no CSS real)
├── vite.config.js              # Vite: puerto 3000, HMR, recarga en .php/.twig, build → dist/
├── tailwind.config.js          # Content: .php, .twig, .js, flowbite; paleta Architectural Pulse
├── postcss.config.js           # PostCSS con Tailwind + Autoprefixer
├── composer.json               # PHP deps + autoload + dev tools
├── package.json                # Scripts: dev / build; JS deps
├── .env / .env.example         # Variables de entorno (WP_ENV=development activa HMR)
└── GEMINI.md / QWEN.md         # Contexto para otros agentes de IA
```

---

## 3. Stack de dependencias completo

### PHP (`composer.json`)

| Paquete | Versión | Uso |
|---|---|---|
| `timber/timber` | ^2.3 | Twig templating sobre WordPress |
| `vlucas/phpdotenv` | ^5.6 | Variables de entorno (`.env`) |
| `resend/resend-php` | ^1.1 | Envío de correos transaccionales |
| `meilisearch/meilisearch-php` | ^1.16 | Motor de búsqueda avanzado |
| `jgrossi/corcel` | ^6.0 | Acceso estilo Eloquent a datos WP |
| `nesbot/carbon` | ^2.0 | Manejo de fechas/tiempos |
| `phpstan/phpstan` *(dev)* | ^2.1 | Análisis estático |
| `laravel/pint` *(dev)* | ^0.1.2 | Formateador de código PHP |
| `itsgoingd/clockwork` *(dev)* | ^5.3 | Profiler / debugger |
| `symfony/var-dumper` *(dev)* | ^6.0 | Dump de variables en desarrollo |

### JavaScript (`package.json`)

| Paquete | Versión | Uso |
|---|---|---|
| `alpinejs` | ^3.15 | Interactividad cliente ligera |
| `htmx.org` | ^2.0 | Actualizaciones parciales desde servidor |
| `flowbite` | ^4.0 | Componentes UI sobre Tailwind |
| `tailwindcss` *(dev)* | ^3.4 | Framework CSS utility-first |
| `vite` *(dev)* | ^8.0 | Build tool + dev server HMR |
| `sass` *(dev)* | ^1.98 | Soporte SCSS (opcional) |
| `autoprefixer` *(dev)* | ^10.4 | Prefijos CSS automáticos |
| `vite-plugin-live-reload` *(dev)* | ^3.1 | Recarga automática en .php/.twig |
| `puppeteer` *(dev)* | ^24.4 | Testing / scraping headless |

---

## 4. Sistema de diseño: Architectural Pulse

**Paleta de colores** (`tailwind.config.js`):

| Token | Valor | Uso |
|---|---|---|
| `primary` | `#355da8` | Azul institucional, acentos principales |
| `secondary` | `#006880` | Teal oscuro para elementos secundarios |
| `tertiary` | `#b52900` | Naranja/Rojo de contraste |
| `on-surface` | `#203256` | Color de texto principal |
| `surface-container-low` | `#f3f4f5` | Fondos de sección (Tonal Layering) |
| `outline-variant` | `rgba(146, 110, 108, 0.15)` | Separadores sutiles (No-Line Rule) |

**Tipografía:**
- **Titulares:** `Manrope` (pesos 700-800, tracking -0.02em)
- **Cuerpo:** `Inter` (pesos 400-600)
- Cargadas vía Google Fonts CDN en `base.twig`.

**Íconos:** Bootstrap Icons (cargados mediante función `icon()` en Twig).

**Utilidades CSS custom** (en `src/style.css`):
- `.card-pulse` — Tarjeta con sombra profunda y superficie tonal.
- `.pill-nav-glass` — Navegación flotante con glassmorphism.
- `.btn-primary-gradient` — Botón con degradado institucional.
- `.section-alt` — Fondo tonal para alternar secciones sin usar líneas.

---

## 5. Arquitectura de plantillas Twig

```
base.twig
└── {% block content %}
    ├── index.twig (homepage)
│   └── partials/
│       └── card-editorial.twig # Tarjeta principal bajo Architectural Pulse
```

**`base.twig`** provee el shell completo:
- `<header>` flotante (`pill-nav-glass`) con logo, búsqueda modal y acceso de usuario.
- `SearchModal` — Interfaz de búsqueda de alto impacto a pantalla completa.
- `<main class="pt-32">` — Canvas de contenido sin sidebar lateral fijo.
- `<footer>` — Estructura de 4 columnas con branding unificado.

---

## 6. Flujo de datos — Homepage (`index.php`)

El controlador `index.php` pasa el siguiente contexto a `index.twig`:

| Variable Twig | Categoría WP | Uso |
|---|---|---|
| `hero_post` | `destacadas` | Post principal en el Hero |
| `posts_nacionales` | `nacionales` | Grid principal de noticias |
| `posts_internacionales` | `internacionales` | Sección de noticias globales |
| `posts_viral` | `viral` | Widget lateral de tendencias |
| `posts_ambiente` | `ambiente` | Sección de Ecología/Ambiente |
| `posts_salud` | `salud` | Sección de Salud y Bienestar |
| `posts_agricultura` | `agricultura` | Sección de campo y producción |
| `posts_tecnologia` | `tecnologia` | Sección de Tech |
| `posts_deportes` | `deportes` | Sección de Deportes |
| `posts_farandula` | `farandula` | Sección de Entretenimiento |
| `latest_analisis` | `analisis` | Destacado de opinión |
| `latest_galleries` | CPT `galeria` | Carrusel multimedia |

---

## 7. Integración Vite (desarrollo vs producción)

`app/Setup/Vite.php` detecta automáticamente el entorno:

**Desarrollo** (cuando `WP_ENV=development` o el servidor Vite responde en `:3000`):
```html
<script type="module" src="http://localhost:3000/@vite/client"></script>
<script type="module" src="http://localhost:3000/src/main.js"></script>
```

**Producción** (lee `dist/.vite/manifest.json` o `dist/manifest.json`):
```html
<script src="/wp-content/themes/diarionacional/dist/assets/main-[hash].js"></script>
<link rel="stylesheet" href="/wp-content/themes/diarionacional/dist/assets/main-[hash].css">
```

---

## 8. Convenciones de desarrollo — OBLIGATORIAS

### "No-Line Rule"
1. **Evitar bordes de 1px** — Usar `bg-surface-container-low` para separar secciones.
2. **Jerarquía por Sombras** — Usar la clase `.card-pulse` para elevar elementos sobre el fondo.
3. **Tipografía de Precisión** — Respetar el tracking negativo en titulares para un look editorial.

### Arquitectura
4. **Toda la lógica en `app/`** — `functions.php` es solo bootstrapper.
5. **Timber/Twig** — Nunca mezclar HTML con PHP.
6. **Alpine.js** — Preferir para estado cliente ligero sobre vanilla JS.

---

## 9. Comandos esenciales

```bash
# Servidor de desarrollo (HMR activo)
npm run dev

# Build de producción → dist/
npm run build

# Análisis estático PHP
vendor/bin/phpstan analyse app/

# Formatear código PHP
vendor/bin/pint app/
```

---

## 13. Servidor de producción

| Dato | Valor |
|---|---|
| **Alias SSH** | `diarionacional` |
| **Ruta del tema** | `/ruta/al/servidor/wp-content/themes/diarionacional` |

**Despliegue:**
```bash
# Build local
npm run build

# Sincronizar
rsync -avz --exclude='node_modules' --exclude='vendor' ./ diarionacional:/ruta/al/tema/
```

---

*Última actualización: Actualizado al sistema Architectural Pulse para Diario Nacional.*

