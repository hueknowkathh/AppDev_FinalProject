import json
import sys
from pathlib import Path

from sklearn.feature_extraction import DictVectorizer
from sklearn.neighbors import NearestNeighbors


STYLE_HINTS = {
    "minimalist": ["black", "white", "gray", "beige"],
    "chic": ["black", "white", "beige", "brown"],
    "smart casual": ["white", "blue", "gray", "black"],
    "streetwear": ["black", "gray", "blue"],
    "korean": ["white", "beige", "gray", "blue"],
    "party": ["black", "red", "pink"],
}

TEXT_CONCEPTS = [
    ("Anchor Look", "Build the fit around {focus_name} as the hero piece."),
    ("Layered Direction", "Shape the outfit around {focus_name} with extra layering in mind."),
    ("Color Story", "Use {focus_name} as the starting point for the color direction."),
    ("Rotation Refresh", "Bring {focus_name} back into rotation as the lead item."),
    ("Soft Statement", "Let {focus_name} carry the quiet statement of the look."),
]


def normalize(value):
    return (value or "").strip().lower()


def title_case(value):
    return (value or "").strip().title()


def build_rules(occasion):
    mapping = {
        "formal": ["Top", "Bottom", "Shoes"],
        "business": ["Top", "Bottom", "Outerwear", "Shoes"],
        "party": ["Dress", "Shoes", "Accessory"],
        "travel": ["Top", "Bottom", "Outerwear", "Shoes"],
        "sportswear": ["Top", "Bottom", "Shoes"],
    }
    return mapping.get(normalize(occasion), ["Top", "Bottom", "Shoes"])


def style_palette(preferred_style):
    lowered = normalize(preferred_style)
    for keyword, colors in STYLE_HINTS.items():
        if keyword in lowered:
            return colors
    return []


def season_match(item_season, target_season):
    item_value = normalize(item_season)
    target_value = normalize(target_season)
    if not target_value:
        return 1.0
    if item_value == target_value:
        return 1.0
    if item_value == "all season":
        return 0.8
    return 0.0


def build_feature_record(item):
    wear_count = int(item.get("wear_count", 0) or 0)
    favorite = int(item.get("favorite", 0) or 0)
    color = normalize(item.get("color"))
    record = {
        f"category={normalize(item.get('category'))}": 1.0,
        f"occasion={normalize(item.get('occasion'))}": 1.0,
        f"season={normalize(item.get('season'))}": 1.0,
        f"color={color or 'unknown'}": 1.0,
        f"favorite={favorite}": 1.0,
        "wear_count_scaled": min(1.0, wear_count / 10.0),
        "rotation_value": 1.0 / (wear_count + 1.0),
    }
    brand = normalize(item.get("brand"))
    if brand:
        record[f"brand={brand}"] = 0.6
    return record


def build_query_record(category, occasion, season, preferred_style, preferred_color, variant_index):
    palette = style_palette(preferred_style)
    color_candidates = []
    if preferred_color:
        color_candidates.append(normalize(preferred_color))
    color_candidates.extend(palette)
    if not color_candidates:
        color_candidates = ["neutral", "black", "white", "beige"]

    color_choice = color_candidates[variant_index % len(color_candidates)]
    record = {
        f"category={normalize(category)}": 1.0,
        f"occasion={normalize(occasion)}": 1.0,
        "wear_count_scaled": 0.15 + (variant_index * 0.08),
        "rotation_value": 1.0,
        f"color={color_choice}": 1.0,
    }
    if season:
        record[f"season={normalize(season)}"] = 1.0
        record["season=all season"] = 0.45
    if preferred_style:
        record[f"style={normalize(preferred_style)}"] = 0.4
    if variant_index % 2 == 0:
        record["favorite=1"] = 0.25
    else:
        record["favorite=0"] = 0.25
    return record


def build_category_models(wardrobe):
    models = {}
    by_category = {}
    for item in wardrobe:
        category = item.get("category")
        if not category:
            continue
        by_category.setdefault(category, []).append(item)

    for category, items in by_category.items():
        records = [build_feature_record(item) for item in items]
        vectorizer = DictVectorizer(sparse=True)
        matrix = vectorizer.fit_transform(records)
        neighbors = NearestNeighbors(metric="cosine", n_neighbors=min(len(items), max(1, len(items))))
        neighbors.fit(matrix)
        models[category] = {
            "items": items,
            "vectorizer": vectorizer,
            "neighbors": neighbors,
        }
    return models


def ml_rank_category_items(category_model, category, occasion, season, preferred_style, preferred_color, variant_index):
    query_record = build_query_record(category, occasion, season, preferred_style, preferred_color, variant_index)
    query_vector = category_model["vectorizer"].transform([query_record])
    distances, indices = category_model["neighbors"].kneighbors(query_vector, n_neighbors=len(category_model["items"]))

    ranked = []
    palette = style_palette(preferred_style)
    for distance, raw_index in zip(distances[0], indices[0]):
        item = category_model["items"][raw_index]
        boost = 0.0
        if preferred_color and preferred_color.lower() in normalize(item.get("color")):
            boost += 0.12
        if any(color in normalize(item.get("color")) for color in palette):
            boost += 0.08
        boost += 0.06 * season_match(item.get("season"), season)
        boost += 0.05 if normalize(item.get("occasion")) == normalize(occasion) else 0.0
        boost += 0.04 if int(item.get("favorite", 0) or 0) == 1 else 0.0
        boost += min(0.12, 0.02 / (int(item.get("wear_count", 0) or 0) + 1.0))
        adjusted_score = (1.0 - float(distance)) + boost
        ranked.append((adjusted_score, item))

    ranked.sort(key=lambda pair: (-pair[0], pair[1].get("name", "")))
    return ranked


def build_ml_outfits(wardrobe, occasion, season, preferred_style, preferred_color, outfit_count):
    categories = build_rules(occasion)
    models = build_category_models(wardrobe)
    if any(category not in models for category in categories):
        return []

    outfits = []
    seen = set()
    usage_counts = {}
    variant_window = max(outfit_count + 4, 7)

    for variant_index in range(variant_window):
        selected = []
        used_ids = set()
        total_score = 0.0
        valid = True

        for category in categories:
            ranked_items = ml_rank_category_items(
                models[category], category, occasion, season, preferred_style, preferred_color, variant_index
            )
            picked = None
            picked_score = None

            for rank_index, (score, item) in enumerate(ranked_items):
                item_id = item.get("id")
                if item_id in used_ids:
                    continue

                diversity_penalty = usage_counts.get(item_id, 0) * 0.28
                rank_penalty = rank_index * 0.035
                freshness_bonus = min(0.16, 0.04 / (int(item.get("wear_count", 0) or 0) + 1.0))
                adjusted_score = score - diversity_penalty - rank_penalty + freshness_bonus

                if picked is None or adjusted_score > picked_score:
                    picked = item
                    picked_score = adjusted_score

            if picked is None:
                valid = False
                break

            used_ids.add(picked.get("id"))
            total_score += picked_score or 0.0
            selected.append(picked)

        if not valid:
            continue

        if normalize(season) == "rainy" and "Outerwear" in models and all(normalize(item.get("category")) != "outerwear" for item in selected):
            for _, extra_item in ml_rank_category_items(
                models["Outerwear"], "Outerwear", occasion, season, preferred_style, preferred_color, variant_index
            ):
                if extra_item.get("id") not in used_ids:
                    selected.append(extra_item)
                    used_ids.add(extra_item.get("id"))
                    break

        combo_key = tuple(sorted(int(item.get("id")) for item in selected))
        if combo_key in seen:
            continue
        seen.add(combo_key)
        outfits.append((total_score, selected))
        for item in selected:
            item_id = int(item.get("id"))
            usage_counts[item_id] = usage_counts.get(item_id, 0) + 1
        if len(outfits) >= outfit_count:
            break

    outfits.sort(key=lambda pair: -pair[0])
    return [items for _, items in outfits]


def wardrobe_snapshot(wardrobe):
    categories = {}
    for item in wardrobe:
        key = item.get("category") or "Item"
        categories.setdefault(key, []).append(item)
    return categories


def format_missing_text(missing_categories):
    if not missing_categories:
        return "supporting layers"
    lowered = [category.lower() for category in missing_categories]
    if len(lowered) == 1:
        return lowered[0]
    if len(lowered) == 2:
        return f"{lowered[0]} and {lowered[1]}"
    return ", ".join(lowered[:-1]) + f", and {lowered[-1]}"


def category_phrase(category):
    normalized = normalize(category)
    mapping = {
        "top": "a top",
        "bottom": "a bottom",
        "outerwear": "an outerwear layer",
        "shoes": "a pair of shoes",
        "dress": "a dress",
        "accessory": "an accessory",
    }
    return mapping.get(normalized, f"a {normalized or 'piece'}")


def rank_anchor_items(wardrobe, preferred_color, palette):
    ranked = []
    for item in wardrobe:
        score = 0.0
        score += 0.3 if int(item.get("favorite", 0) or 0) == 1 else 0.0
        score += 0.25 / (int(item.get("wear_count", 0) or 0) + 1.0)
        if preferred_color and preferred_color.lower() in normalize(item.get("color")):
            score += 0.25
        if any(color in normalize(item.get("color")) for color in palette):
            score += 0.18
        ranked.append((score, item))
    ranked.sort(key=lambda pair: (-pair[0], pair[1].get("name", "")))
    return [item for _, item in ranked]


def select_partial_items(snapshot, categories, preferred_color, palette, variant_index):
    selected = []
    used_ids = set()

    for category_index, category in enumerate(categories):
        items = snapshot.get(category, [])
        if not items:
            continue

        ranked = rank_anchor_items(items, preferred_color, palette)
        item = ranked[(variant_index + category_index) % len(ranked)]
        item_id = int(item.get("id", 0))
        if item_id in used_ids:
            for fallback in ranked:
                fallback_id = int(fallback.get("id", 0))
                if fallback_id not in used_ids:
                    item = fallback
                    item_id = fallback_id
                    break
        if item_id in used_ids:
            continue

        used_ids.add(item_id)
        selected.append(item)

    return selected


def build_text_only_outfits(wardrobe, occasion, season, preferred_style, preferred_color, outfit_count):
    categories = build_rules(occasion)
    palette = style_palette(preferred_style)
    snapshot = wardrobe_snapshot(wardrobe)
    available_categories = [category for category in categories if snapshot.get(category)]
    missing_categories = [category for category in categories if not snapshot.get(category)]
    highlighted_items = rank_anchor_items(wardrobe, preferred_color, palette)[: min(max(outfit_count, 3), len(wardrobe))]
    color_direction = (preferred_color or (palette[0] if palette else "neutral")).lower()
    style_text = preferred_style.strip() if preferred_style else "refined"
    season_text = season.strip() if season else "all-season"
    missing_text = format_missing_text(missing_categories)
    occasion_lower = occasion.lower()

    if not wardrobe:
        return []

    outfits = []
    for index in range(1, outfit_count + 1):
        focus_item = highlighted_items[(index - 1) % len(highlighted_items)]
        focus_name = focus_item.get("name", "a saved piece")
        focus_category = normalize(focus_item.get("category")) or "piece"
        concept_label, concept_lead = TEXT_CONCEPTS[(index - 1) % len(TEXT_CONCEPTS)]
        partial_items = select_partial_items(snapshot, categories, preferred_color, palette, index - 1)
        summary_parts = [
            concept_lead.format(focus_name=focus_name),
            f"Keep the silhouette {style_text} and the mood aligned with {occasion_lower} dressing.",
        ]
        if season:
            summary_parts.append(f"Push it toward a {season_text.lower()} finish.")
        if preferred_color or palette:
            summary_parts.append(f"Let {color_direction} guide the color story.")
        if missing_categories:
            summary_parts.append(f"Use your saved pieces as the base, then complete it with {missing_text} if those categories are still missing from your wardrobe.")
        elif partial_items:
            summary_parts.append("Use the suggested wardrobe pieces below to build the look immediately.")

        reasons = [
            f"Anchors the concept on your available {focus_category} pieces.",
            f"Uses wardrobe signals like color, favorites, and wear rotation to choose the focal item.",
            "Keeps the recommendation useful even before the wardrobe is complete.",
        ]
        if preferred_style:
            reasons.append(f"Follows your selected style direction: {preferred_style}.")
        if missing_categories:
            reasons.append("Adds outside-the-wardrobe suggestions only for the missing categories needed to complete the outfit.")

        outfits.append({
            "title": f"Option {index}: {concept_label}",
            "summary": " ".join(summary_parts),
            "wear_guide": build_text_wear_guide(
                focus_name,
                missing_categories,
                occasion,
                season,
                preferred_style,
                preferred_color,
            ),
            "items": partial_items,
            "reasons": reasons,
            "mode": "description",
        })

    return outfits


def build_reasons(outfit, occasion, preferred_style, preferred_color, season):
    reasons = [
        f"Built for a {occasion.lower()} setting using the closest matching pieces in your wardrobe.",
    ]
    if preferred_style:
        reasons.append(f"Pulled toward your selected style direction: {preferred_style}.")
    if preferred_color:
        reasons.append(f"Weighted items that support your preferred color: {preferred_color}.")
    if normalize(season) == "rainy":
        reasons.append("Added extra weight to layering and season-compatible pieces.")
    if any(int(item.get("wear_count", 0) or 0) == 0 for item in outfit):
        reasons.append("Balances style fit with lower-wear pieces to improve rotation.")
    return reasons


def build_item_wear_guide(outfit, occasion, season, preferred_style, preferred_color):
    lines = []
    for item in outfit:
        category = normalize(item.get("category"))
        prefix = {
            "top": "Wear",
            "bottom": "Pair it with",
            "outerwear": "Layer with",
            "shoes": "Finish with",
            "dress": "Choose",
            "accessory": "Add",
        }.get(category, "Include")
        lines.append(
            f"{prefix} {item.get('name')} as your {category or 'key'} piece"
            + (f" in {(item.get('color') or 'a versatile tone').lower()}." )
        )

    if preferred_style:
        lines.append(f"Keep the overall styling {preferred_style.lower()} and polished for {occasion.lower()} wear.")
    elif season:
        lines.append(f"Keep the final look suitable for {season.lower()} conditions.")

    if preferred_color:
        lines.append(f"Use {preferred_color.lower()} accents to tie the outfit together.")

    return lines[:6]


def build_text_wear_guide(focus_name, missing_categories, occasion, season, preferred_style, preferred_color):
    lines = [f"Start with {focus_name} as the anchor piece for this {occasion.lower()} outfit."]
    for index, category in enumerate(missing_categories[:3]):
        starter = ["Add", "Pair it with", "Finish with"][min(index, 2)]
        color_note = f" in {preferred_color.lower()}" if preferred_color else ""
        lines.append(f"{starter} {category_phrase(category)}{color_note} from outside your current wardrobe to complete the look.")

    if preferred_style:
        lines.append(f"Keep the silhouette {preferred_style.lower()} rather than overly busy.")
    elif season:
        lines.append(f"Choose textures and coverage that fit {season.lower()} weather.")

    return lines[:6]


def title_for(index, preferred_style, occasion):
    style_text = preferred_style.strip() if preferred_style else "Smart"
    return f"Option {index}: {style_text} {title_case(occasion)} Look"


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"message": "Missing input file.", "outfits": []}))
        return

    payload = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    wardrobe = payload.get("wardrobe", [])
    fallback_wardrobe = payload.get("fallback_wardrobe", [])
    occasion = payload.get("occasion", "Casual")
    season = payload.get("season", "")
    preferred_style = payload.get("preferred_style", "")
    preferred_color = payload.get("color", "")
    outfit_count = int(payload.get("outfit_count", 5))
    outfit_count = max(3, min(5, outfit_count))

    outfits = build_ml_outfits(wardrobe, occasion, season, preferred_style, preferred_color, outfit_count)

    if not outfits:
        text_source = wardrobe or fallback_wardrobe
        text_only_outfits = build_text_only_outfits(text_source, occasion, season, preferred_style, preferred_color, outfit_count)
        if text_only_outfits:
            output = {
                "message": f"Generated {len(text_only_outfits)} ML-guided fit concept(s) from your wardrobe.",
                "access": "Your exact filters could not form a full outfit set, so the model generated text-based fit directions from the wardrobe pieces you already saved.",
                "outfits": text_only_outfits,
            }
            print(json.dumps(output))
            return

        output = {
            "message": "No matching outfit set could be created from the current wardrobe filters.",
            "access": "Add more wardrobe variety so the model can build stronger combinations.",
            "outfits": [],
        }
        print(json.dumps(output))
        return

    response_outfits = []
    for index, outfit in enumerate(outfits, start=1):
        response_outfits.append({
            "title": title_for(index, preferred_style or "Smart", occasion),
            "summary": f"A curated {occasion.lower()} combination ranked by the wardrobe similarity model from your saved pieces.",
            "wear_guide": build_item_wear_guide(outfit, occasion, season, preferred_style, preferred_color),
            "items": outfit,
            "reasons": build_reasons(outfit, occasion, preferred_style, preferred_color, season),
            "mode": "items",
        })

    output = {
        "message": f"Generated {len(response_outfits)} ML-ranked outfit option(s) from your wardrobe.",
        "access": "Recommendations were produced by a content-based machine learning model fitted on your saved wardrobe data.",
        "outfits": response_outfits,
    }
    print(json.dumps(output))


if __name__ == "__main__":
    main()
