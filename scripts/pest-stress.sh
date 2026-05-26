#!/usr/bin/env bash
# pest-stress.sh — repeat-runner for flake hunting.
#
# Re-runs the Pest suite (or a filtered subset) N times in a loop, bailing
# at the first failure with a clear "iteration X failed" banner. Surfaces
# intermittent failures that would slip past a single green run — exactly
# the class of bug that bit us with the Playwright 5s timeout flakes.
#
# Usage (inside the container — invoke via `sail shell` first, or `sail exec`):
#
#   ./scripts/pest-stress.sh                              # 10 runs of the full suite
#   ./scripts/pest-stress.sh 50                           # 50 runs of the full suite
#   ./scripts/pest-stress.sh 20 --testsuite=Browser       # 20 runs of one suite
#   ./scripts/pest-stress.sh 30 --filter=Impersonation    # 30 runs of one test file
#
# All args after the first are forwarded to pest verbatim.

set -euo pipefail

ITERATIONS="${1:-10}"
shift || true
PEST_ARGS=("$@")

if ! [[ "$ITERATIONS" =~ ^[0-9]+$ ]] || [ "$ITERATIONS" -lt 1 ]; then
    echo "First argument must be a positive integer (iteration count). Got: '$ITERATIONS'" >&2
    exit 64
fi

cd "$(dirname "$0")/.."

echo "▶ Running pest ${PEST_ARGS[*]:-(full suite)} × $ITERATIONS iterations"
echo

START=$(date +%s)
for ((i = 1; i <= ITERATIONS; i++)); do
    printf '═══ iteration %d/%d ═══\n' "$i" "$ITERATIONS"
    if ! vendor/bin/pest --bail "${PEST_ARGS[@]}"; then
        echo
        echo "✘ iteration $i FAILED — stopping the loop." >&2
        echo "   re-run with the same args to reproduce, or add --filter=… to narrow." >&2
        exit 1
    fi
    echo
done
END=$(date +%s)

echo "✔ all $ITERATIONS iterations passed in $((END - START))s."
