#!/usr/bin/env python3
import json
from pathlib import Path

p = Path("/home/newstargeted.com/api.newstargeted.com/modules/plugin_grants/allowed_plugins.json")
data = json.loads(p.read_text(encoding="utf-8"))
data["ntHostingBilling"] = "News Targeted Hosting Commerce"
data["ntMalwareApi"] = "News Targeted Malware API"
p.write_text(json.dumps(data, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
print("updated", "ntHostingBilling" in data, "ntMalwareApi" in data)
