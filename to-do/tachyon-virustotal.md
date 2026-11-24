# Tachyon VirusTotal (CPN Email plugin)

## What it does

Dual-mode VirusTotal checks inside Tachyon webmail:

- **No API key:** SHA-256 / URL open on VirusTotal GUI
- **Optional free public API key:** inline detection counts; optional submit with confirm

## Install on a CPN host

```bash
# After Plugin Store install, or from this repo:
sudo bash /home/<domain>/plugins/tachyonVirusTotal/install-host.sh
# or:
sudo bash tachyonVirusTotal/install-host.sh
```

Then open Tachyon Admin → Extensions → VirusTotal to paste an optional API key.

## API key links

- Create account: https://www.virustotal.com/gui/join-us
- Get API key: https://www.virustotal.com/gui/my-apikey
- API overview: https://docs.virustotal.com/docs/api-overview

## Upstream

Plugin source also targets `kimusan/Tachyon` `plugins/virustotal/` so it can appear in Tachyon Admin → Extensions packages.
