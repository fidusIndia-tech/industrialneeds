#!/bin/sh
#
# Re-create the media symlink that a deploy destroys.
#
# Uploaded images live in <site>/persistent_public, OUTSIDE the deploy tree, and are
# reached via <site>/public_html/storage/app/public. Every panel "Deploy" rebuilds the
# web root and takes that symlink with it, after which every image on the site 404s.
#
# This cannot be done from PHP on this host: symlink(), link(), exec(), shell_exec(),
# system(), passthru() and popen() are all in disable_functions. Hence a shell script
# driven straight from cron.
#
# INSTALL — copy this OUTSIDE the deploy tree (a deploy would overwrite it otherwise):
#
#   cp scripts/relink-media.sh ~/domains/industrialsupply.in/relink-media.sh
#   chmod +x ~/domains/industrialsupply.in/relink-media.sh
#
# Then add one cron entry (hPanel -> Advanced -> Cron Jobs), every minute:
#
#   /home/u488681185/domains/industrialsupply.in/relink-media.sh
#
# The script derives every path from its own location, so it must sit in the site
# directory that holds both public_html/ and persistent_public/.

SITE="$(cd "$(dirname "$0")" && pwd)"
LINK="$SITE/public_html/storage/app/public"
TARGET="$SITE/persistent_public"
LOG="$SITE/relink-media.log"

# Refuse to do anything if the real media directory is not where we expect it.
if [ ! -d "$TARGET" ]; then
    echo "$(date '+%F %T') ERROR target missing: $TARGET" >> "$LOG"
    exit 1
fi

# Already correct? Exit silently — this runs every minute, so stay quiet when healthy.
if [ -L "$LINK" ]; then
    [ "$(readlink "$LINK")" = "$TARGET" ] && exit 0
    rm -f "$LINK"
    echo "$(date '+%F %T') removed symlink pointing at the wrong target" >> "$LOG"
fi

# A deploy (or Laravel writing to the public disk) can leave a REAL directory here.
# Use rmdir, never rm -rf: it fails on a non-empty directory, which is exactly the
# safety we want — anything with files in it needs a human to merge, not a cron job.
if [ -d "$LINK" ]; then
    if ! rmdir "$LINK" 2>/dev/null; then
        echo "$(date '+%F %T') ERROR $LINK is a non-empty directory - merge it into $TARGET by hand" >> "$LOG"
        exit 1
    fi
fi

if ln -s "$TARGET" "$LINK"; then
    echo "$(date '+%F %T') relinked $LINK -> $TARGET" >> "$LOG"
else
    echo "$(date '+%F %T') ERROR failed to create symlink $LINK" >> "$LOG"
    exit 1
fi
