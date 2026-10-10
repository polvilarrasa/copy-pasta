#!/usr/bin/env bash
#
# Fails when a commit in BASE..HEAD adds or modifies a file larger than 1 MB, unless the path is listed with a
# justification in .github/large-files-allowed.txt. Every commit is checked, not just the final tree: a file added
# and removed again inside the same branch still ends up in the history that gets pushed and cloned.
#
# Usage: check-large-files.sh <base-ref> [head-ref]

set -euo pipefail

base="${1:?usage: check-large-files.sh <base-ref> [head-ref]}"
head="${2:-HEAD}"
limit=1048576
allowlist=".github/large-files-allowed.txt"
allowed_paths=""

if [ -f "$allowlist" ]; then
    while IFS= read -r line || [ -n "$line" ]; do
        case "$line" in
            '' | '#'*) continue ;;
        esac

        path="${line%% # *}"
        reason="${line#* # }"

        if [ "$path" = "$line" ] || [ -z "${reason// /}" ]; then
            echo "::error file=$allowlist::Exception without a justification: '$line'. Use '<path> # <why it has to be in the repository>'."
            exit 1
        fi

        allowed_paths="${allowed_paths}${path}"$'\n'
    done < "$allowlist"
fi

failed=0

for commit in $(git rev-list --no-merges "$base..$head"); do
    while IFS=$'\t' read -r meta path; do
        [ -n "$path" ] || continue

        blob="$(awk '{print $4}' <<< "$meta")"
        size="$(git cat-file -s "$blob")"

        if [ "$size" -le "$limit" ]; then
            continue
        fi

        if grep -Fxq -- "$path" <<< "$allowed_paths"; then
            continue
        fi

        echo "::error::Commit ${commit:0:7} adds '$path' ($((size / 1024)) KB), over the 1 MB limit. Keep it out of the repository, or justify it in $allowlist."
        failed=1
    done < <(git -c core.quotepath=off diff-tree -r --no-commit-id --diff-filter=AM --raw "$commit")
done

exit "$failed"
