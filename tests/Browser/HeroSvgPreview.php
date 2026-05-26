<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Route;

it('captures each candidate SVG by embedding it in a minimal HTML wrapper', function () {
    foreach (['hero-orchard-canopy', 'hero-tree-with-basket', 'hero-mangoes'] as $name) {
        $svg = file_get_contents(public_path("images/{$name}.svg"));
        // Stash the SVG as a session value the route below can read
        Route::get("/__preview/{$name}", function () use ($svg) {
            $bg = '#fef3c7'; // amber-50-ish

            return response("<!doctype html><html><body style='margin:0;background:{$bg};display:flex;align-items:center;justify-content:center;min-height:100vh'><div style='width:900px;height:700px'>{$svg}</div></body></html>");
        })->name("preview.{$name}");

        visit("/__preview/{$name}")
            ->on()->desktop()
            ->screenshot(filename: "preview_{$name}");
    }
});
