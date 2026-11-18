'use strict';

const { test } = require('node:test');
const assert = require('node:assert/strict');
const logic = require('../wp-content/themes/aluna/assets/js/logic.js');

// A representative catalog intentionally includes universal and skin-specific items.
// The assertions test observable selection rules, not the scoring implementation.
const products = [
  { id: 1, name: 'Limpiador esencial', category: 'limpieza', categoryLabel: 'Limpieza', description: 'Limpieza suave para todos los días.', ingredients: 'Avena · pantenol', price: 68000, skin: ['todas'], concern: 'equilibrio', featured: true },
  { id: 2, name: 'Sérum Luz', category: 'tratamientos', categoryLabel: 'Tratamientos', description: 'Textura ligera para tu rutina de luminosidad.', ingredients: 'Vitamina C · té verde', price: 98000, skin: ['seca', 'mixta', 'grasa'], concern: 'luminosidad', featured: true },
  { id: 3, name: 'Crema Calma', category: 'hidratacion', categoryLabel: 'Hidratación', description: 'Una sensación de confort.', ingredients: 'Escualano · ceramidas', price: 86000, skin: ['seca', 'sensible'], concern: 'hidratacion', featured: true },
  { id: 4, name: 'Fluido Solar', category: 'proteccion', categoryLabel: 'Protección solar', description: 'El último paso de la rutina de día.', ingredients: 'Vitamina E', price: 92000, skin: ['todas'], concern: 'proteccion', featured: true },
  { id: 5, name: 'Gel Equilibrio', category: 'hidratacion', categoryLabel: 'Hidratación', description: 'Acabado fresco y ligero.', ingredients: 'Ácido hialurónico · niacinamida', price: 78000, skin: ['mixta', 'grasa'], concern: 'equilibrio', featured: false },
  { id: 6, name: 'Sérum Agua', category: 'tratamientos', categoryLabel: 'Tratamientos', description: 'Un gesto de hidratación.', ingredients: 'Ácido hialurónico · pantenol', price: 89000, skin: ['todas'], concern: 'hidratacion', featured: false },
  { id: 7, name: 'Bálsamo Suave', category: 'limpieza', categoryLabel: 'Limpieza', description: 'Limpieza de la noche.', ingredients: 'Escualano · vitamina E', price: 74000, skin: ['seca', 'sensible', 'mixta'], concern: 'hidratacion', featured: false },
  { id: 8, name: 'Sérum Balance', category: 'tratamientos', categoryLabel: 'Tratamientos', description: 'Una rutina ligera de pocos pasos.', ingredients: 'Niacinamida · glicerina', price: 94000, skin: ['mixta', 'grasa'], concern: 'equilibrio', featured: false },
];
for (const p of products) { Object.freeze(p.skin); Object.freeze(p); }
Object.freeze(products);
const ids = result => result.map(p => p.id);
const filter = changes => logic.filter(products, { query: '', category: 'all', skin: 'all', sort: 'featured', ...changes });
const compatible = (p, skin) => p.skin.includes('todas') || p.skin.includes(skin);

test('search is insensitive to accents, Unicode decomposition, case, and outer spaces', () => {
  assert.equal(logic.normalize('  HIDRATACIO\u0301N  '), 'hidratacion');
  assert.deepEqual(ids(filter({ query: '  SERUM  ' })), [2, 6, 8]);
  assert.deepEqual(ids(filter({ query: 'SÉRUM' })), [2, 6, 8]);
  assert.deepEqual(ids(filter({ query: 'seru\u0301m luz' })), [2]);
  assert.deepEqual(ids(filter({ query: 'te verde' })), [2]);
});

test('search includes descriptions, ingredients, and category labels', () => {
  assert.deepEqual(ids(filter({ query: 'confort' })), [3]);
  assert.deepEqual(ids(filter({ query: 'niacinamida' })), [5, 8]);
  assert.deepEqual(ids(filter({ query: 'proteccion solar' })), [4]);
  assert.deepEqual(ids(filter({ query: '<script>alert(1)</script>' })), []);
});

test('category, skin, and text filters intersect instead of overwriting each other', () => {
  assert.deepEqual(ids(filter({ query: 'serum', category: 'tratamientos', skin: 'sensible' })), [6]);
  assert.deepEqual(ids(filter({ query: 'niacinamida', category: 'tratamientos', skin: 'grasa' })), [8]);
  assert.deepEqual(ids(filter({ query: 'niacinamida', category: 'limpieza', skin: 'grasa' })), []);
  assert.deepEqual(ids(filter({ category: 'hidratacion', skin: 'grasa' })), [5]);
  assert.deepEqual(ids(filter({ category: 'proteccion', skin: 'sensible' })), [4]);
});

for (const skin of ['seca', 'mixta', 'grasa', 'sensible']) {
  for (const category of ['all', 'limpieza', 'hidratacion', 'tratamientos', 'proteccion']) {
    test(`catalog filter includes only compatible products: ${skin}, ${category}`, () => {
      const result = filter({ skin, category });
      assert.ok(result.every(p => compatible(p, skin)));
      assert.ok(result.every(p => category === 'all' || p.category === category));
      const expected = products.filter(p => compatible(p, skin) && (category === 'all' || p.category === category));
      assert.deepEqual(new Set(ids(result)), new Set(ids(expected)));
    });
  }
}

test('sorting produces numeric prices and Spanish alphabetical ordering', () => {
  assert.deepEqual(ids(filter({ sort: 'price-asc' })), [1, 7, 5, 3, 6, 4, 8, 2]);
  assert.deepEqual(ids(filter({ sort: 'price-desc' })), [2, 8, 4, 6, 3, 5, 7, 1]);
  assert.deepEqual(ids(filter({ sort: 'name' })), [7, 3, 4, 5, 1, 6, 8, 2]);
  assert.deepEqual(ids(filter({ sort: 'featured' })), [1, 2, 3, 4, 5, 6, 7, 8]);
});

test('filters and sorting never mutate the CMS catalog', () => {
  const snapshot = JSON.stringify(products);
  for (const sort of ['featured', 'price-asc', 'price-desc', 'name']) filter({ sort });
  for (const skin of ['seca', 'mixta', 'grasa', 'sensible']) logic.routine(products, skin, 'hidratacion');
  assert.equal(JSON.stringify(products), snapshot);
  assert.notEqual(filter({}), products);
});

test('empty catalogs and zero-result searches are safe', () => {
  const state = { query: '', category: 'all', skin: 'all', sort: 'price-asc' };
  assert.deepEqual(logic.filter([], state), []);
  assert.deepEqual(logic.routine([], 'seca', 'hidratacion'), []);
  assert.deepEqual(logic.favoriteIds('[1,2]', []), []);
  assert.deepEqual(filter({ query: 'producto que no existe' }), []);
});

test('favorites accept existing numeric/string IDs, deduplicate and reject stale IDs', () => {
  assert.deepEqual(logic.favoriteIds('[1,"1",2,999,-1,0,"8"]', products), ['1', '2', '8']);
  assert.deepEqual(logic.favoriteIds('[8,1,8,2,1]', products), ['8', '1', '2']);
  assert.deepEqual(logic.favoriteIds('[1,2]', products.filter(p => p.id !== 1)), ['2']);
});

test('corrupt or missing storage cannot crash or inject favorite entries', () => {
  for (const raw of [null, undefined, '', 'not json', 'null', '{}', '42', '"1"', '{"__proto__":{"x":1}}']) {
    assert.deepEqual(logic.favoriteIds(raw, products), []);
  }
  assert.deepEqual(logic.favoriteIds('[[1],"2",{},null,true,false,1.2]', products), ['2']);
  assert.deepEqual(logic.favoriteIds('[1,{"toString":null},2]', products), ['1', '2']);
});

test('favorites bound valid persisted selections to 100 entries', () => {
  const large = Array.from({ length: 120 }, (_, index) => ({ id: index + 1 }));
  const result = logic.favoriteIds(JSON.stringify(large.map(p => p.id)), large);
  assert.equal(result.length, 100);
  assert.equal(result[0], '1');
  assert.equal(result.at(-1), '100');
});

for (const skin of ['seca', 'mixta', 'grasa', 'sensible']) {
  for (const concern of ['hidratacion', 'equilibrio', 'luminosidad', 'simple']) {
    test(`routine is compatible, ordered, and goal-specific: ${skin}, ${concern}`, () => {
      const result = logic.routine(products, skin, concern);
      assert.ok(result.every(p => compatible(p, skin)), 'No incompatible skin product');
      assert.equal(new Set(ids(result)).size, result.length, 'No duplicate products');
      const categories = result.map(p => p.category);
      assert.equal(categories[0], 'limpieza');
      assert.equal(categories.at(-1), 'proteccion');
      assert.equal(categories.filter(c => c === 'hidratacion').length, 1);
      assert.equal(new Set(categories).size, categories.length);
      const treatment = result.find(p => p.category === 'tratamientos');
      if (concern === 'simple') {
        assert.equal(treatment, undefined, 'Simple routine omits optional treatments');
        assert.equal(result.length, 3);
      } else {
        const matchingExists = products.some(p => p.category === 'tratamientos' && compatible(p, skin) && p.concern === concern);
        assert.equal(Boolean(treatment), matchingExists, 'No irrelevant treatment substitutes for a missing match');
        if (treatment) assert.equal(treatment.concern, concern);
      }
      const categoryOrder = ['limpieza', 'tratamientos', 'hidratacion', 'proteccion'];
      const positions = categories.map(c => categoryOrder.indexOf(c));
      assert.deepEqual(positions, [...positions].sort((a, b) => a - b));
    });
  }
}

test('routine keeps useful compatible steps when a CMS category is unpublished', () => {
  const withoutProtection = products.filter(p => p.category !== 'proteccion');
  const result = logic.routine(withoutProtection, 'sensible', 'simple');
  assert.deepEqual(result.map(p => p.category), ['limpieza', 'hidratacion']);
  assert.ok(result.every(p => compatible(p, 'sensible')));
});
