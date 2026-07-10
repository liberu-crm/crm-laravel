<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| App-panel permission catalog + system-role matrix
|--------------------------------------------------------------------------
|
| Single source of truth for App\Support\PermissionCatalog::sync(), which both
| the PermissionsSeeder (fresh installs) and the seed-role-permissions migration
| (existing deploys) call. Permission names are `{action}_{resource}` in
| snake_case; `resource` matches the token the EnforcesResourcePermissions trait
| derives per resource (Str::snake of the model, with a couple of clean-token
| overrides — see the trait). `super_admin` is omnipotent via shield's Gate::before
| bypass and also holds every permission here; `customer` is portal-only.
|
*/

return [
    // CRUD verbs minted per resource. `view` gates list + record read,
    // `delete` gates single + bulk delete.
    'actions' => ['view', 'create', 'update', 'delete'],

    // Resource tokens grouped for the matrix below.
    'groups' => [
        'core' => [
            'contact', 'deal', 'lead', 'company', 'opportunity',
            'task', 'note', 'activation', 'message',
        ],
        'advertising' => [
            'advertising_account', 'campaign', 'ad_set', 'ad',
            'marketing_campaign', 'mailchimp_campaign', 'social_media_post',
            'landing_page', 'form_builder', 'workflow',
            'dashboard_widget', 'call_setting', 'whatsapp_number',
        ],
        'settings' => [
            'team_member', 'team_role', 'sso_connection', 'saml_connection',
            'oauth_configuration', 'webhook_delivery', 'portal_branding', 'territory',
        ],
        'logs' => [
            'audit_log', 'portal_access_log', 'team_role_log',
        ],
        // Staff-authoring resources: minted into the catalog but granted only
        // per-role (no group-wide grant), so e.g. sales_rep doesn't get them.
        'staff' => [
            'knowledge_base_article',
        ],
    ],

    // Per-role grants. `groups` maps a group => the verbs granted on every
    // resource in it; `resources` grants verbs on individual resources (used for
    // the manager's territory + knowledge_base exceptions inside the otherwise
    // withheld settings group). super_admin is handled in code (all permissions).
    'matrix' => [
        'admin' => [
            'groups' => [
                'core' => ['view', 'create', 'update', 'delete'],
                'advertising' => ['view', 'create', 'update', 'delete'],
                'settings' => ['view', 'create', 'update', 'delete'],
                'logs' => ['view'],
            ],
            // knowledge_base is staff-authoring (not part of a group): admin +
            // manager only, matching its historical [admin, manager] gate.
            'resources' => [
                'knowledge_base_article' => ['view', 'create', 'update', 'delete'],
            ],
        ],
        'manager' => [
            'groups' => [
                'core' => ['view', 'create', 'update', 'delete'],
                'advertising' => ['view', 'create', 'update', 'delete'],
            ],
            // territory + knowledge_base CRUD (knowledge_base is already in the
            // advertising group); portal_access_log view only — the audit_log /
            // team_role_log gates historically excluded manager, so it does NOT
            // get those.
            'resources' => [
                'territory' => ['view', 'create', 'update', 'delete'],
                'knowledge_base_article' => ['view', 'create', 'update', 'delete'],
                'portal_access_log' => ['view'],
            ],
        ],
        'sales_rep' => [
            'groups' => [
                'core' => ['view', 'create', 'update', 'delete'],
                'advertising' => ['view'],
            ],
        ],
        // free is a limited editor of core records: it can view/create/update
        // (sensitive money/PII stays masked via MasksFields), but not delete.
        'free' => [
            'groups' => [
                'core' => ['view', 'create', 'update'],
            ],
        ],
    ],
];
