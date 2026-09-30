(function (config) {
    'use strict';
    var c = window.console;
    var marker = '__divaConsolePrivacyActive';
    if (!c || window[marker]) { return; }
    window[marker] = true;

    // Print exactly once; never clear the console or poll it.
    if (config.greeting && typeof c.log === 'function') {
        c.log('%c%s%c\n%s',
            'background:' + config.background + ';color:' + config.color + ';padding:12px 22px;border-radius:8px;font-size:22px;font-weight:bold;',
            config.title, 'color:' + config.color + ';font-size:13px;line-height:1.8;', config.message);
    }
    var noop = function () {};
    var methods = ['log', 'info', 'debug', 'warn', 'error', 'trace', 'dir', 'dirxml',
        'table', 'assert', 'group', 'groupCollapsed', 'groupEnd', 'count', 'countReset',
        'time', 'timeLog', 'timeEnd', 'clear', 'profile', 'profileEnd', 'timeStamp'];
    if (config.quiet) { methods.forEach(function (name) {
        if (typeof c[name] === 'function') {
            try { c[name] = noop; } catch (ignored) { /* Read-only browser methods. */ }
        }
    }); }
    // Cancel default reporting only: other listeners/monitoring still receive events.
    if (config.errors) {
    window.addEventListener('error', function (event) {
        if (event.target === window && event.cancelable) { event.preventDefault(); }
    });
    window.addEventListener('unhandledrejection', function (event) {
        if (event.cancelable) { event.preventDefault(); }
    });
    }
}(/*DIVA_CONFIG*/{}));
