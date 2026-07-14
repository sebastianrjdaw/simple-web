#!/usr/bin/env bash
set -euo pipefail
ROOT="${SIMPLEVIEW_PROJECT_PATH:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
DATA="${SIMPLEVIEW_DATA_PATH:-$ROOT/data}"
OUT="$DATA/metrics/storage-host.json"
mkdir -p "$(dirname "$OUT")"
read -r total used free < <(df -B1 --output=size,used,avail "$DATA" | tail -n1)
project_bytes=$({ du -sb "$ROOT" 2>/dev/null || true; } | awk 'NR==1{print $1}')
project_bytes=${project_bytes:-0}
if command -v docker >/dev/null 2>&1; then
  # JSON output varies between Docker versions; retain its safe summarized output separately.
  docker_raw=$(docker system df --format '{{json .}}' 2>/dev/null || true)
else docker_raw=""; fi
tmp="${OUT}.tmp.$$"
python3 - "$tmp" "$total" "$used" "$free" "$project_bytes" "$docker_raw" <<'PY'
import json,sys,datetime
path,total,used,free,project,raw=sys.argv[1:]
def size(value):
    value=(value or '0').split()[0].upper(); units=[('TB',1000**4),('GB',1000**3),('MB',1000**2),('KB',1000),('B',1)]
    for unit,mult in units:
        if value.endswith(unit):
            try:return int(float(value[:-len(unit)])*mult)
            except:return 0
    return 0
rows=[]
for line in raw.strip().splitlines():
    line=line.strip()
    if not line:continue
    try:rows.append(json.loads(line))
    except:pass
dtotal=sum(size(x.get('Size')) for x in rows); dreclaim=sum(size(x.get('Reclaimable')) for x in rows)
data={"measured_at":datetime.datetime.now(datetime.timezone.utc).isoformat(),"filesystem":{"total_bytes":int(total),"used_bytes":int(used),"free_bytes":int(free)},"project_bytes":int(project),"docker":{"total_bytes":dtotal,"reclaimable_bytes":dreclaim,"summary":raw[:20000]}}
with open(path,'w') as f: json.dump(data,f,separators=(',',':'))
PY
chmod 0644 "$tmp"; mv -f "$tmp" "$OUT"
echo "Informe escrito en $OUT"
