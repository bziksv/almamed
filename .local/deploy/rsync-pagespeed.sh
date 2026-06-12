#!/usr/bin/env bash
# Синхронизация PageSpeed-правок на prod (альтернатива git pull, если не всё в main).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
LIST="${ROOT}/.local/deploy/pagespeed-rsync.txt"
REMOTE="${1:?Usage: $0 user@host:/path/to/almamed.su/}"

mapfile -t FILES < <(grep -v '^#' "$LIST" | grep -v '^[[:space:]]*$' || true)
if [[ ${#FILES[@]} -eq 0 ]]; then
  echo "No files in $LIST"
  exit 1
fi

echo "=== rsync PageSpeed bundle → $REMOTE (${#FILES[@]} paths) ==="
rsync -avz --relative "${FILES[@]/#/$ROOT/}" "$REMOTE"

echo
echo "Done. Run post-deploy on server:"
echo "  ssh … 'cd /path && sudo -u almamed.su bash .local/deploy/post-deploy-pagespeed.sh'"
