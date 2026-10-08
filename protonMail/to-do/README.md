# Proton Mail (external / Bridge)

Free CPN Panel catalog plugin (Host scope). Installing it unlocks **Email → Proton Mail** (sidebar and hub tile).

## What this plugin can do

- Show **Open Proton Mail** linking to https://mail.proton.me (label and enable/disable in panel settings)
- Store operator display fields: Proton account email, display name, Bridge host/ports, optional notes
- Document Proton Mail Bridge defaults (IMAP/SMTP) for local mail clients on a workstation
- Gate the Email hub page until the Host plugin is installed (same pattern as MTA-STS / BIMI)

## What this plugin cannot do

- It does **not** run a Proton Mail server inside CPN
- It does **not** terminate or host Proton end-to-end encryption on the panel host
- It does **not** store Proton account passwords in git or in this package
- It does **not** replace Tachyon / SnappyMail / Roundcube as the default active webmail
- Bridge automation (install/heal Bridge as a service) is **not** LIVE in v1.0.0; see `BRIDGE-FOLLOWUP.md`

## Install

```bash
# Plugin Store → Host target → Email → Proton Mail, or:
sudo cpn plugin install --host --id protonMail

# Optional host flag (also written by install-host.sh):
sudo /var/lib/cpn/host-plugins/protonMail/install-host.sh
```

## Verify

- Sidebar: Email → Proton Mail appears
- Open `https://<panel>/email/proton`, save settings, use Open Proton Mail

## Uninstall

```bash
sudo /var/lib/cpn/host-plugins/protonMail/uninstall-host.sh
# Or Plugins → Installed → Uninstall from Host
```
