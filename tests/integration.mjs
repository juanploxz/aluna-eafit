import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { startPreview, runtimePath } from '../scripts/preview.mjs';

const output = path.dirname(fileURLToPath(import.meta.url));
const testDirectory = path.join(runtimePath, 'test-site');
const root = path.join(testDirectory, 'wordpress');
await fs.mkdir(testDirectory, { recursive: true });
await fs.cp(path.join(runtimePath, 'site', 'wordpress'), root, {
  recursive: true,
  filter: source => !source.endsWith('wp-config.php') && !source.includes(`${path.sep}database`) && !source.includes(`${path.sep}uploads`),
});
const server = await startPreview({ port: 8091, testing: true, siteRoot: root });
const results = [];
const check = (name, value) => { assert.ok(value, name); results.push(name); console.log(`PASS ${name}`); };
try {
  const lint = await server.playground.run({ code: `<?php $checked=0; foreach (['/wordpress/wp-content/plugins/aluna-core','/wordpress/wp-content/themes/aluna'] as $root) { foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) { if ($file->isFile() && $file->getExtension()==='php') { token_get_all(file_get_contents($file->getPathname()), TOKEN_PARSE); $checked++; } } } echo $checked;` });
  check('All PHP files parse without syntax errors', lint.exitCode === 0 && !lint.errors && Number(lint.text) > 10);
  let unitSource = await fs.readFile(path.join(output, '../wp-content/plugins/aluna-core/tests/unit.php'), 'utf8');
  unitSource = unitSource.replace(/if \(PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg'\) \{[\s\S]*?\}\s*/, '');
  unitSource = unitSource.replaceAll('__DIR__', "'/wordpress/wp-content/plugins/aluna-core/tests'");
  unitSource = unitSource.replace('<?php', "<?php define('STDOUT', fopen('php://output', 'w')); define('STDERR', fopen('php://stderr', 'w')); ");
  const unit = await server.playground.run({ code: unitSource });
  check(`Unit assertions: ${unit.text.trim()}`, unit.exitCode === 0 && !unit.errors && /assertions passed/.test(unit.text));
  const php = await server.playground.run({ code: `<?php require '/wordpress/wp-load.php'; define('ALUNA_TESTING', true); require '/wordpress/wp-content/plugins/aluna-core/tests/integration.php'; echo json_encode(aluna_run_integration_checks(), JSON_UNESCAPED_UNICODE);` });
  check('PHP integration checks exit successfully', php.exitCode === 0);
  check('PHP integration checks have no errors', !php.errors);
  const decoded = JSON.parse(php.text);
  for (const result of decoded) { check(`PHP ${result.name}`, result.pass); }
  console.log(php.text);

  const base = 'http://127.0.0.1:8091';
  const first = await fetch(base + '/');
  const html = await first.text();
  check('Homepage returns 200', first.status === 200);
  check('Homepage uses Spanish language', /<html[^>]+lang="es(?:-[A-Z]{2})?"/.test(html));
  check('Homepage has no PHP fatal/warning leakage', !/Fatal error|Warning:|Notice:|Stack trace:/.test(html));
  check('Homepage includes ALUNA', html.includes('ALUNA'));
  check('Homepage is not cacheable', /no-store/.test(first.headers.get('cache-control')));
  const second = await fetch(base + '/?verification=2');
  const identity1 = first.headers.get('x-aluna-request-id');
  const identity2 = second.headers.get('x-aluna-request-id');
  check('Each response has a unique request identifier', identity1 && identity2 && identity1 !== identity2);
  check('Visible homepage identity matches its response header', html.includes(first.headers.get('x-aluna-node')));
  const health = await fetch(base + '/?rest_route=/aluna/v1/health');
  check('Health endpoint checks database', health.status === 200 && (await health.json()).status === 'ok');
  const node = await fetch(base + '/?rest_route=/aluna/v1/node');
  const nodeBody = await node.json();
  check('Node endpoint provides identity without hostname', node.status === 200 && nodeBody.request_id && !('hostname' in nodeBody));
  check('Node endpoint is not cacheable', /no-store/.test(node.headers.get('cache-control')));
  const denied = await fetch(base + '/?rest_route=/wp/v2/aluna_product', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ title: 'UNAUTHORIZED', status: 'publish' }) });
  check('Anonymous catalog writes denied', [401, 403].includes(denied.status));
  const catalogResponse = await fetch(base + '/?rest_route=/wp/v2/aluna_product&per_page=100');
  const catalog = await catalogResponse.json();
  check('Public catalog returns eight sample products', catalogResponse.status === 200 && catalog.length === 8);
  for (const product of catalog) {
    const detail = await fetch(product.link);
    const detailHtml = await detail.text();
    check(`Product page renders: ${product.slug}`, detail.status === 200 && detailHtml.includes(product.title.rendered) && !/Fatal error|Warning:|Notice:/.test(detailHtml));
  }
  const missing = await fetch(base + '/not-a-real-aluna-page-404');
  check('Unknown paths return HTTP 404', missing.status === 404);
  const urls = [...new Set([...html.matchAll(/(?:src|href)=(["'])(.*?)\1/g)].map(m => m[2]).filter(u => u.includes('/themes/aluna/') && !u.includes('#')))];
  for (const url of urls) {
    const response = await fetch(url);
    check(`Asset responds: ${new URL(url).pathname}`, response.status === 200);
  }
  await fs.writeFile(path.join(output, 'results.json'), JSON.stringify({ testedAt: new Date().toISOString(), runtime: { wordpress: '7.1.4', php: '8.3', database: 'SQLite (local verification only)' }, passed: results.length, checks: results }, null, 2) + '\n');
  console.log(`All ${results.length} checks passed.`);
} finally {
  await server[Symbol.asyncDispose]();
}
