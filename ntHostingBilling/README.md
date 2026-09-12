# News Targeted Hosting Commerce (`ntHostingBilling`)

Paid CPN plugin: hosting business layer (clients, products/packages, orders, invoices, subscriptions, site ownership, PayPal/manual billing, client password recovery).

**Author:** master3395  
**Pricing:** paid (entitlement via `api.newstargeted.com`)

## Install

1. From CPN Plugin Store, install `ntHostingBilling` on a site (example: `test2.newstargeted.com`).
2. As root on the host:

```bash
sudo bash /home/<parent-or-domain>/.../plugins/ntHostingBilling/install-host.sh test2.newstargeted.com
```

Typical path:

`/home/newstargeted.com/test2.newstargeted.com/plugins/ntHostingBilling/install-host.sh`

3. Copy secrets:

```bash
sudo cp config.php.example config.php
sudo chmod 600 config.php
# edit admin_password, license_email, activation_key or entitlement_token
```

Optional host override: `/var/lib/cpn/nt-hosting-billing/config.php` (mode 600).

4. Open `https://test2.newstargeted.com/nt-billing` (or HTTP in lab).

## License

Uses the same News Targeted plugin grant APIs as other paid plugins:

- `POST /api/verify-plugin-grant.php`
- `POST /api/activate-plugin-key.php`
- `POST /api/verify-entitlement.php`

Plugin id: `ntHostingBilling`. Catalog metadata: `GET /api/cpn-plugins/v1/catalog.php`.

Never store tokens in git. Do not put secrets in `settings.json`.

## Operator UI

Admin password from `config.php` (separate from CPN panel login). Tabs: clients, products, orders, invoices (mark paid / PayPal), subscriptions, ownership, license.

Clients can log in, view sites/invoices, and recover passwords by email without changing CPN admin auth.

## Data

SQLite under `data/billing.sqlite` (mode 600). Schema: `sql/001_schema.sql`.

## Uninstall

```bash
sudo bash uninstall-host.sh test2.newstargeted.com
sudo cpn plugin remove --domain test2.newstargeted.com --id ntHostingBilling
```
