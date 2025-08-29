#!/usr/bin/env bash
set -euo pipefail
command -v python3 >/dev/null 2>&1 || { echo "python3 required" >&2; exit 1; }

# Compare two snapshot directories produced by remote_snapshot.sh.
# Usage: scripts/diff_snapshots.sh SNAPSHOT_OLD SNAPSHOT_NEW
# Outputs a human-readable diff summary to stdout and machine JSON to snapshot-diff.json

if [ $# -ne 2 ]; then
  echo "Usage: $0 <old_snapshot_dir> <new_snapshot_dir>" >&2
  exit 1
fi
OLD=$1
NEW=$2

for d in "$OLD" "$NEW"; do
  [ -d "$d" ] || { echo "Missing snapshot dir: $d" >&2; exit 1; }
  [ -f "$d/snapshot.json" ] || { echo "Missing snapshot.json in $d" >&2; exit 1; }
done

read_json_field() { # file field_path (dot notation, no arrays)
  local file=$1 field=$2
  python3 - <<PY "$file" "$field" || echo ""
import json,sys
data=json.load(open(sys.argv[1]))
cur=data
for part in sys.argv[2].split('.'):
    if part in cur:
        cur=cur[part]
    else:
        print('')
        sys.exit(0)
print(cur)
PY
}

old_cfg_hash=$(read_json_field "$OLD/snapshot.json" wp_config.md5)
new_cfg_hash=$(read_json_field "$NEW/snapshot.json" wp_config.md5)
old_cfg_lines=$(read_json_field "$OLD/snapshot.json" wp_config.lines)
new_cfg_lines=$(read_json_field "$NEW/snapshot.json" wp_config.lines)

plugin_hash_changes=$(python3 - <<'PY'
import json,sys
import os
old_dir, new_dir = sys.argv[1], sys.argv[2]
po=json.load(open(os.path.join(old_dir,'snapshot.json'))).get('plugin_hashes',{})
pn=json.load(open(os.path.join(new_dir,'snapshot.json'))).get('plugin_hashes',{})
removed=[p for p in po if p not in pn]
added=[p for p in pn if p not in po]
changed=[p for p in pn if p in po and po[p]!=pn[p]]
import json as J
print(J.dumps({'added':added,'removed':removed,'changed':changed}))
PY "$OLD" "$NEW")

theme_hash_changes=$(python3 - <<'PY'
import json,sys,os
old_dir, new_dir = sys.argv[1], sys.argv[2]
to=json.load(open(os.path.join(old_dir,'snapshot.json'))).get('theme_hashes',{})
tn=json.load(open(os.path.join(new_dir,'snapshot.json'))).get('theme_hashes',{})
removed=[p for p in to if p not in tn]
added=[p for p in tn if p not in to]
changed=[p for p in tn if p in to and to[p]!=tn[p]]
import json as J
print(J.dumps({'added':added,'removed':removed,'changed':changed}))
PY "$OLD" "$NEW")

marker_old=$(python3 - <<'PY'
import json,sys,os
p=os.path.join(sys.argv[1],'snapshot.json')
print(json.load(open(p)).get('marker_present'))
PY "$OLD")
marker_new=$(python3 - <<'PY'
import json,sys,os
p=os.path.join(sys.argv[1],'snapshot.json')
print(json.load(open(p)).get('marker_present'))
PY "$NEW")

echo "Snapshot Diff Summary"
echo "OLD: $OLD  NEW: $NEW"
if [ "$old_cfg_hash" != "$new_cfg_hash" ]; then
  echo "wp-config.md5 changed: $old_cfg_hash -> $new_cfg_hash"
else
  echo "wp-config.md5 unchanged ($old_cfg_hash)"
fi
if [ "$old_cfg_lines" != "$new_cfg_lines" ]; then
  echo "wp-config line count changed: $old_cfg_lines -> $new_cfg_lines"
fi
echo "Marker present old=$marker_old new=$marker_new"

echo "Plugin hash changes: $plugin_hash_changes"
echo "Theme hash changes: $theme_hash_changes"

python3 - <<PY "$plugin_hash_changes" "$theme_hash_changes" > snapshot-diff.json
import json,sys
plugins=json.loads(sys.argv[1])
themes=json.loads(sys.argv[2])
summary={'plugins':plugins,'themes':themes}
print(json.dumps(summary,indent=2))
PY

echo "Wrote snapshot-diff.json"
