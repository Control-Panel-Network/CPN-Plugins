# Backup Destinations (Host)

Free Host-scoped Plugin Store package for CPN Panel.

## What it unlocks

- `/backups/google-drive` (Google Drive OAuth destination)
- `/backups/remote` (SFTP/SCP)
- `/backups/s3` (S3-compatible object storage)
- `/backups/incremental` (restic jobs)

## Always available in core (no plugin required)

- `/backups` hub
- `/backups/create`
- `/backups/restore`
- `/backups/schedule`
- `/backups/destinations` (local toggles; remote links unlock with this plugin)

## Install

From Plugin Store (Host target), or:

```bash
sudo cpn plugin install --host --id backupDestinations
```

`install-host.sh` writes `/var/lib/cpn/features/backup-destinations.enabled`. Missing `restic` or `aws` CLI does not fail install.

## Uninstall

Clears the feature flag and hides remote destination UIs. Does not delete local archives or destination secrets.
