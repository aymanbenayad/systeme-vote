from PIL import Image

# Ouvre l'image
image = Image.open('G:/My Drive/Ensias/1A/S2/PFA/systeme-vote/designs/barre.png')

# Dimensions de l'image originale
width, height = image.size

# Largeur de chaque segment
segment_width = 121

# Découpe l'image en cinq parties
for i in range(5):
    left = i * segment_width
    top = 0
    right = left + segment_width
    bottom = height
    
    # Crée la nouvelle image découpée
    cropped_image = image.crop((left, top, right, bottom))
    
    # Sauvegarde la partie découpée
    cropped_image.save(f'G:/My Drive/Ensias/1A/S2/PFA/systeme-vote/designs/niveau{i+1}.png')

print("L'image a été découpée en 5 parties.")
