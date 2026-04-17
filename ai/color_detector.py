import json
import sys
from pathlib import Path

try:
    import cv2
    import numpy as np
except ImportError:
    cv2 = None
    np = None

try:
    from PIL import Image, ImageOps
except ImportError:
    Image = None
    ImageOps = None


COLOR_MAP = {
    "Red": (185, 65, 60),
    "Blue": (70, 105, 185),
    "Navy": (45, 60, 110),
    "Green": (85, 145, 90),
    "Yellow": (220, 190, 80),
    "Brown": (130, 88, 62),
    "Beige": (214, 194, 160),
    "Pink": (220, 155, 175),
}


def load_image(path):
    image = cv2.imread(str(path), cv2.IMREAD_UNCHANGED)
    if image is not None:
        return image

    if Image is None or ImageOps is None or np is None:
        return None

    try:
        with Image.open(path) as pil_image:
            pil_image = ImageOps.exif_transpose(pil_image)
            if pil_image.mode in ('RGBA', 'LA'):
                rgba = np.array(pil_image.convert('RGBA'))
                return cv2.cvtColor(rgba, cv2.COLOR_RGBA2BGRA)

            rgb = np.array(pil_image.convert('RGB'))
            return cv2.cvtColor(rgb, cv2.COLOR_RGB2BGR)
    except Exception:
        return None


def classify_color(rgb):
    r, g, b = [int(channel) for channel in rgb]
    max_c = max(r, g, b)
    min_c = min(r, g, b)
    spread = max_c - min_c
    avg = (r + g + b) / 3

    if avg >= 225 and spread <= 18:
        return "White"
    if avg <= 45 and spread <= 20:
        return "Black"
    if spread <= 22:
        return "Gray"
    if b > r + 18 and b > g + 8:
        return "Blue" if avg > 85 else "Navy"
    if r > 150 and g > 135 and b < 120:
        return "Yellow"
    if r > 170 and g > 145 and b > 135:
        return "Beige"
    if r > 150 and b > 120 and g > 100:
        return "Pink"
    if r > g + 20 and r > b + 20:
        if r < 150 and g < 110:
            return "Brown"
        return "Red"
    if g > r + 10 and g > b + 10:
        return "Green"

    color_vector = np.array(rgb, dtype=np.float32)
    closest_name = "Gray"
    closest_distance = float("inf")

    for name, reference in COLOR_MAP.items():
        distance = np.linalg.norm(color_vector - np.array(reference, dtype=np.float32))
        if distance < closest_distance:
            closest_distance = distance
            closest_name = name

    return closest_name



def extract_subject_pixels(image_bgr, alpha_channel=None):
    rgb = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2RGB)
    hsv = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2HSV)

    if alpha_channel is not None:
        alpha_mask = alpha_channel > 15
    else:
        alpha_mask = np.ones(hsv.shape[:2], dtype=bool)

    value = hsv[:, :, 2]
    saturation = hsv[:, :, 1]

    non_white_mask = ~((value > 235) & (saturation < 28))
    non_black_mask = value > 25

    mask = alpha_mask & non_white_mask & non_black_mask
    pixels = rgb[mask]

    if pixels.shape[0] < 400:
        relaxed_mask = alpha_mask & (value < 245)
        pixels = rgb[relaxed_mask]

    if pixels.shape[0] < 100:
        pixels = rgb.reshape((-1, 3))

    return pixels.astype(np.float32)



def dominant_rgb(pixels):
    if pixels.shape[0] == 0:
        return [240, 240, 240]

    sample_size = min(len(pixels), 8000)
    if len(pixels) > sample_size:
        indices = np.random.choice(len(pixels), sample_size, replace=False)
        pixels = pixels[indices]

    cluster_count = 3 if len(pixels) >= 3 else 1
    criteria = (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 25, 0.8)
    _, labels, palette = cv2.kmeans(
        pixels,
        cluster_count,
        None,
        criteria,
        8,
        cv2.KMEANS_PP_CENTERS,
    )

    labels = labels.flatten()
    counts = np.bincount(labels)
    dominant = palette[counts.argmax()].astype(int).tolist()
    return dominant



def main():
    if len(sys.argv) < 2:
        print(json.dumps({"dominant_color": None, "message": "Missing image path."}))
        return

    image_path = Path(sys.argv[1])
    if cv2 is None or np is None:
        print(json.dumps({"dominant_color": None, "message": "OpenCV dependencies are not installed."}))
        return

    image = load_image(image_path)
    if image is None:
        print(json.dumps({"dominant_color": None, "message": "Image could not be read."}))
        return

    alpha_channel = None
    if len(image.shape) == 2:
        image = cv2.cvtColor(image, cv2.COLOR_GRAY2BGR)
    elif image.shape[2] == 4:
        alpha_channel = image[:, :, 3]
        image = image[:, :, :3]

    resized = cv2.resize(image, (160, 160), interpolation=cv2.INTER_AREA)
    resized_alpha = None
    if alpha_channel is not None:
        resized_alpha = cv2.resize(alpha_channel, (160, 160), interpolation=cv2.INTER_AREA)

    pixels = extract_subject_pixels(resized, resized_alpha)
    dominant = dominant_rgb(pixels)
    print(json.dumps({
        "dominant_color": classify_color(dominant),
        "rgb": dominant,
        "pixel_count": int(len(pixels))
    }))


if __name__ == "__main__":
    main()
