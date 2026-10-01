# Auto Ban Security Alerts Plugin - Installation Guide

## Installation Steps

### 1. Plugin Files
✅ Plugin files are located at: `/home/cpn-plugins/autoBanSecurityAlerts/`

### 2. INSTALLED_APPS
✅ Plugin has been added to `INSTALLED_APPS` in `/home/cpn-repo/cpn/settings.py`

### 3. Run Migrations
Run the following command to create database tables:

```bash
cd /usr/local/cpn
python3 manage.py migrate autoBanSecurityAlerts
```

If you encounter migration errors with other plugins, you can run migrations for all apps:
```bash
cd /usr/local/cpn
python3 manage.py migrate
```

### 4. Restart CPN
Restart the CPN service to load the plugin:

```bash
systemctl restart lscpd
```

### 5. Access the Plugin
Navigate to:
```
https://cpn.newstargeted.com:2087/plugins/autoBanSecurityAlerts/settings/
```

Or via the CPN dashboard:
- Go to Plugins → Installed Plugins
- Find "Auto Ban Security Alerts"
- Click "Settings"

### 6. Activate Premium Access
1. **Option A: Activation Key**
   - Enter your activation key in the settings page
   - Click "Activate"

2. **Option B: Patreon Subscription**
   - Subscribe to "CPN Paid Plugin" tier
   - The plugin will automatically verify your subscription

3. **Option C: PayPal Payment**
   - Complete payment via PayPal.me link
   - The plugin will automatically verify your payment

### 7. Configure Settings
Once activated:
1. Enable/disable auto-banning
2. Set ban duration (1h, 24h, 7d, 30d, or permanent)
3. Configure check interval (minimum 30 seconds)
4. Add IPs to whitelist (system IP is auto-whitelisted)

### 8. Verify Installation
Run the verification script:
```bash
/home/cpn-plugins/autoBanSecurityAlerts/verify_installation.sh
```

## Troubleshooting

### Plugin Not Appearing
- Verify plugin is in `INSTALLED_APPS` in settings.py
- Check that `/home/cpn-plugins/autoBanSecurityAlerts/` exists
- Restart CPN: `systemctl restart lscpd`
- Check CPN logs: `/usr/local/lscp/logs/error.log`

### Migration Errors
- Ensure you're in the correct directory: `cd /usr/local/cpn`
- Check database connection
- Run: `python3 manage.py migrate autoBanSecurityAlerts --verbosity=2`

### Plugin Not Working
- Check that premium access is activated
- Verify the monitoring thread is running (check logs)
- Ensure firewall manager is accessible
- Check `/usr/local/lscp/logs/error.log` for errors

## System IP Auto-Whitelisting

The plugin automatically:
- Reads the CPN machine IP from `/etc/cpn/machineIP`
- Whitelists it on first access
- Updates the whitelist if the IP changes
- Prevents deletion of the system IP

## Monitoring

The plugin runs a background monitoring thread that:
- Checks Security Alerts every N seconds (configurable)
- Extracts IPs from alerts
- Bans non-whitelisted IPs automatically
- Logs all bans for tracking

## Support

For issues or questions:
- Check the README.md file
- Review plugin logs in CPN error logs
- Contact support via Patreon or PayPal
