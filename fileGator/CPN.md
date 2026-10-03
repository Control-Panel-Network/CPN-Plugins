# FileGator on CPN Panel

Catalog id: `fileGator`

Enhanced multi-user file manager for one website. Complements the panel-native File Manager (`/websites/files` and `/server/files`); it does not replace them.

## Install

1. Plugin Store: install **FileGator File Manager** on a site (Install target: Site).
2. As root, finish the FileGator application deploy:

```bash
sudo bash /home/<domain>/plugins/fileGator/install.sh <domain>
# subdomain example:
sudo bash /home/<parent>/<fqdn>/plugins/fileGator/install.sh <fqdn>
```

3. Open `https://<domain>/filegator`
4. Login as `admin` with the password from:

```text
/var/lib/cpn/filegator/<domain>/admin.credentials
```

(mode 600, root-readable). Change the password inside FileGator after first login.

## Heal / upgrade pin

```bash
sudo bash /home/<domain>/plugins/fileGator/heal.sh <domain>
```

Preserves `app/private/` and existing credentials. Re-downloads only when the pinned `APP_VERSION` is missing.

Reset password:

```bash
sudo bash /home/<domain>/plugins/fileGator/install.sh <domain> --reset-password
```

## Repository jail

Default repository root is the **site home** (parent of `public_html`). For docroot-only:

```bash
sudo REPO_SCOPE=docroot bash /home/<domain>/plugins/fileGator/install.sh <domain> --heal
```

## Uninstall

```bash
sudo bash /home/<domain>/plugins/fileGator/uninstall.sh <domain> --purge-app
sudo cpn plugin remove --domain <domain> --id fileGator --yes
```

## Security notes

- Only `app/dist` is symlinked into the site docroot; `configuration.php`, `private/`, and vendor stay outside that symlink.
- Plugin root ships a deny-all `.htaccess`.
- Admin password is never committed; store path is under `/var/lib/cpn/`.
- Panel upgrades do not wipe `/home/<domain>/plugins/fileGator/app/private/` when you use `heal.sh`.

## Auth

v1 uses FileGator JSON users with a generated admin password. Full CPN session SSO can be added later as a custom auth adapter.
