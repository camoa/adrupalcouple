#!/usr/bin/env bash
#
# ADrupalCouple deploy — run on the target environment AFTER pulling the code.
#
# Deployment model:
#   - CONFIG  -> Drupal config sync (cex on source / cim here). Source of truth = sites/default/sync.
#   - CONTENT -> single_content_sync. The build-created structural content (Home + Accessibility
#                pages + main/footer menu links) lives in content-export/ and is NOT carried by
#                config sync. Existing content is SKIPPED on import (your articles are never touched).
#   - i18n    -> interface translations live in DB locale tables, NOT config -> imported from the .po.
#   - THEME   -> Vite build emits dist/css (incl. the self-hosted brand fonts) — a build artifact.
#
# Idempotent: safe to re-run.
set -euo pipefail
cd "$(dirname "$0")/.."   # project root

DRUSH="${DRUSH:-drush}"
THEME="themes/custom/adrupalcouple"

echo "==> 1/6  composer install"
composer install --no-dev --no-interaction --prefer-dist

echo "==> 2/6  theme build (Vite: dist/css + self-hosted fonts)"
( cd "$THEME" && npm ci && npm run build )

echo "==> 3/6  database updates"
$DRUSH updatedb -y

echo "==> 4/6  config import (sites/default/sync)"
$DRUSH config:import -y

echo "==> 5/6  content import (build-created structural content; existing skipped)"
for f in content-export/*.yml; do
  [ -e "$f" ] || continue
  echo "    - $f"
  $DRUSH content:import "$f" -y
done

echo "==> 6/6  interface translations (es) — locale tables, not config"
if [ -f "$THEME/translations/es.po" ]; then
  $DRUSH locale:import es "$THEME/translations/es.po" --type=customized --override=not-customized
fi

$DRUSH cache:rebuild
echo "==> deploy complete"
