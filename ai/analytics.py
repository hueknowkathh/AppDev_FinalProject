import json
import sys
from collections import Counter
from pathlib import Path


def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Missing input file"}))
        return

    payload = json.loads(Path(sys.argv[1]).read_text(encoding="utf-8"))
    wardrobe = payload.get("wardrobe", [])

    categories = Counter(item.get("category", "Unknown") for item in wardrobe)
    seasons = Counter(item.get("season", "Unknown") for item in wardrobe)
    never_worn = [item.get("name") for item in wardrobe if int(item.get("wear_count", 0)) == 0]

    print(json.dumps({
        "category_distribution": categories,
        "season_distribution": seasons,
        "never_worn": never_worn,
    }))


if __name__ == "__main__":
    main()
