# PostgreSQL Manager

Author: KraoESPfan1n

Free CPN plugin that installs PostgreSQL, creates a dedicated local admin role, and exposes Adminer at `/postgres-adminer/` with an automatic PostgreSQL login button from the plugin page.

## What It Installs

- `postgresql-server` and `postgresql-contrib`
- The matching LiteSpeed PHP PostgreSQL extension where available, for example `lsphp83-pgsql`
- Adminer under `/usr/local/cpn/public/postgres-adminer/`
- A dedicated PostgreSQL role named `cpn_pgadmin`
- A default database named `cpn_postgres`

PostgreSQL is kept bound to localhost by default.

## Installation

From the plugin directory:

```bash
bash install.sh
```

Then open:

- CPN plugin page: `/plugins/postgresManager/`
- PostgreSQL web console: `/postgres-adminer/`

The generated password is stored on the server at:

```text
/usr/local/cpn/pluginState/postgresManager/cpn_pgadmin_password
```

## Compatibility

The installer supports:

- AlmaLinux, Rocky Linux, CentOS, RHEL style systems with `dnf` or `yum`
- Debian and Ubuntu systems with `apt-get`
- CPN installs with dynamic plugin routing
- Older CPN plugin routing by adding an idempotent fallback route in `pluginHolder/urls.py`
