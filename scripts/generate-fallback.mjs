#!/usr/bin/env node

/**
 * Generate fallback.jpg placeholder image with Barricada branding.
 * Usage: node scripts/generate-fallback.mjs
 */

import puppeteer from 'puppeteer';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const WIDTH = 800;
const HEIGHT = 450;
const OUTPUT = path.resolve(__dirname, '..', 'data', 'fallback.jpg');

const html = `
<!DOCTYPE html>
<html>
<head>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      width: ${WIDTH}px;
      height: ${HEIGHT}px;
      background: #2d2d2d;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      font-family: 'Arial', sans-serif;
      color: #ffffff;
      overflow: hidden;
    }
    .container {
      text-align: center;
      opacity: 0.6;
    }
    .logo {
      font-size: 48px;
      font-weight: 800;
      color: #d90429;
      letter-spacing: -1px;
      margin-bottom: 16px;
    }
    .text {
      font-size: 18px;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 2px;
    }
    .line {
      width: 60px;
      height: 3px;
      background: #d90429;
      margin: 16px auto;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">DB</div>
    <div class="line"></div>
    <div class="text">Imagen no disponible</div>
  </div>
</body>
</html>
`;

async function main() {
  console.log('🎨 Generating fallback.jpg...');

  const browser = await puppeteer.launch({
    headless: 'new',
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  const page = await browser.newPage();
  await page.setViewport({ width: WIDTH, height: HEIGHT });
  await page.setContent(html, { waitUntil: 'networkidle0' });

  const buffer = await page.screenshot({
    type: 'jpeg',
    quality: 80,
    clip: { x: 0, y: 0, width: WIDTH, height: HEIGHT },
  });

  await browser.close();

  fs.mkdirSync(path.resolve(__dirname, '..', 'data'), { recursive: true });
  fs.writeFileSync(OUTPUT, buffer);

  console.log(`✅ Fallback image saved to: ${OUTPUT}`);
  console.log(`📐 Dimensions: ${WIDTH}x${HEIGHT}px`);
}

main().catch((err) => {
  console.error('❌ Failed to generate fallback image:', err.message);
  process.exit(1);
});
