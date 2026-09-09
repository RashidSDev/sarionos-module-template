<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GenericScaffoldNamingContractTest extends TestCase
{
    public function test_template_specific_route_names_are_removed(): void
    {
        $root = dirname(__DIR__, 2);

        $files = [
            $root . '/routes/web.php',
            $root . '/app/Http/Controllers/ModuleSystemCheckController.php',
            $root . '/app/Http/Controllers/Admin/AccessRouteRegistryController.php',
            $root . '/resources/views/dashboard.blade.php',
            $root . '/resources/views/admin/access-routes/index.blade.php',
            $root . '/resources/views/admin/system-check.blade.php',
        ];

        $source = '';

        foreach ($files as $file) {
            $value = file_get_contents(
                $file
            );

            self::assertIsString(
                $value
            );

            $source .= $value;
        }

        self::assertStringNotContainsString(
            'template' . '.admin',
            $source
        );

        self::assertStringContainsString(
            'module.admin.system-check',
            $source
        );

        self::assertStringContainsString(
            'module.admin.access-routes.index',
            $source
        );
    }

    public function test_session_cookie_default_is_derived_from_module_identity(): void
    {
        $root = dirname(__DIR__, 2);

        $session = file_get_contents(
            $root . '/config/session.php'
        );

        self::assertIsString(
            $session
        );

        self::assertStringNotContainsString(
            'sarionos_' . 'template_session',
            $session
        );

        self::assertStringContainsString(
            'SARIONOS_MODULE_KEY',
            $session
        );

        self::assertStringContainsString(
            "'sarionos_'",
            $session
        );

        self::assertStringContainsString(
            "'_session'",
            $session
        );
    }

    public function test_logout_forgets_the_configured_session_cookie(): void
    {
        $root = dirname(__DIR__, 2);

        $routes = file_get_contents(
            $root . '/routes/web.php'
        );

        self::assertIsString(
            $routes
        );

        self::assertStringContainsString(
            "config('session.cookie')",
            $routes
        );

        self::assertStringNotContainsString(
            'sarionos_' . 'template_session',
            $routes
        );
    }

    public function test_user_facing_template_specific_wording_is_removed(): void
    {
        $root = dirname(__DIR__, 2);

        $files = [
            $root . '/routes/web.php',
            $root . '/resources/views/admin/access-routes/index.blade.php',
            $root . '/resources/views/errors/403.blade.php',
        ];

        $source = '';

        foreach ($files as $file) {
            $value = file_get_contents(
                $file
            );

            self::assertIsString(
                $value
            );

            $source .= $value;
        }

        foreach (
            [
                'MODULE ' . 'TEMPLATE',
                'Module ' . 'Template',
                'Local Module ' . 'Template',
                'Template ' . 'area',
                'current ' . 'workspace',
            ]
            as $forbidden
        ) {
            self::assertStringNotContainsString(
                $forbidden,
                $source
            );
        }
    }
}
