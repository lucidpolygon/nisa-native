<?php

return [
    'types' => [
        'airtable' => [
            'label' => 'Airtable',
            'fields' => [
                ['name' => 'pat', 'label' => 'Personal Access Token', 'type' => 'password', 'required' => true],
            ],
        ],

        'tape' => [
            'label' => 'Tape',
            'fields' => [
                ['name' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
            ],
        ],

        'podio' => [
            'label' => 'Podio',
            'fields' => [
                ['name' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
            ],
        ],

        'notion' => [
            'label' => 'Notion',
            'fields' => [
                ['name' => 'token', 'label' => 'Internal Integration Token', 'type' => 'password', 'required' => true],
            ],
        ],

        'oauth2' => [
            'label' => 'OAuth2 Service',
            'fields' => [
                ['name' => 'service_name', 'label' => 'Service Name', 'type' => 'text', 'required' => true],
                ['name' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
                ['name' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
                ['name' => 'authorize_url', 'label' => 'Authorize URL', 'type' => 'url', 'required' => true],
                ['name' => 'scopes', 'label' => 'Scopes (Optional)', 'type' => 'text', 'required' => false],
                ['name' => 'access_token_url', 'label' => 'Access Token URL', 'type' => 'url', 'required' => true],
                ['name' => 'resource_owner_details_url', 'label' => 'Resource Owner Details URL', 'type' => 'url', 'required' => true],
                ['name' => 'result_webhook_url', 'label' => 'Result Webhook URL', 'type' => 'url', 'required' => true],
                ['name' => 'redirect_success_url', 'label' => 'Redirect Success URL', 'type' => 'url', 'required' => true],
                ['name' => 'redirect_failure_url', 'label' => 'Redirect Failure URL', 'type' => 'url', 'required' => true],
            ],
        ],

        'smtp' => [
            'label' => 'SMTP',
            'fields' => [
                ['name' => 'encryption', 'label' => 'Encryption (SSL/TLS)', 'type' => 'select', 'options' => ['ssl', 'tls'], 'required' => true],
                ['name' => 'server', 'label' => 'Server', 'type' => 'text', 'required' => true],
                ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
                ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
                ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'required' => true],
            ],
        ],

        'mysql' => [
            'label' => 'MySQL Database',
            'fields' => [
                ['name' => 'host', 'label' => 'Host', 'type' => 'text', 'required' => true],
                ['name' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
                ['name' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
                ['name' => 'database', 'label' => 'Database Name', 'type' => 'text', 'required' => true],
                ['name' => 'port', 'label' => 'Port', 'type' => 'number', 'required' => true],
            ],
        ],
    ],
];
