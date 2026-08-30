<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | Used by the <mds:price> component, the @toman/@rial directives, and
    | Persian::money() when no explicit currency is given. Supported values
    | are "toman", "rial", "none", or any literal label (e.g. "درهم").
    |
    */

    // Note: the special "toman"/"rial" keywords always render the Persian
    // word regardless of persian_digits (mahdimajidzadeh/ds v0.3.0's
    // Persian::currencyLabel() doesn't take an fa param, unlike its own
    // docs). Pokie stays English, so this is the literal label instead.
    'currency' => 'Toman',

    /*
    |--------------------------------------------------------------------------
    | Persian Digits
    |--------------------------------------------------------------------------
    |
    | When enabled, numeric output in components (prices, ratings, counters,
    | Jalali dates) is rendered with Persian digits (۰۱۲۳...). Individual
    | components can override this with their :fa="..." prop.
    |
    */

    'persian_digits' => false,

    /*
    |--------------------------------------------------------------------------
    | Icons
    |--------------------------------------------------------------------------
    |
    | <mds:icon> and every mds component's `icon` prop render Hugeicons
    | (hugeicons.com). The free Stroke Rounded set ships with the
    | afatmustafa/blade-hugeicons dependency; the other eight styles are Pro
    | and are never bundled — register your own licensed export under `sets`.
    |
    | Set "default" to "flux" to go back to Flux's heroicons everywhere.
    |
    */

    'icons' => [

        'default' => 'hugeicons',

        // Style used when a component doesn't ask for one.
        'style' => 'stroke-rounded',

        // Fall back to the free Stroke Rounded set when a requested Pro
        // style isn't registered. Set false to render nothing instead.
        'fallback_style' => true,

        // Pro styles: style name => directory of .svg files exported from
        // your Hugeicons licence. Nothing here is shipped with this package.
        //
        //   'solid-rounded' => resource_path('svg/hugeicons/solid-rounded'),
        //
        'sets' => [],

    ],

];
