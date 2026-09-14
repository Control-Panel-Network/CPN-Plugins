# -*- coding: utf-8 -*-
"""Shared paths for Roundcube Webmail catalog entry (host-package redirect)."""
from __future__ import print_function

import os

PLUGIN_NAME = 'roundcubeWebmail'
# CPN host package paths (never CyberPanel).
ROUNDCUBE_ROOT = '/opt/cpn-webmail/roundcube'
ROUNDCUBE_PUBLIC = os.path.join(ROUNDCUBE_ROOT, 'public_html')
ROUNDCUBE_CONFIG = os.path.join(ROUNDCUBE_ROOT, 'config', 'config.inc.php')
ROUNDCUBE_DB_DIR = ROUNDCUBE_ROOT
ROUNDCUBE_SQLITE = os.path.join(ROUNDCUBE_ROOT, 'db.sqlite')
SETTINGS_FILE = '/var/lib/cpn/plugins/roundcubeWebmail_settings.json'
DISABLE_MARKER = '/opt/cpn-webmail/roundcube/.cp_webmail_disabled'
ROUNDCUBE_VERSION_FILE = '/var/lib/cpn/roundcube_version'
ROUNDCUBE_FALLBACK_VERSION = '1.7.3'

LSWS_ROOT = '/usr/local/lsws'
VHOST_DIR = os.path.join(LSWS_ROOT, 'conf', 'vhosts', 'CPNWebmail')
VHOST_CONF = os.path.join(VHOST_DIR, 'vhconf.conf')
BIND_CONF = '/var/lib/cpn/bind.conf'


def _log(msg):
    try:
        print('[roundcubeWebmail] ' + str(msg))
    except Exception:
        pass


def detect_lsphp_version():
    for ver in ('85', '84', '83', '82', '81', '80'):
        path = '/usr/local/lsws/lsphp%s/bin/lsphp' % ver
        if os.path.isfile(path):
            return ver
    return '83'


def get_panel_port():
    try:
        if os.path.isfile(BIND_CONF):
            line = open(BIND_CONF, 'r').read().strip()
            if line.startswith('*:'):
                port = line.split(':', 1)[1].strip().split()[0]
                if port.isdigit():
                    return port
    except Exception:
        pass
    return '2087'
