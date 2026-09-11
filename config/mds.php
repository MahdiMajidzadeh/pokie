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

    'currency' => 'none',

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

        // <mds:icon> renders flux:icon (heroicons) for a name no Hugeicons
        // source has. Set true to render nothing instead — use this to keep
        // heroicons out of pages that only ever call <mds:icon>.
        'strict' => false,

        // Pro styles: style name => directory of .svg files exported from
        // your Hugeicons licence. Nothing here is shipped with this package.
        //
        //   'solid-rounded' => resource_path('svg/hugeicons/solid-rounded'),
        //
        'sets' => [],

    ],

];
