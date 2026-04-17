# Closet Couture

Closet Couture is a luxury fashion-tech wardrobe system built with PHP, MySQL, JavaScript, Bootstrap 5, and Python. It helps users upload wardrobe items, process clothing images, detect colors, save outfit combinations, and generate outfit recommendations using machine learning and optional generative AI.

## Core Feature

The main working module is the recommendation engine.

- Users add wardrobe items with images and metadata.
- The system can remove image backgrounds and detect dominant colors.
- Users request outfit recommendations by occasion, season, style, and color preference.
- The system generates recommendations using:
  - a local machine learning recommender
  - an OpenAI generative AI layer when an API key is configured
  - a fallback text-direction mode when a full outfit cannot be formed

## Tech Stack

- Frontend: HTML, CSS, JavaScript, Bootstrap 5
- Backend: PHP
- Database: MySQL
- Python Services: scikit-learn, OpenCV utilities
- AI / ML:
  - content-based machine learning recommender
  - optional OpenAI generative recommendations
  - computer vision utilities for background removal and color detection

## Why ML + AI + Computer Vision

This project uses multiple emerging technologies in a way that is central to the system:

- Machine Learning:
  The local recommender in `ai/recommender.py` fits a content-based recommendation model on wardrobe attributes such as category, occasion, season, color, favorite status, and wear count. This allows the system to rank the most relevant clothing combinations from the saved wardrobe.

- Generative AI:
  When `OPENAI_API_KEY` is configured, `actions/get_recommendation.php` sends wardrobe context and user preferences to an OpenAI model. This produces more natural and adaptive recommendation text while still grounding the output in real saved wardrobe data.

- Computer Vision:
  `ai/background_remover.py` and `ai/color_detector.py` process uploaded clothing images. These scripts help isolate clothing items visually and extract dominant colors automatically, improving wardrobe presentation and recommendation quality.

## Project Structure

- `index.php`
  Dashboard
- `pages/wardrobe.php`
  Wardrobe management and saved fits
- `pages/recommendation.php`
  Recommendation UI
- `actions/get_recommendation.php`
  Recommendation controller
- `ai/recommender.py`
  Local ML recommender
- `ai/background_remover.py`
  Background removal utility
- `ai/color_detector.py`
  Color detection utility

## Setup Instructions

### 1. Requirements

- XAMPP or a PHP + MySQL environment
- Python 3.12+ or compatible
- pip packages for the Python scripts
- optional OpenAI API key for generative recommendations

### 2. Database Setup

1. Create a MySQL database named `wardrobe_db`.
2. Import `sql/wardrobe_db.sql`.
3. Update `config/db.php` if your local database credentials are different.

If you already have an older local database, add the new `updated_at` columns to `clothes` and `outfits`, and add the unique `(outfit_id, clothing_id)` constraint in `outfit_items` so recent edits and duplicate-prevention work correctly.

### 3. Python Dependencies

Install the required Python packages:

```bash
pip install scikit-learn numpy pandas opencv-python pillow
```

If you want stronger background removal results and your image utility depends on extra packages, install the package requirements needed by your local Python image-processing setup as well.

### 4. Environment Configuration

Copy `.env.example` to `.env` and update values if needed:

```env
OPENAI_API_KEY=
OPENAI_MODEL=gpt-5.4-mini
PYTHON_PATH=python
```

Notes:

- `OPENAI_API_KEY`
  Optional. If present, the system will try generative AI recommendations first.
- `OPENAI_MODEL`
  Optional. Defaults to `gpt-5.4-mini`.
- `PYTHON_PATH`
  Optional. Set this if `python` is not available on your PATH.

### 5. Run the Project

1. Place the project inside your web root, for example `c:\xampp\htdocs\ClosetCouture`.
2. Start Apache and MySQL.
3. Open:

```text
http://localhost/ClosetCouture/
```

## Recommendation Engine Behavior

The recommendation system is designed to remain stable for demo day:

- If OpenAI is configured and reachable:
  the system uses generative AI recommendations.
- If OpenAI is not configured or fails:
  the system falls back to the local Python ML recommender.
- If the wardrobe does not have enough exact pieces:
  the system falls back to text-based fit direction instead of failing completely.

This means the recommendation page can still be demonstrated even without external AI access.

## Demo Flow

Suggested live demo order:

1. Open the dashboard.
2. Add an item in the wardrobe.
3. Show image processing and auto-detected color.
4. Save a fit from selected items.
5. Go to Recommendations.
6. Generate an ML recommendation.
7. If OpenAI is configured, show the generative AI path.
8. If the wardrobe is limited, show the text-based fallback recommendation.

## Capstone / Final Project Positioning

Closet Couture aligns with the Emerging Tech Track because the core feature combines:

- machine learning recommendation
- optional generative AI recommendation
- computer vision-based wardrobe image processing
- a full-stack web application workflow

## Notes

- `temp_reco_test.json` is only a temporary test file and is not part of the application logic.
- If recommendation output looks repetitive, improve either:
  - wardrobe data variety
  - OpenAI prompt quality
  - ML diversity logic in `ai/recommender.py`
