#!/usr/bin/env bash
# Sets config-version to the release, and records the changes to config/ultimate-formatter.php since the last
# recorded diff as config/diffs/{version}.diff, so `ultimate-formatter migrate-config` can replay them on a published copy.
# semantic-release runs it in its prepare step with the version being released.
set -euo pipefail

version=${1:?usage: generate-config-diff.sh <version>}
config=config/ultimate-formatter.php
diffs=config/diffs
version_pattern="^    'config-version' => '[^']*',$"

# The diff leaves the version line out, so it can be set before diffing.
perl -pi -e "s/^    'config-version' => '[^']*',\$/    'config-version' => '$version',/" "$config"

base=$(git log -1 --format=%H -- "$diffs")
status=0
diff -U1 --label "a/$config" --label "b/$config" \
    <(git show "$base:$config" | grep -vE "$version_pattern") \
    <(grep -vE "$version_pattern" "$config") > "$diffs/$version.diff" || status=$?

if [ "$status" -eq 0 ]; then
    rm "$diffs/$version.diff"
    echo "$config is unchanged since $base."
    exit 0
fi
if [ "$status" -ne 1 ]; then exit "$status"; fi
echo "Recorded $diffs/$version.diff."
