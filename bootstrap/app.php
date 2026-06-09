<?php

use App\Http\Middleware\EnforceReadonlyMode;
use App\Http\Middleware\EnsureOnboardingComplete;
use App\Http\Middleware\HonorCookieConsent;
use App\Http\Middleware\RequireCookieConsent;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Behind a TLS-terminating reverse proxy the app receives plain HTTP
        // with the original scheme in X-Forwarded-Proto. Trust the proxy's
        // forwarded headers so url()/route()/asset() honour https and Laravel
        // doesn't generate mixed-content http:// links. `at: '*'` trusts any
        // upstream — correct when the app is only ever reachable through the
        // proxy (e.g. Docker network / not publicly bound).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        // Gate gated features behind a cookie-consent choice. Runs BEFORE
        // EnsureOnboardingComplete so an un-consented visitor sees the
        // consent explainer rather than being bounced through onboarding.
        // Also raised in the priority list above `Authenticate` so that
        // routes which declare `auth` via HasMiddleware (e.g. DashboardController)
        // still hit the consent gate first instead of redirecting to /login.
        $middleware->appendToGroup('web', RequireCookieConsent::class);
        $middleware->appendToGroup('web', EnforceReadonlyMode::class);
        $middleware->appendToGroup('web', EnsureOnboardingComplete::class);
        $middleware->prependToPriorityList(
            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            RequireCookieConsent::class,
        );

        // `cookie_consent` and `theme_preference` are both set client-side
        // by JS — the cookie banner and the theme switcher — so they
        // arrive unencrypted. Tell Laravel not to try decrypting them.
        $middleware->encryptCookies(except: ['cookie_consent', 'theme_preference']);

        // Strip session + XSRF cookies from responses to guest visitors who
        // haven't yet clicked the cookie banner. Prepended so it runs LAST
        // in the response phase — sees Set-Cookie headers added by StartSession
        // and friends, removes the ones the visitor hasn't opted into.
        $middleware->prependToGroup('web', HonorCookieConsent::class);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
