import json
import sys
from pathlib import Path

try:
    import cv2
    import numpy as np
except ImportError:
    cv2 = None
    np = None


def remove_background(image_bgr):
    height, width = image_bgr.shape[:2]
    resized = image_bgr.copy()

    hsv = cv2.cvtColor(resized, cv2.COLOR_BGR2HSV)
    value = hsv[:, :, 2]
    saturation = hsv[:, :, 1]

    # Build a foreground mask by removing bright low-saturation studio backgrounds.
    foreground_mask = np.where((value < 245) | (saturation > 22), 255, 0).astype('uint8')

    kernel = np.ones((3, 3), np.uint8)
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

    image = cv2.imread(str(input_path), cv2.IMREAD_COLOR)
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
