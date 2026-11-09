import csv
import json

# Nombre de tus archivos de entrada y salida
archivo_csv = 'productos.csv'
archivo_json = 'productos.json'

lista_productos = []

# Leer el archivo CSV con codificación UTF-8
with open(archivo_csv, mode='r', encoding='utf-8-sig') as csv_file:
    # Usar DictReader para convertir cada fila en un diccionario automáticamente
    csv_reader = csv.DictReader(csv_file)
    
    for fila in csv_reader:
        # Opcional: Limpiar espacios en blanco de las claves y valores
        fila_limpia = {k.strip(): v.strip() if isinstance(v, str) else v for k, v in fila.items()}
        lista_productos.append(fila_limpia)

# Guardar la lista formateada en un archivo JSON
with open(archivo_json, mode='w', encoding='utf-8') as json_file:
    json.dump(lista_productos, json_file, ensure_ascii=False, indent=2)

print(f"¡Conversión exitosa! Se procesaron {len(lista_productos)} productos y se guardaron en {archivo_json}.")
