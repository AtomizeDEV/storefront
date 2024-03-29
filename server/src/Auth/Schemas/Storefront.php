<?php

namespace Fleetbase\Storefront\Auth\Schemas;

class Storefront
{
    /**
     * The permission schema Name.
     */
    public string $name = 'storefront';

    /**
     * The permission schema Polict Name.
     */
    public string $policyName = 'Storefront';

    /**
     * Guards these permissions should apply to.
     */
    public array $guards = ['web', 'api'];

    /**
     * The permission schema resources.
     */
    public array $resources = [
        [
            'name'    => 'order',
            'actions' => ['accept', 'mark-as-ready', 'mark-as-completed', 'reject'],
        ],
        [
            'name'    => 'product',
            'actions' => ['import'],
        ],
        [
            'name'    => 'product-addon',
            'actions' => [],
        ],
        [
            'name'    => 'product-addon-category',
            'actions' => [],
        ],
        [
            'name'    => 'product-hour',
            'actions' => [],
        ],
        [
            'name'    => 'product-store-location',
            'actions' => [],
        ],
        [
            'name'    => 'product-variant',
            'actions' => [],
        ],
        [
            'name'    => 'product-variant-option',
            'actions' => [],
        ],
        [
            'name'    => 'gateway',
            'actions' => [],
        ],
        [
            'name'    => 'notification-channel',
            'actions' => [],
        ],
        [
            'name'    => 'network',
            'actions' => [],
        ],
        [
            'name'    => 'network-store',
            'actions' => [],
        ],
        [
            'name'    => 'store',
            'actions' => [],
        ],
        [
            'name'    => 'store-location',
            'actions' => [],
        ],
        [
            'name'    => 'store-hour',
            'actions' => [],
        ],
    ];
}
