# FileGator (`fileGator`)

CPN Panel site plugin that deploys [FileGator](https://github.com/filegator/filegator) as an enhanced multi-user file manager for one domain or sub-domain.

**Author:** master3395  
**Pricing:** free  
**Pinned upstream:** FileGator **7.16.5** (SHA256-verified zip)

## What it does

- Copies into `/home/<domain>/plugins/fileGator/` from the CPN Plugin Store catalog
- Downloads the pinned precompiled FileGator build into `app/`
- Jails the storage adapter to the **site home** (or docroot)
- Publishes a clean URL: `https://<domain>/filegator` (symlink to `app/dist` only)
- Generates a strong admin password under `/var/lib/cpn/filegator/<domain>/` (mode 600)

## Quick start

```bash
# After Store install on example.com
sudo bash /home/example.com/plugins/fileGator/install.sh example.com
sudo cat /var/lib/cpn/filegator/example.com/admin.credentials
# Open https://example.com/filegator
```

See **CPN.md** for heal, uninstall, and security notes.

## Layout

```text
fileGator/
  meta.xml
  cpn-plugin.json
  install.sh
  heal.sh
  uninstall.sh
  CPN.md
  README.md
  app/                 # created on install (gitignored)
    dist/              # public (symlinked)
    private/           # users, logs, sessions
    configuration.php  # mode 600
```
