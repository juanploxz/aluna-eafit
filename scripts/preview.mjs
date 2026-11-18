/** Local-only real WordPress/PHP preview. Production uses native PHP + MariaDB. */
import { createRequire } from 'node:module';
import { fileURLToPath, pathToFileURL } from 'node:url';
import path from 'node:path';
import fs from 'node:fs/promises';

const project = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const runtimePath = path.resolve(process.env.ALUNA_RUNTIME || path.join(project, '.preview'));
const require = createRequire(import.meta.url);

async function dependency(name) {
  return import(pathToFileURL(require.resolve(name, { paths: [runtimePath, path.join(project, 'scripts')] })).href);
}

async function ensureWordPress(siteRoot) {
  const root = siteRoot || path.join(runtimePath, 'site', 'wordpress');
  try { await fs.access(path.join(root, 'wp-includes', 'version.php')); return root; } catch { /* fresh preview */ }
  const { ZipReader, Uint8ArrayReader, Uint8ArrayWriter } = await dependency('@zip.js/zip.js');
  console.log('Downloading official WordPress 7.1.4 for local verification…');
  const response = await fetch('https://wordpress.org/wordpress-7.1.4.zip');
  if (!response.ok) throw new Error(`WordPress download failed: HTTP ${response.status}`);
  const reader = new ZipReader(new Uint8ArrayReader(new Uint8Array(await response.arrayBuffer())));
  const extractionRoot = path.resolve(runtimePath, 'site');
  for (const entry of await reader.getEntries()) {
    const target = path.resolve(extractionRoot, entry.filename);
    if (!target.startsWith(extractionRoot + path.sep)) throw new Error('Unsafe ZIP path');
    if (entry.directory) await fs.mkdir(target, { recursive: true });
    else {
      await fs.mkdir(path.dirname(target), { recursive: true });
      await fs.writeFile(target, await entry.getData(new Uint8ArrayWriter()));
    }
  }
  await reader.close();
  return root;
}

export async function startPreview({ port = Number(process.env.PORT || 8090), testing = false, siteRoot } = {}) {
  const [major, minor] = process.versions.node.split('.').map(Number);
  if (major < 24 || (major === 24 && minor < 18)) throw new Error('Use Node.js 24.18 or newer.');
  const root = await ensureWordPress(siteRoot);
  const { runCLI } = await dependency('@wp-playground/cli');
  const server = await runCLI({
    command: 'server', port, php: '8.3', wp: '7.1.4', workers: 6,
    'site-url': `http://127.0.0.1:${port}`,
    wordpressInstallMode: 'install-from-existing-files-if-needed',
    'mount-before-install': [{ hostPath: root, vfsPath: '/wordpress' }],
    mount: [
      { hostPath: path.join(project, 'wp-content', 'plugins', 'aluna-core'), vfsPath: '/wordpress/wp-content/plugins/aluna-core' },
      { hostPath: path.join(project, 'wp-content', 'themes', 'aluna'), vfsPath: '/wordpress/wp-content/themes/aluna' },
    ],
    'define-bool': { WP_DEBUG: true, WP_DEBUG_DISPLAY: false, DISALLOW_FILE_EDIT: true },
    login: false,
    blueprint: {
      steps: [
        { step: 'activatePlugin', pluginPath: 'aluna-core/aluna-core.php' },
        { step: 'activateTheme', themeFolderName: 'aluna' },
        { step: 'setSiteOptions', options: { blogname: 'ALUNA', blogdescription: 'Cuidado que se siente.', WPLANG: 'es_CO', timezone_string: 'America/Bogota', permalink_structure: '/%postname%/', show_on_front: 'posts' } },
        { step: 'runPHP', code: "<?php require '/wordpress/wp-load.php'; if (function_exists('aluna_seed_catalog')) { aluna_seed_catalog(); } flush_rewrite_rules();" }
      ]
    }
  });
  // Playground 3.1.65 exposes no initial listen-host option. It binds all
  // interfaces while booting; this rebind protects the final running preview,
  // not the initial startup interval. See docs/EDICION-Y-PRUEBAS.md.
  await new Promise((resolve, reject) => server.server.close(error => error ? reject(error) : resolve()));
  await new Promise((resolve, reject) => server.server.once('error', reject).listen(port, '127.0.0.1', resolve));
  console.log(`ALUNA preview: http://127.0.0.1:${port} (WordPress 7.1.4, PHP 8.3, SQLite; local only)`);
  if (!testing) {
    console.log('Admin de demostración local: /wp-admin/ · usuario admin · contraseña password');
    const stop = async () => { await server[Symbol.asyncDispose](); process.exit(0); };
    process.once('SIGINT', stop); process.once('SIGTERM', stop);
  }
  return server;
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  startPreview().catch(error => { console.error(error); process.exitCode = 1; });
}
