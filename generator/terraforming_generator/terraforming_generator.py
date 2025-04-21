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

# Neue Maske mit weichem Übergang ±5 Pixel um zufällige Linie mit starker Abweichung
def create_soft_mask(width, height, blend_width=10, offset_range=4):
    mask = Image.new('L', (width, height), 0)
    pixels = mask.load()
    center = width // 2
    np.random.seed(42)  # für konstante Ergebnisse

    for y in range(height):
        random_offset = np.random.randint(-offset_range, offset_range + 1)
        curve_x = center + random_offset

        for x in range(width):
            distance = x - curve_x
            if distance <= -blend_width // 2:
                alpha = 255  # komplett sichtbar
            elif -blend_width // 2 < distance < blend_width // 2:
                rel = 0.5 - (distance / blend_width)
                alpha = int(max(0, min(1, rel)) * 255)
            else:
                alpha = 0  # komplett transparent
            pixels[x, y] = alpha

    return mask

# Excel einlesen
df = pd.read_excel(excel_path)
ids = df.iloc[:, 0].astype(str)
von_ids = df.iloc[:, 2].astype(str)
zu_ids = df.iloc[:, 4].astype(str)

# Layer-Bild laden
layer = Image.open(layer_path).convert("RGBA").resize((40, 40))
mask = create_soft_mask(40, 40, blend_width=10, offset_range=4)

# Verarbeitung jeder ID
for idx, id_ in enumerate(ids):
    von_id = von_ids[idx]
    zu_id = zu_ids[idx]
    
    out_dir = os.path.join(output_base_dir, id_)
    os.makedirs(out_dir, exist_ok=True)

    for prefix, label in [('t', 't.png'), ('n', 'n.png')]:
        zu_path = os.path.join(source_image_dir, f"{prefix}{zu_id}.png")
        von_path = os.path.join(source_image_dir, f"{prefix}{von_id}.png")

        try:
            img_zu = Image.open(zu_path).convert("RGBA").resize((40, 40))
            img_von = Image.open(von_path).convert("RGBA").resize((40, 40))
        except FileNotFoundError:
            print(f"Fehlende Quelle: {von_path} oder {zu_path}")
            continue

        # Maske anwenden
        img_von_masked = Image.composite(img_von, Image.new("RGBA", img_von.size, (0, 0, 0, 0)), mask)

        # Bilder kombinieren
        final_img = Image.alpha_composite(img_zu, img_von_masked)
        final_img = Image.alpha_composite(final_img, layer)

        # Speichern
        final_path = os.path.join(out_dir, label)
        final_img.save(final_path, format='PNG')

print("Fertig – der Übergang ist jetzt noch weicher und dynamischer.")
