<?php

declare(strict_types=1);

/**
 * Stressless load tests for the highest-traffic public read paths.
 *
 * Run against the live Sail container — these fire real HTTP through k6, not
 * Laravel's test kernel. Default base URL is the in-container Apache on port
 * 80; override with `STRESS_BASE_URL=http://app.test` if you're targeting a
 * remote env.
 *
 * Invoke:
 *
 *   sail composer stress              # full Stress suite
 *   sail composer stress -- --filter=marketplace
 *
 * NOT part of the default `sail bin pest` run — these are slow and stomp on
 * the dev DB. CI should run them in a dedicated job, not on every PR.
 */

use function Pest\Stressless\stress;

$baseUrl = rtrim((string) (getenv('STRESS_BASE_URL') ?: 'http://localhost'), '/');

it('keeps the landing page responsive under 10 concurrent users for 5s', function () use ($baseUrl) {
    $result = stress($baseUrl.'/')
        ->concurrently(requests: 10)
        ->for(5)->seconds();

    expect($result->requests()->duration()->med())->toBeLessThan(500);
    expect($result->requests()->failed()->count())->toBe(0);
})->group('stress', 'landing');

it('keeps the marketplace listing index responsive under 10 concurrent users for 5s', function () use ($baseUrl) {
    $result = stress($baseUrl.'/listings')
        ->concurrently(requests: 10)
        ->for(5)->seconds();

    expect($result->requests()->duration()->med())->toBeLessThan(500);
    expect($result->requests()->failed()->count())->toBe(0);
})->group('stress', 'marketplace');

it('keeps the variety catalogue responsive under 10 concurrent users for 5s', function () use ($baseUrl) {
    $result = stress($baseUrl.'/varieties')
        ->concurrently(requests: 10)
        ->for(5)->seconds();

    expect($result->requests()->duration()->med())->toBeLessThan(500);
    expect($result->requests()->failed()->count())->toBe(0);
})->group('stress', 'varieties');
