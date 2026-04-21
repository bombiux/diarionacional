# Guía de Medidas de Imágenes — DBarricada

Esta guía detalla las dimensiones y proporciones recomendadas para que las imágenes cargadas en WordPress se vean correctamente en el tema, respetando el diseño panorámico y las esquinas redondeadas.

---

## 1. Cabecera de Artículo (Single Post)
Es la imagen principal que aparece al abrir una noticia.
- **Medida recomendada:** `1280 x 420 px`
- **Proporción:** Panorámica (aprox 3:1).
- **Efecto:** Se muestra contenida con bordes redondeados de `2.5rem`.

## 2. Portada Principal (Hero Portal)
El banner grande que aparece en la parte superior de la página de inicio.
- **Medida recomendada:** `1600 x 500 px`
- **Proporción:** Panorámica extendida.
- **Uso:** Solo para los posts marcados como "Destacados" o "Hero".

## 3. Tarjetas de Noticias (Cards)
Imágenes que aparecen en las grillas de categorías (Nacionales, Historia, Economía, etc.).
- **Medida recomendada:** `800 x 450 px`
- **Proporción:** `16:9` (Estándar de video).
- **Uso:** Imagen destacada estándar para todos los artículos.

## 4. Galerías de Imágenes
- **Miniaturas (Grid):** `600 x 600 px` (Formato cuadrado).
- **Imagen Completa:** Alta resolución. El sistema redimensiona automáticamente para el visor, pero se recomienda subir originales de buena calidad.

---

## Recomendaciones Técnicas

### Formato y Optimización
- **Formato Sugerido:** WebP (mejor compresión y menos peso).
- **Peso Máximo:** Intentar que las imágenes no superen los **250 KB**.
- **Herramientas de Optimización:** `Squoosh.app` o plugins de WordPress como `Converter for Media`.

### Composición (Object Cover)
El tema utiliza la propiedad `object-cover` para asegurar que las imágenes llenen el espacio sin deformarse.
- **Punto de Interés:** Mantén el motivo principal de la foto (caras, objetos) en el **centro geométrico** de la imagen para evitar que se corten en diferentes tamaños de pantalla (móvil vs escritorio).

---
*Última actualización: 17 de abril de 2026*
