<?php

namespace Tests\Unit;

use Tests\TestCase;

class CoreIdentityAuthorityContractTest extends TestCase
{
    public function test_template_has_no_local_laravel_user_authority(): void
    {
        $this->assertFileDoesNotExist(
            app_path('Models/User.php')
        );

        $this->assertFileDoesNotExist(
            database_path('factories/UserFactory.php')
        );

        $this->assertNull(
            config('auth.defaults.guard')
        );

        $this->assertNull(
            config('auth.defaults.passwords')
        );

        $this->assertSame([], config('auth.guards'));
        $this->assertSame([], config('auth.providers'));
        $this->assertSame([], config('auth.passwords'));
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
