from PIL import Image
import pytesseract

# Chemin de ton image à tester
image_path = "/home/johny/Images/baignets.jpg"

# Ouvre l'image
image = Image.open(image_path)

# OCR en français (lang='fra')
texte = pytesseract.image_to_string(image, lang='fra')

print("Texte OCR extrait :")
print("-------------------")
print(texte)
