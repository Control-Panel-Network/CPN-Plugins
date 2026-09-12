# Fail2ban on CPN Panel

Catalog id: `fail2ban`

CPN Panel unlocks **Security → Fail2ban** when `fail2ban-client` (or `fail2ban-server`) is on the host PATH. Install host packages after the Plugin Store copies this folder:

```bash
sudo cpn plugin install --domain example.com --id fail2ban
sudo /home/example.com/plugins/fail2ban/install-host.sh
```

Legacy Django/UI files in this folder may still mention older hosts. CPN Panel sanitizes store listings and uses host probes for the sidebar gate.

Related free/paid malware catalog plugins: `clamav`, `ntMalwareApi`.
