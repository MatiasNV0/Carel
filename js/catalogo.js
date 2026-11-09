/* Carel SRL — utilidades compartidas del catálogo.
   Requiere data/productos.js (window.CAREL_PRODUCTOS / CAREL_IMGS). */
(function () {
  const IMGS = window.CAREL_IMGS || [];
  const ALL = (window.CAREL_PRODUCTOS || []).map(p => ({
    id: p.id, sku: p.s || '', nombre: p.n, cat: p.c, sub: p.u || '',
    img: IMGS[p.i] || '', acabado: p.a || '', grado: p.g || '', desc: p.d || ''
  }));
  // Diámetro: M12 / 3/8 / 1,1/4 … (primer valor del nombre que lo indique)
  const DIAM = /(?:^|[\s(])(M\d+(?:[.,]\d+)?|\d+(?:,\d+)?\/\d+)(?=[-\s(]|$)/;
  const diamValue = d => d[0] === 'M' ? parseFloat(d.slice(1).replace(',', '.')) : 100 + d.split(',').reduce((t, x, i, a) =>
    t + (x.includes('/') ? x.split('/')[0] / x.split('/')[1] : +x), 0);
  ALL.forEach(p => { const m = DIAM.exec(p.nombre); p.diam = m ? m[1] : ''; });
  const WA = '541134857763';

  const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g,
    c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const params = () => new URLSearchParams(location.search);
  const url = (page, o) => page + '?' + new URLSearchParams(o).toString();
  const waLink = text => `https://wa.me/${WA}?text=${encodeURIComponent(text)}`;
  const articulos = n => `${n} ${n === 1 ? 'artículo' : 'artículos'}`;

  function group(items, key) {
    const m = new Map();
    items.forEach(p => {
      const k = p[key];
      if (!k) return;
      if (!m.has(k)) m.set(k, []);
      m.get(k).push(p);
    });
    return [...m.entries()]
      .map(([name, list]) => ({ name, count: list.length, img: list[0].img, items: list }))
      .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name, 'es'));
  }

  const categories = () => group(ALL, 'cat').map(c => ({
    ...c,
    subs: group(c.items, 'sub'),
    // sin subcategorías → se entra directo al listado
    hasSubs: c.items.some(p => p.sub)
  }));

  // El buscador del header lleva al listado con el término buscado
  document.querySelectorAll('#header-search').forEach(el =>
    el.addEventListener('keydown', e => {
      if (e.key === 'Enter' && el.value.trim()) location.href = url('productos.html', { q: el.value.trim() });
    }));

  window.Carel = { diamValue, ALL, WA, esc, params, url, waLink, articulos, group, categories };
})();
