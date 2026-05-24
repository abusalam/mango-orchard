#!/usr/bin/env bash
# Optimise a source image into the WebP the welcome hero uses.
#
# Reads a source image, resizes it to 1600px wide (preserving aspect ratio),
# encodes it as WebP at quality 85, and writes it to
#   public/images/hero-orchard-photo.webp
# — the file the welcome blade picks up first.
#
# Usage:
#   ./scripts/optimize-hero.sh                      # source defaults to public/images/orchard-photo.png
#   ./scripts/optimize-hero.sh path/to/source.png   # custom source
#
# Requires:
#   - Docker + Sail stack running (sail up -d)
#   - ffmpeg available inside the laravel.test container (it is, in the default Sail image)

set -euo pipefail

SOURCE="${1:-public/images/orchard-photo.png}"
OUT="public/images/hero-orchard-photo.webp"

# Resolve script location and the project root (parent dir of this script).
SCRIPT_DIR="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_ROOT="$( cd -- "$SCRIPT_DIR/.." &> /dev/null && pwd )"
cd "$PROJECT_ROOT"

if [[ ! -f "$SOURCE" ]]; then
    echo "ERROR: source image not found at $SOURCE" >&2
    echo "Drop a high-res orchard photo at $SOURCE, then re-run, or pass a custom path:" >&2
    echo "  $0 path/to/your-source.png" >&2
    exit 1
fi

if ! docker compose ps --status running --services 2>/dev/null | grep -q '^laravel.test$'; then
    echo "ERROR: the laravel.test container is not running." >&2
    echo "Start the stack first:  ./vendor/laravel/sail/bin/sail up -d" >&2
    exit 1
fi

echo "Optimising $SOURCE → $OUT (resize=1600w, format=WebP q=85)…"

docker compose exec -T laravel.test ffmpeg -y -hide_banner -loglevel error \
    -i "$SOURCE" \
    -vf scale=1600:-1 \
    -c:v libwebp -quality 85 \
    "$OUT"

echo
echo "Done. Output file:"
ls -lh "$OUT"
echo
echo "The welcome page picks up $OUT automatically on the next request."
