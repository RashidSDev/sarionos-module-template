<?php

namespace Tests\Unit;

use App\Http\Middleware\LoadWorkspaceContextFromCore;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class WorkspaceModulePresentationPayloadTest extends TestCase
{
    public function test_core_presentation_payload_is_preserved(): void
    {
        $presentation = [
            'group_key' => 'work_areas',
            'display_label' => 'Accounting',
            'short_description' =>
                'Books, transactions, reporting and fiscal compliance',
            'icon' => 'briefcase',
            'accent' => 'indigo',
            'presentation_style' => 'primary',
            'sort_order' => 30,
            'show_in_web' => true,
            'show_in_mobile' => true,
            'group_label' => 'Work Areas',
            'group_sort_order' => 10,
        ];

        $actual = $this->normalize([
            [
                'uuid' => 'accounting-module-uuid',
                'key' => 'accounting',
                'name' => 'Accounting',
                'url' =>
                    'https://accounting.dev.sarionos.com',
                'presentation' => $presentation,
            ],
        ]);

        $this->assertSame(
            [
                [
                    'uuid' => 'accounting-module-uuid',
                    'key' => 'accounting',
                    'name' => 'Accounting',
                    'url' =>
                        'https://accounting.dev.sarionos.com',
                    'presentation' => $presentation,
                ],
            ],
            $actual
        );
    }

    public function test_legacy_module_payload_remains_supported(): void
    {
        $actual = $this->normalize([
            [
                'uuid' => 'legacy-module-uuid',
                'key' => 'legacy',
                'url' => 'https://legacy.dev.sarionos.com',
            ],
        ]);

        $this->assertSame(
            [
                [
                    'uuid' => 'legacy-module-uuid',
                    'key' => 'legacy',
                    'name' => null,
                    'url' => 'https://legacy.dev.sarionos.com',
                    'presentation' => null,
                ],
            ],
            $actual
        );
    }

    public function test_invalid_module_rows_are_removed(): void
    {
        $actual = $this->normalize([
            [
                'uuid' => null,
                'key' => 'missing-uuid',
            ],
            [
                'uuid' => 'missing-key-uuid',
                'key' => null,
            ],
            'invalid-row',
        ]);

        $this->assertSame([], $actual);
    }

    private function normalize(array $modules): array
    {
        $reflection = new ReflectionClass(
            LoadWorkspaceContextFromCore::class
        );

        $middleware = $reflection
            ->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(
            LoadWorkspaceContextFromCore::class,
            'normalizeWorkspaceModules'
        );

        $method->setAccessible(true);

        return $method->invoke(
            $middleware,
            $modules
        );
    }
}
