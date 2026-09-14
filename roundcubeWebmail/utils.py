# -*- coding: utf-8 -*-
"""Roundcube Webmail catalog helpers: redirect operators to the CPN host package."""
from __future__ import print_function

import json
import os
import subprocess

from .utils_paths import (
    SETTINGS_FILE,
    ROUNDCUBE_ROOT,
    ROUNDCUBE_PUBLIC,
    _log,
)

PLUGIN_NAME = 'roundcubeWebmail'


def _default_settings():
    return {
        'enabled': True,
        'install_surface': 'host_packages',
        'host_path': ROUNDCUBE_ROOT,
        'panel_path': '/roundcube/',
        'imap_host': 'localhost:143',
    }


def load_settings():
    settings = _default_settings()
    try:
        if os.path.isfile(SETTINGS_FILE):
            with open(SETTINGS_FILE, 'r', encoding='utf-8') as handle:
                raw = json.load(handle)
            if isinstance(raw, dict):
                settings.update(raw)
    except Exception as exc:
        _log('WARNING: could not read settings: %s' % exc)
    return settings


def save_settings(settings):
    os.makedirs(os.path.dirname(SETTINGS_FILE), exist_ok=True)
    payload = _default_settings()
    if isinstance(settings, dict):
        payload.update(settings)
    with open(SETTINGS_FILE, 'w', encoding='utf-8') as handle:
        json.dump(payload, handle, indent=2, sort_keys=True)
        handle.write('\n')
    try:
        os.chmod(SETTINGS_FILE, 0o600)
    except Exception:
        pass
    return payload


def is_installed():
    return (
        os.path.isfile(os.path.join(ROUNDCUBE_PUBLIC, 'index.php'))
        or os.path.isfile(os.path.join(ROUNDCUBE_ROOT, 'index.php'))
    )


def status_message():
    if is_installed():
        return 'Roundcube host package present under %s (panel proxy /roundcube/).' % ROUNDCUBE_ROOT
    return (
        'Roundcube is not installed. Use Plugins > Host packages (Email) or: '
        'cpn app install --name roundcube'
    )


def get_status(request=None):
    _ = request
    installed = is_installed()
    return {
        'enabled': True,
        'installed': installed,
        'install_surface': 'host_packages',
        'path': ROUNDCUBE_ROOT if installed else '',
        'panel_url': '/roundcube/' if installed else '/plugins?view=host&category=Email',
        'message': status_message(),
        'imap_host': 'localhost:143',
    }


def set_enabled(enabled):
    settings = load_settings()
    settings['enabled'] = bool(enabled)
    save_settings(settings)
    return settings


def post_install_tasks():
    """Thin wrapper: prefer CPN host package install over any site-plugin deploy."""
    _log(status_message())
    if is_installed():
        save_settings(load_settings())
        return True, status_message()
    if not _which('cpn'):
        return False, (
            'Roundcube is a CPN Email host package. Install from Plugins > Host packages '
            '(Email), or run: cpn app install --name roundcube'
        )
    try:
        proc = subprocess.run(
            ['cpn', 'app', 'install', '--name', 'roundcube'],
            capture_output=True,
            text=True,
            timeout=600,
        )
    except Exception as exc:
        return False, 'Could not run cpn app install --name roundcube: %s' % exc
    if proc.returncode != 0:
        err = (proc.stderr or proc.stdout or '').strip() or 'unknown error'
        return False, 'Host package install failed: %s' % err
    save_settings(load_settings())
    return True, 'Installed Roundcube via host package under %s' % ROUNDCUBE_ROOT


def _which(cmd):
    from shutil import which
    return which(cmd) is not None
