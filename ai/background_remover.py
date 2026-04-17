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


def load_image(path):
    image = cv2.imread(str(path), cv2.IMREAD_COLOR)
    if image is not None:
        return image

    if Image is None or ImageOps is None or np is None:
        return None

    try:
        with Image.open(path) as pil_image:
            pil_image = ImageOps.exif_transpose(pil_image).convert("RGB")
            rgb = np.array(pil_image)
            return cv2.cvtColor(rgb, cv2.COLOR_RGB2BGR)
    except Exception:
        return None


def build_border_background_mask(image_bgr):
    height, width = image_bgr.shape[:2]
    border = max(6, min(height, width) // 18)

    lab = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2LAB).astype(np.float32)
    hsv = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2HSV).astype(np.float32)

    border_pixels = np.concatenate([
        lab[:border, :, :].reshape(-1, 3),
        lab[-border:, :, :].reshape(-1, 3),
        lab[:, :border, :].reshape(-1, 3),
        lab[:, -border:, :].reshape(-1, 3),
    ], axis=0)
    border_hsv = np.concatenate([
        hsv[:border, :, :].reshape(-1, 3),
        hsv[-border:, :, :].reshape(-1, 3),
        hsv[:, :border, :].reshape(-1, 3),
        hsv[:, -border:, :].reshape(-1, 3),
    ], axis=0)

    border_lab_median = np.median(border_pixels, axis=0)
    border_value = float(np.median(border_hsv[:, 2]))
    border_saturation = float(np.median(border_hsv[:, 1]))

    color_distance = np.linalg.norm(lab - border_lab_median, axis=2)
    border_distance = np.linalg.norm(border_pixels - border_lab_median, axis=1)
    distance_threshold = max(18.0, float(np.percentile(border_distance, 92)) + 10.0)

    value = hsv[:, :, 2]
    saturation = hsv[:, :, 1]

    background_like = (
        (color_distance <= distance_threshold)
        | (
            (color_distance <= distance_threshold + 8.0)
            & (value >= border_value - 18.0)
            & (saturation <= max(36.0, border_saturation + 20.0))
        )
    )

    flood_source = (background_like.astype('uint8') * 255)
    flooded = flood_source.copy()
    flood_mask = np.zeros((height + 2, width + 2), dtype='uint8')
    seed_points = [
        (0, 0),
        (width - 1, 0),
        (0, height - 1),
        (width - 1, height - 1),
        (width // 2, 0),
        (width // 2, height - 1),
        (0, height // 2),
        (width - 1, height // 2),
    ]

    for x, y in seed_points:
        if flooded[y, x] == 255:
            cv2.floodFill(flooded, flood_mask, (x, y), 64)

    return flooded == 64


def remove_background(image_bgr):
    height, width = image_bgr.shape[:2]
    resized = image_bgr.copy()

    hsv = cv2.cvtColor(resized, cv2.COLOR_BGR2HSV)
    value = hsv[:, :, 2]
    saturation = hsv[:, :, 1]

    border_background = build_border_background_mask(resized)
    bright_background = (value > 242) & (saturation < 28)

    # Combine border-connected background detection with bright-studio filtering.
    foreground_mask = np.where(~(border_background | bright_background), 255, 0).astype('uint8')

    kernel = np.ones((3, 3), np.uint8)
    foreground_mask = cv2.morphologyEx(foreground_mask, cv2.MORPH_OPEN, kernel, iterations=1)
    foreground_mask = cv2.morphologyEx(foreground_mask, cv2.MORPH_CLOSE, kernel, iterations=2)
    foreground_mask = cv2.GaussianBlur(foreground_mask, (5, 5), 0)

    if np.count_nonzero(foreground_mask > 20) < max(500, (height * width) // 150):
        foreground_mask = np.where((value < 245) | (saturation > 22), 255, 0).astype('uint8')
        foreground_mask = cv2.morphologyEx(foreground_mask, cv2.MORPH_OPEN, kernel, iterations=1)
        foreground_mask = cv2.morphologyEx(foreground_mask, cv2.MORPH_CLOSE, kernel, iterations=2)
        foreground_mask = cv2.GaussianBlur(foreground_mask, (5, 5), 0)

    # Keep the largest meaningful contour so stray shadows do not dominate.
    contours, _ = cv2.findContours((foreground_mask > 20).astype('uint8'), cv2.RETR_EXTERNAL, cv2.CHAIN_APPROX_SIMPLE)
    if contours:
        largest = max(contours, key=cv2.contourArea)
        clean_mask = np.zeros((height, width), dtype='uint8')
        cv2.drawContours(clean_mask, [largest], -1, 255, thickness=cv2.FILLED)
        clean_mask = cv2.GaussianBlur(clean_mask, (5, 5), 0)
    else:
        clean_mask = foreground_mask

    bgra = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2BGRA)
    bgra[:, :, 3] = clean_mask
    return bgra, clean_mask



def crop_to_subject(image_bgra, alpha_mask, padding=18):
    ys, xs = np.where(alpha_mask > 20)
    if len(xs) == 0 or len(ys) == 0:
        return image_bgra

    x1 = max(int(xs.min()) - padding, 0)
    y1 = max(int(ys.min()) - padding, 0)
    x2 = min(int(xs.max()) + padding, image_bgra.shape[1])
    y2 = min(int(ys.max()) + padding, image_bgra.shape[0])
    return image_bgra[y1:y2, x1:x2]



def main():
    if len(sys.argv) < 3:
        print(json.dumps({"success": False, "message": "Usage: background_remover.py <input> <output>"}))
        return

    if cv2 is None or np is None:
        print(json.dumps({"success": False, "message": "OpenCV dependencies are not installed."}))
        return

    input_path = Path(sys.argv[1])
    output_path = Path(sys.argv[2])

    image = load_image(input_path)
    if image is None:
        print(json.dumps({"success": False, "message": "Image could not be read."}))
        return

    cutout, alpha_mask = remove_background(image)
    cropped = crop_to_subject(cutout, alpha_mask)

    output_path.parent.mkdir(parents=True, exist_ok=True)
    ok = cv2.imwrite(str(output_path), cropped)
    if not ok:
        print(json.dumps({"success": False, "message": "Failed to write cutout image."}))
        return

    print(json.dumps({
        "success": True,
        "output_path": str(output_path),
        "width": int(cropped.shape[1]),
        "height": int(cropped.shape[0])
    }))


if __name__ == '__main__':
    main()
