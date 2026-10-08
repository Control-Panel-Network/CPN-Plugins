# CPN-only catalog scope

Date: 08/10/2026

Plugins that are panel-operator tools (not public website features) should declare:

```xml
<scope>host+cpn</scope>
```

or `cpn` when Host install is not offered.

Panel installs CPN-only copies under `/var/lib/cpn/user-plugins/<user>/<id>/`.

**Auto Ban Security Alerts** uses `host+cpn` so owners can still deploy Host-wide while normal users install for their CPN account only.
