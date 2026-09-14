# -*- coding: utf-8 -*-
"""OLS helpers retired: Roundcube host package is wired by CPN panel webmail runtime."""
from __future__ import print_function

from .utils_paths import _log


def ensure_roundcube_context():
    _log('OLS context is managed by CPN host package / panel proxy (/roundcube/).')
    return True, 'Use Plugins > Host packages (Email) or cpn app install --name roundcube'


def remove_roundcube_context():
    _log('OLS context removal is managed by CPN host package uninstall.')
    return True, 'Use Plugins > Host packages Uninstall or: cpn app uninstall --name roundcube'
