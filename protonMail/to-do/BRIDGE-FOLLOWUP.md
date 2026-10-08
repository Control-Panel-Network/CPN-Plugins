# Follow-up: Proton Mail Bridge automation (not LIVE in v1.0.0)

v1.0.0 ships Store unlock, Email hub page, settings, Open Proton Mail, and Bridge **guidance** only.

Possible later work (keep as SCAFFOLD until implemented):

1. Detect Bridge listening on configured host/ports (operator workstation only; not assumed on the CPN server)
2. Optional panel toast when Bridge ports are unreachable from the panel host (honest: often N/A for remote panels)
3. Documented Windows / macOS / Linux Bridge install links without embedding Proton credentials
4. Optional "prefer Proton for Open Webmail" switch that redirects Open Webmail to mail.proton.me without changing Tachyon as the default active IMAP client

Do not mark any of the above LIVE until code lands.
