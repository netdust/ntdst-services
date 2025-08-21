<?php

return [
    'security-section' => [
        'name'        => __( 'Security' ),
        'groups' => [
            'login-group' => [
                'name'        => __( 'Login Security Settings', app()->text_domain ),
                'description' => __('Securing the login page.', app()->text_domain ),
                'fields'      => [
                    [
                        'name' => __('Enable Login Limit', app()->text_domain),
                        'slug' => 'enable_login_limit',
                        'default' => true,
                        'addons' => [
                            'label' => __('Block users when after X login attemps', app()->text_domain),
                        ],
                        'description' => __(
                            'Put a stop to hackers trying to randomly guess your login credentials. Defender will lock out users after a set number of failed login attempts.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'sanitize'],
                    ],
                    [
                        'name' => __('Login Treshold', app()->text_domain),
                        'slug' => 'login_treshold',
                        'default' => 5,
                        'addons' => [
                            'label' => __('Allowed failed login attempts', app()->text_domain),
                        ],
                        'description' => __(
                            'Specify how many failed login attempts within a specific time period will trigger a lockout.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Number(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Number(), 'sanitize'],
                    ],
                    [
                        'name' => __('Time limit', app()->text_domain),
                        'slug' => 'time_limit',
                        'default' => 30,
                        'addons' => [
                            'label' => __('Time interval a user has to login', app()->text_domain),
                        ],
                        'description' => __(
                            'If you user do fail login x times in x minutes then system will block the user for x minutes',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Number(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Number(), 'sanitize'],
                    ],
                ],
            ],
            'login-mask-group' => [
                'name'        => __( 'Mask Login Area', app()->text_domain ),
                'description' => __('Change your default WordPress login URL to hide your login area from hackers and bots.', app()->text_domain ),
                'collapsed'   => true,
                'fields'      => [
                    [
                        'name' => __('Enable masking', app()->text_domain),
                        'slug' => 'enable_mask_area',
                        'default' => false,
                        'addons' => [
                            'label' => __('Enable login area masking', app()->text_domain),
                        ],
                        'description' => __(
                            'Enable login area masking and bypass to the default wp-admin and wp-login URLS.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'sanitize'],
                    ],
                    [
                        'name' => __('Masking URL slug', app()->text_domain),
                        'slug' => 'masking_url_slug',
                        'default' => '',
                        'addons' => [
                            'label' => __('Choose the new URL slug', app()->text_domain),
                        ],
                        'description' => __(
                            'Replace the default wp-admin or wp-login with a custom slug below. For security reasons, less obvious URLs are recommended as they are harder for bots to guess.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Text(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Text(), 'sanitize'],
                    ],
                    [
                        'name' => __('Redirect traffic', app()->text_domain),
                        'slug' => 'redirect_traffic',
                        'default' => '',
                        'addons' => [
                            'label' => __('URL to redirect to', app()->text_domain),
                        ],
                        'description' => __(
                            'Send visitors and bots who try to visit the default WordPress login URLs to a separate URL to avoid 404s.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Number(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Number(), 'sanitize'],
                    ]
                ],
            ],
            'hardening-group' => [
                'name'        => __( 'Hardening', app()->text_domain ),
                'description' => __('Make WordPress harder to break into.', app()->text_domain ),
                'collapsed'   => true,
                'fields'      => [
                    [
                        'name' => __('Disable XML_RPC', app()->text_domain),
                        'slug' => 'disable_xmlrpc',
                        'default' => true,
                        'addons' => [
                            'label' => __('Most websites don\'t need XMLRPC', app()->text_domain),
                        ],
                        'description' => __(
                            'Vulnerability in XML-RPC allows an attacker to make a system call, which can be dangerous for the application and servers',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'sanitize'],
                    ],

                    [
                        'name' => __('Disable API User', app()->text_domain),
                        'slug' => 'disable_api_user',
                        'default' => true,
                        'addons' => [
                            'label' => __('Stop REST API user calls', app()->text_domain),
                        ],
                        'description' => __(
                            'Restricts REST API calls to find users. Only requests by logged-in users will be allowed.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'sanitize'],
                    ],
                    [
                        'name' => __('Disable oEmbed calls', app()->text_domain),
                        'slug' => 'disable_oembed_calls',
                        'default' => true,
                        'addons' => [
                            'label' => __('Stop oEmbed calls revealing user IDs', app()->text_domain),
                        ],
                        'description' => __(
                            'Excludes Author Archives links from oEmbed calls which expose the user ID by default.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'sanitize'],
                    ],
                    [
                        'name' => __('Disable author sitemaps', app()->text_domain),
                        'slug' => 'disable_author_sitemaps',
                        'default' => true,
                        'addons' => [
                            'label' => __('Hide user IDs for built in content types', app()->text_domain),
                        ],
                        'description' => __(
                            'Disables sitemaps for built-in content types like Pages and Author Archives which expose user ID by default.',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'sanitize'],
                    ],
                    [
                        'name' => __('Disable trackbacks and pingbacks', app()->text_domain),
                        'slug' => 'disable_restpoint_query',
                        'default' => false,
                        'addons' => [
                            'label' => __('Protect against DDOS attacks that slow down the website', app()->text_domain),
                        ],
                        'description' => __(
                            'These notifications can be sent to any website willing to receive them, opening you up to DDoS attacks',
                            app()->text_domain
                        ),
                        'render' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'input'],
                        'sanitize' => [new Netdust\Services\Settings\CoreFields\Checkbox(), 'sanitize'],
                    ]
                ]
            ]
        ]

    ],
];