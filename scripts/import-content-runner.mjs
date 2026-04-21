#!/usr/bin/env node

/**
 * Wrapper that runs import-content.php inside the Docker container.
 *
 * Reads container ID from .dockerrc and executes the PHP import script.
 * Usage: npm run import:content
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawn } from 'node:child_process';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Read .dockerrc to get container ID
const dockerrcPath = path.resolve(__dirname, '..', '.dockerrc');
const dockerrc = JSON.parse(fs.readFileSync(dockerrcPath, 'utf-8'));

// Support both old array format and new object format
let containerId;
if (dockerrc.containers.wp) {
  containerId = dockerrc.containers.wp;
} else if (Array.isArray(dockerrc.containers)) {
  containerId = dockerrc.containers[0];
} else {
  containerId = dockerrc.containers;
}

if (!containerId) {
  console.error('❌ No container ID found in .dockerrc');
  process.exit(1);
}

console.log(`🐳 Running import in container: ${containerId}`);

// Build the command
const workDir = '/var/www/html/wp-content/themes/DBarricada';
const command = `docker exec -w ${workDir} ${containerId} php scripts/import-content.php`;

// Execute and pipe output to console
const child = spawn(command, {
  shell: true,
  stdio: 'inherit',
});

child.on('error', (err) => {
  console.error('❌ Failed to execute import:', err.message);
  process.exit(1);
});

child.on('close', (code) => {
  if (code !== 0) {
    console.error(`❌ Import failed with exit code ${code}`);
    process.exit(code);
  }
  console.log('\n✅ Import complete!');
});
