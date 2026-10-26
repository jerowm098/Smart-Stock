<?php

/*
|--------------------------------------------------------------------------
| Hardware vocabulary — Filipino / Tagalog → English
|--------------------------------------------------------------------------
|
| Store staff often type product names in Filipino ("pako", "martilyo",
| "turnilyo"). The product NAME is kept exactly as typed, but everything the
| system derives from it — the auto-generated SKU prefix and the suggested
| category — is built from the English meaning, so codes and categories stay
| consistent with the rest of the catalogue.
|
| Keys are matched case-insensitively on whole words (multi-word phrases are
| supported and always win over shorter terms). Values must be lowercase
| English words.
|
*/

return [

    'translations' => [
        // Fasteners
        'pako' => 'nail',
        'turnilyo' => 'screw',
        'tornilyo' => 'screw',
        'perno' => 'bolt',
        'tuerca' => 'nut',
        'pala-pako' => 'claw hammer',

        // Hand / power tools
        'martilyo' => 'hammer',
        'lagari' => 'saw',
        'barena' => 'drill',
        'bareno' => 'drill',
        'destornilyador' => 'screwdriver',
        'distornilyador' => 'screwdriver',
        'distilyador' => 'screwdriver',
        'liyabe' => 'wrench',
        'liyabe de tubo' => 'pipe wrench',
        'maso' => 'mallet',
        'palakol' => 'axe',
        'pala' => 'shovel',
        'asarol' => 'hoe',
        'kalaykay' => 'rake',
        'kutsilyo' => 'knife',
        'gunting' => 'scissors',
        'panghasa' => 'sharpener',

        // Plumbing
        'tubo' => 'pipe',
        'gripo' => 'faucet',
        'balbula' => 'valve',
        'kabo' => 'coupling',
        'siko' => 'elbow',

        // Electrical / lighting
        'kawad' => 'wire',
        'alambre' => 'wire',
        'kable' => 'cable',
        'saksakan' => 'outlet',
        'suwits' => 'switch',
        'kuryente' => 'electric',
        'bombilya' => 'bulb',
        'bumbilya' => 'bulb',
        'ilaw' => 'light',
        'baterya' => 'battery',
        'susi' => 'key',

        // Painting / adhesives / building
        'pintura' => 'paint',
        'pintor' => 'painter',
        'liha' => 'sandpaper',
        'papel de liha' => 'sandpaper',
        'pandikit' => 'glue',
        'kola' => 'glue',
        'rugby' => 'glue',
        'semento' => 'cement',
        'kahoy' => 'wood',
        'bakal' => 'steel',
        'asero' => 'steel',
        'tanso' => 'copper',
        'yero' => 'sheet',
        'plastik' => 'plastic',
        'goma' => 'rubber',

        // Hardware / general
        'kadena' => 'chain',
        'kandado' => 'padlock',
        'seradura' => 'lock',
        'bisagra' => 'hinge',
        'pinto' => 'door',
        'bintana' => 'window',
        'salamin' => 'glass',
        'lubid' => 'rope',
        'pisi' => 'string',
        'kandila' => 'candle',
        'posporo' => 'match',
        'walis' => 'broom',
        'walis tambo' => 'broom',
        'balde' => 'bucket',
        'timba' => 'bucket',
        'basahan' => 'rag',
        'espongha' => 'sponge',
        'sabon' => 'soap',
        'langis' => 'oil',
        'grasa' => 'grease',
        'plantsa' => 'iron',
        'bentilador' => 'fan',

        // Units and quantity words — dropped from the English form.
        'mga' => '',
        'pulgada' => 'inch',
        'talampakan' => 'foot',
        'sentimetro' => 'centimeter',
        'kilogramo' => 'kilogram',
        'piraso' => 'piece',
        'dosena' => 'dozen',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy category spellings → canonical catalogue category
    |--------------------------------------------------------------------------
    |
    | The seed data contains a few one-off category names ("Nail", "Piping").
    | They collapse onto the standard names so suggestions never fragment the
    | catalogue into a dozen near-duplicate categories.
    |
    */

    'category_aliases' => [
        'nail' => 'Fasteners',
        'nails' => 'Fasteners',
        'screw' => 'Fasteners',
        'screws' => 'Fasteners',
        'bolt' => 'Fasteners',
        'bolts' => 'Fasteners',
        'fastener' => 'Fasteners',
        'hand tool' => 'Hand Tools',
        'handtools' => 'Hand Tools',
        'power tool' => 'Power Tools',
        'powertools' => 'Power Tools',
        'electricals' => 'Electrical',
        'electronic' => 'Electronics',
        'paint' => 'Painting',
        'paints' => 'Painting',
        'measuring tools' => 'Measuring',
        'measurement' => 'Measuring',
        'safety gear' => 'Safety',
        'ppe' => 'Safety',
        'pipe' => 'Plumbing',
        'plumbing supplies' => 'Plumbing',
        'piping' => 'Plumbing',
        'adhesive' => 'Adhesives',
        'lumber and wood' => 'Lumber',
        'building material' => 'Building Materials',
        'lighting' => 'Lighting',
        'uncategorized' => 'Uncategorized',
    ],

    /*
    |--------------------------------------------------------------------------
    | English keyword → catalogue category
    |--------------------------------------------------------------------------
    |
    | Matched against the TRANSLATED product name, longest keyword first, so
    | "screwdriver" wins over "screw" and "pipe wrench" wins over "wrench".
    | Category names mirror the ones already used by the catalogue.
    |
    */

    'categories' => [
        'pipe wrench' => 'Plumbing',
        'extension cord' => 'Electrical',
        'paint brush' => 'Painting',
        'paint roller' => 'Painting',
        'tape measure' => 'Measuring',
        'utility knife' => 'Hand Tools',
        'circular saw' => 'Power Tools',
        'screwdriver' => 'Hand Tools',
        'sandpaper' => 'Painting',
        'paintbrush' => 'Painting',
        'flashlight' => 'Electronics',

        'nail' => 'Fasteners',
        'screw' => 'Fasteners',
        'bolt' => 'Fasteners',
        'nut' => 'Fasteners',
        'washer' => 'Fasteners',
        'anchor' => 'Fasteners',
        'rivet' => 'Fasteners',
        'staple' => 'Fasteners',

        'hammer' => 'Hand Tools',
        'wrench' => 'Hand Tools',
        'plier' => 'Hand Tools',
        'chisel' => 'Hand Tools',
        'crowbar' => 'Hand Tools',
        'clamp' => 'Hand Tools',
        'vise' => 'Hand Tools',
        'saw' => 'Hand Tools',
        'axe' => 'Hand Tools',
        'shovel' => 'Hand Tools',
        'hoe' => 'Hand Tools',
        'rake' => 'Hand Tools',
        'trowel' => 'Hand Tools',
        'knife' => 'Hand Tools',
        'scissors' => 'Hand Tools',

        'drill' => 'Power Tools',
        'grinder' => 'Power Tools',
        'sander' => 'Power Tools',
        'polisher' => 'Power Tools',
        'router' => 'Power Tools',
        'welder' => 'Power Tools',
        'compressor' => 'Power Tools',

        'wire' => 'Electrical',
        'cable' => 'Electrical',
        'cord' => 'Electrical',
        'switch' => 'Electrical',
        'outlet' => 'Electrical',
        'socket' => 'Electrical',
        'breaker' => 'Electrical',
        'fuse' => 'Electrical',
        'plug' => 'Electrical',
        'conduit' => 'Electrical',
        'voltage' => 'Electrical',

        'bulb' => 'Lighting',
        'lamp' => 'Lighting',
        'led' => 'Lighting',
        'light' => 'Lighting',
        'fluorescent' => 'Lighting',

        'battery' => 'Electronics',
        'charger' => 'Electronics',
        'radio' => 'Electronics',

        'faucet' => 'Plumbing',
        'valve' => 'Plumbing',
        'elbow' => 'Plumbing',
        'coupling' => 'Plumbing',
        'trap' => 'Plumbing',
        'flange' => 'Plumbing',
        'toilet' => 'Plumbing',
        'shower' => 'Plumbing',
        'hose' => 'Plumbing',
        'fitting' => 'Plumbing',
        'pipe' => 'Plumbing',
        'pvc' => 'Plumbing',
        'gutter' => 'Plumbing',

        'paint' => 'Painting',
        'brush' => 'Painting',
        'roller' => 'Painting',
        'thinner' => 'Painting',
        'primer' => 'Painting',
        'varnish' => 'Painting',

        'ruler' => 'Measuring',
        'caliper' => 'Measuring',
        'square' => 'Measuring',
        'level' => 'Measuring',
        'measure' => 'Measuring',
        'gauge' => 'Measuring',

        'glove' => 'Safety',
        'goggles' => 'Safety',
        'helmet' => 'Safety',
        'mask' => 'Safety',
        'vest' => 'Safety',
        'harness' => 'Safety',
        'earplug' => 'Safety',
        'boots' => 'Safety',

        'glue' => 'Adhesives',
        'adhesive' => 'Adhesives',
        'sealant' => 'Adhesives',
        'epoxy' => 'Adhesives',

        'cement' => 'Building Materials',
        'steel' => 'Building Materials',
        'sheet' => 'Building Materials',
        'wood' => 'Lumber',
        'lumber' => 'Lumber',
    ],

];
