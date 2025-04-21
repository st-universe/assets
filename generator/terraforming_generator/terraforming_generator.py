import os
import pandas as pd
from PIL import Image, ImageDraw
import numpy as np

# Verzeichnisse
# Nicht zur automatisierung auf Servern bestimmt
# Ausgangsdatei mit fehlenden Terraformings 'fehlende_ids.xlsx' wird benötigt.

base_path = os.path.dirname(__file__)
excel_path = os.path.join(base_path, 'fehlende_ids.xlsx')
layer_path = os.path.join(base_path, 'layer.png')
source_image_dir = r'PATH' #assets/generated/fields
output_base_dir = r'PATH' #assets/terraforming

# Hilfsfunktion für geschwungene Maske
def create_half_mask(width, height, variation=3):
    mask = Image.new('L', (width, height), 0)
    draw = ImageDraw.Draw(mask)
    curve = []
    center = width // 2
    np.random.seed(0)  # Für konsistente Ergebnisse
    for y in range(height):
        offset = int(np.sin(y / 4.0) * variation)
        x = center + offset
        curve.append((x, y))
    for y in range(height):
        draw.line([(0, y), (curve[y][0], y)], fill=255)
    return mask

# Einlesen der Excel-Datei
df = pd.read_excel(excel_path)
ids = df.iloc[:, 0].astype(str)
von_ids = df.iloc[:, 2].astype(str)
zu_ids = df.iloc[:, 4].astype(str)

# Layer-Bild laden
layer = Image.open(layer_path).convert("RGBA").resize((40, 40))
mask = create_half_mask(40, 40)

# Verarbeitung jeder ID
for idx, id_ in enumerate(ids):
    von_id = von_ids[idx]
    zu_id = zu_ids[idx]
    
    out_dir = os.path.join(output_base_dir, id_)
    os.makedirs(out_dir, exist_ok=True)

    for prefix, label in [('t', 't.png'), ('n', 'n.png')]:
        # Pfade zu Quellbildern
        zu_path = os.path.join(source_image_dir, f"{prefix}{zu_id}.png")
        von_path = os.path.join(source_image_dir, f"{prefix}{von_id}.png")

        # Bilder laden und vorbereiten
        try:
            img_zu = Image.open(zu_path).convert("RGBA").resize((40, 40))
            img_von = Image.open(von_path).convert("RGBA").resize((40, 40))
        except FileNotFoundError:
            print(f"Fehlende Quelle: {von_path} oder {zu_path}")
            continue

        # Maske anwenden (linke Hälfte von von_id-Bild)
        img_von_masked = Image.composite(img_von, Image.new("RGBA", img_von.size, (0, 0, 0, 0)), mask)

        # Layer aufbauen
        final_img = Image.alpha_composite(img_zu, img_von_masked)
        final_img = Image.alpha_composite(final_img, layer)

        # Speichern
        final_path = os.path.join(out_dir, label)
        final_img.save(final_path, format='PNG')

print("Alle Grafiken erfolgreich erzeugt!")
