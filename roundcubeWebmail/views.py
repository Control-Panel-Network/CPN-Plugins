# -*- coding: utf-8 -*-
"""Roundcube Webmail admin settings (redirect copy for legacy Django hosts)."""
from django.shortcuts import redirect
from django.http import JsonResponse
from django.views.decorators.http import require_http_methods
from django.views.decorators.csrf import csrf_exempt
from functools import wraps

from . import utils


def panel_login_required(view_func):
    @wraps(view_func)
    def _wrapped_view(request, *args, **kwargs):
        try:
            request.session['userID']
            return view_func(request, *args, **kwargs)
        except KeyError:
            return redirect('/login')
    return _wrapped_view


@panel_login_required
@require_http_methods(["GET"])
def admin_settings(request):
    status = utils.get_status(request)
    try:
        from plogical.httpProc import httpProc
        context = {
            'title': 'Roundcube Webmail',
            'plugin_name': 'Roundcube Webmail',
            'version': '1.1.0',
            'is_paid': False,
            'status': status,
        }
        proc = httpProc(request, 'roundcubeWebmail/admin_settings.html', context, 'managePlugins')
        return proc.render()
    except Exception:
        from django.http import HttpResponse
        body = (
            '<h1>Roundcube Webmail</h1>'
            '<p>%s</p>'
            '<p><a href="/plugins?view=host&amp;category=Email">Open Host packages (Email)</a></p>'
        ) % status.get('message', '')
        return HttpResponse(body)


@panel_login_required
@csrf_exempt
@require_http_methods(["POST"])
def api_toggle(request):
    return JsonResponse({
        'success': False,
        'error': 'Roundcube is managed as a host package. Use Plugins > Host packages (Email).',
    }, status=400)


@panel_login_required
@require_http_methods(["GET"])
def api_status(request):
    return JsonResponse({'success': True, 'status': utils.get_status(request)})
