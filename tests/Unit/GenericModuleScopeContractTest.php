<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GenericModuleScopeContractTest extends TestCase
{
    public function test_module_has_local_identity_but_no_local_scope_declaration(): void
    {
        $config = file_get_contents(
            dirname(__DIR__, 2)
            . '/config/sarionos.php'
        );

        self::assertIsString(
            $config
        );

        self::assertStringContainsString(
            "'module_key' => env("
            . "'SARIONOS_MODULE_KEY', "
            . "'template'"
            . ")",
            $config
        );

        self::assertStringNotContainsString(
            'SARIONOS_MODULE_SCOPE',
            $config
        );

        self::assertStringNotContainsString(
            "'module_scope'",
            $config
        );
    }

    public function test_core_is_the_only_effective_scope_authority(): void
    {
        $root =
            dirname(__DIR__, 2)
            . '/app/Http/Middleware/';

        $load = file_get_contents(
            $root
            . 'LoadWorkspaceContextFromCore.php'
        );

        $enable = file_get_contents(
            $root
            . 'EnsureModuleEnabled.php'
        );

        self::assertIsString($load);
        self::assertIsString($enable);

        self::assertStringContainsString(
            '/api/me/modules',
            $load
        );

        self::assertStringContainsString(
            "'scope_type'",
            $load
        );

        self::assertStringContainsString(
            'Core is the sole authority',
            $load
        );

        self::assertStringContainsString(
            'Core returned an invalid module scope.',
            $load
        );

        self::assertStringNotContainsString(
            'declaredScope',
            $load
        );

        self::assertStringNotContainsString(
            'Module scope declaration does not match Core.',
            $load
        );

        self::assertStringContainsString(
            "'sarionos_modules'",
            $enable
        );
    }

    public function test_workspace_context_is_only_supplemental_for_workspace_scope(): void
    {
        $load = file_get_contents(
            dirname(__DIR__, 2)
            . '/app/Http/Middleware/'
            . 'LoadWorkspaceContextFromCore.php'
        );

        self::assertStringContainsString(
            "\$effectiveScope\n"
            . "                === 'workspace'",
            $load
        );

        self::assertStringContainsString(
            '/api/me/workspaces',
            $load
        );

        self::assertStringContainsString(
            "'/users'",
            str_replace(
                '. $workspaceUuid',
                '',
                $load
            )
        );

        self::assertStringNotContainsString(
            "/api/workspaces/\$workspaceUuid/modules",
            $load
        );

        self::assertStringNotContainsString(
            '/api/me/workspace"',
            $load
        );

        self::assertStringNotContainsString(
            "/api/me/workspace'",
            $load
        );
    }

    public function test_no_module_key_special_case_selects_scope(): void
    {
        $root =
            dirname(__DIR__, 2)
            . '/app/Http/Middleware/';

        $source =
            file_get_contents(
                $root
                . 'LoadWorkspaceContextFromCore.php'
            )
            . file_get_contents(
                $root
                . 'SyncWorkspaceFromCore.php'
            )
            . file_get_contents(
                $root
                . 'EnsureModuleEnabled.php'
            );

        foreach (
            [
                "'vault'",
                "'myspace'",
                "'accounting'",
                "'planning'",
                "'productivity'",
                "'files'",
                "'media'",
            ]
            as $moduleKey
        ) {
            self::assertStringNotContainsString(
                $moduleKey,
                $source
            );
        }
    }
}
