# ClamAV (free malware scanner)

CPN Panel catalog plugin. Installs **ClamAV** on the host so **Security → Malware scan** can use the free local engine (`clamscan` / `clamd`).

This package is for **CPN Panel** (Control Panel Network). It is not branded as CyberPanel.

## What it does

1. Catalog install copies this folder to `/home/<domain>/plugins/clamav/`.
2. An operator (root) runs `install-host.sh` to install host packages.
3. CPN Panel probes `clamscan` / `clamdscan` / `clamd` and unlocks the Malware scan sidebar when tools are present.

## Requirements

- AlmaLinux 8 / 9 / 10 (dnf) or Debian/Ubuntu (apt)
- Root shell for host package install
- Outbound network for package mirrors and virus definitions

## Install (CPN)

```bash
# From the panel Plugin Store, or:
sudo cpn plugin install --domain example.com --id clamav

# Then install host packages:
sudo /home/example.com/plugins/clamav/install-host.sh
```

Fresh virus DB (optional, after packages):

```bash
sudo freshclam || true
sudo systemctl enable --now clamd@scan 2>/dev/null || sudo systemctl enable --now clamav-daemon 2>/dev/null || true
```

## Verify

```bash
command -v clamscan
clamscan --version
# In CPN Panel: Security → Malware scan should show engine clamav
```

## Paid alternative

For cloud/API scanning without local ClamAV, install catalog plugin `ntMalwareApi` and configure `/var/lib/cpn/malware.json` (token never committed to git).

## Uninstall host packages

```bash
sudo /home/example.com/plugins/clamav/uninstall-host.sh
# Then remove the site plugin copy if desired:
sudo cpn plugin remove --domain example.com --id clamav --yes
```

## Security

- No API keys in this package.
- Definition updates use your OS ClamAV/freshclam configuration only.
