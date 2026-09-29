<?php

namespace Tests\Unit;

use Tests\TestCase;

class CoreIdentityAuthorityContractTest extends TestCase
{
    public function test_template_uses_null_framework_guard_without_local_user_authority(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Models/User.php')
        );

        $this->assertFileDoesNotExist(
            database_path('factories/UserFactory.php')
        );

        $this->assertSame(
            'web',
            config('auth.defaults.guard')
        );

        $this->assertNull(
            config('auth.defaults.passwords')
        );

        $this->assertSame([
            'web' => [
                'driver' => 'session',
                'provider' => 'sarionos_null_users',
            ],
        ], config('auth.guards'));

        $this->assertSame([
            'sarionos_null_users' => [
                'driver' => 'sarionos_null',
            ],
        ], config('auth.providers'));

        $this->assertSame([], config('auth.passwords'));

        $this->assertNull(
            auth()->guard('web')->user()
        );
    }

    public function test_template_seeder_does_not_create_local_users(): void
    {
        $seeder = file_get_contents(
            database_path('seeders/DatabaseSeeder.php')
        );

        $this->assertIsString($seeder);

        $this->assertStringNotContainsString(
            'App\\Models\\User',
            $seeder
        );

        $this->assertStringNotContainsString(
            'User::',
            $seeder
        );
    }

    public function test_session_schema_does_not_create_local_users_table(): void
    {
        $migrations = glob(
            database_path('migrations/*.php')
        );

        $this->assertIsArray($migrations);

        $contents = '';

        foreach ($migrations as $migration) {
            $contents .= file_get_contents($migration);
        }

        $this->assertStringContainsString(
            "Schema::create('sessions'",
            $contents
        );

        $this->assertStringNotContainsString(
            "Schema::create('users'",
            $contents
        );
    }
}
