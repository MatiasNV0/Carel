#!/usr/bin/env python3
"""Genera data/productos.js (liviano) a partir del export de WooCommerce.

Uso:  python3 tools/build_data.py ruta/a/productos.json
Cada vez que se actualice el catálogo, se vuelve a correr este script.
"""
import json, re, sys, os
from collections import Counter

src = sys.argv[1] if len(sys.argv) > 1 else 'productos.json'
out = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'data', 'productos.js')

rows = json.load(open(src, encoding='utf-8'))
rows = [r for r in rows if r.get('Publicado') == '1']   # excluye borradores ('-1')

FINISHES = {'ZINC', 'DICRO', 'NAT', 'FOSF', 'INOX', 'NEGRO', 'GALV', 'CROMADO', 'PAVON', 'PAVONADO', 'ZINCADO'}
GRADE = re.compile(r'\((8\.8|10\.9|12\.9)\)|\bG(2|5|8|10)\b|\b(1010|A2|A4)\b')

def parse(name):
    toks = name.split()
    acabado = toks[-1] if toks and toks[-1].upper() in FINISHES else ''
    grado = ''
    m = GRADE.search(name)
    if m:
        grado = m.group(1) or ('G' + m.group(2) if m.group(2) else m.group(3))
    return acabado, grado

def natkey(s):
    return [int(t) if t.isdigit() else t.lower() for t in re.split(r'(\d+)', s)]

imgs, img_idx = [], {}
cats, items = [], []
for r in rows:
    cat, _, sub = [p.strip() for p in r['Categorías'].partition('>')]
    url = r['Imágenes'].split(',')[0].strip()
    if url not in img_idx:
        img_idx[url] = len(imgs); imgs.append(url)
    acabado, grado = parse(r['Nombre'])
    items.append({
        'id': int(r['ID']), 's': r['SKU'], 'n': r['Nombre'].strip(),
        'c': cat, 'u': sub, 'i': img_idx[url], 'a': acabado, 'g': grado,
        'd': (r['Descripción'] or r['Descripción corta'] or '').strip(),
    })
items.sort(key=lambda x: (x['c'], x['u'], natkey(x['n'])))
for x in items:
    if not x['d']: del x['d']
    if not x['g']: del x['g']
    if not x['a']: del x['a']
    if not x['s']: del x['s']

with open(out, 'w', encoding='utf-8') as f:
    f.write('// Generado por tools/build_data.py — no editar a mano\n')
    f.write('window.CAREL_IMGS = ' + json.dumps(imgs, ensure_ascii=False) + ';\n')
    f.write('window.CAREL_PRODUCTOS = ' + json.dumps(items, ensure_ascii=False, separators=(',', ':')) + ';\n')

print(len(items), 'productos;', len(imgs), 'imágenes;', os.path.getsize(out) // 1024, 'KB')
print(Counter(x.get('a', '—') for x in items).most_common())
print(Counter(x.get('g', '—') for x in items).most_common())
