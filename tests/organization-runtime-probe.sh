#!/usr/bin/env bash
set -euo pipefail

if [[ ! -f .ddev/config.yaml || ${PWD##*/} != nextcloud-dev ]]; then
    printf '%s\n' 'Run this read-only probe from the documented nextcloud-dev root.' >&2
    exit 2
fi

ddev exec --raw -d /var/www/html/html php custom_apps/flz_data_protection/tests/organization-runtime-probe.php /var/www/html/html
