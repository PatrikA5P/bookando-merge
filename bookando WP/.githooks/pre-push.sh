#!/usr/bin/env sh
set -eu
if [ "${ALLOW_MAIN_PUSH:-0}" = "1" ]; then
  exit 0
fi
protected_branch="refs/heads/main"
while read local_ref local_sha remote_ref remote_sha
do
  if [ "$remote_ref" = "$protected_branch" ]; then
    printf "%s\n" "Direktes Pushen auf 'main' ist deaktiviert." \
                  "Bitte: git push -u origin <feature-branch> und PR erstellen." >&2
    exit 1
  fi
done
exit 0