<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    public function test_user_initials_extraction(): void
    {
        $user1 = new User(['name' => 'Jean Dupont']);
        $this->assertSame('JD', $user1->initials());

        $user2 = new User(['name' => 'Alice']);
        $this->assertSame('A', $user2->initials());

        $user3 = new User(['name' => 'Jean-Luc De La Tour']);
        $this->assertSame('JD', $user3->initials());

        $user4 = new User(['name' => '   ']);
        $this->assertSame('', $user4->initials());
    }

    public function test_user_role_helpers_and_admin_check(): void
    {
        $admin = new User();
        $admin->role = 'admin';
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->canAccessBackOffice());
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->hasRole(['admin', 'producer']));
        $this->assertFalse($admin->hasRole('consumer'));

        $consumer = new User();
        $consumer->role = 'consumer';
        $this->assertFalse($consumer->isAdmin());
        $this->assertFalse($consumer->canAccessBackOffice());
        $this->assertTrue($consumer->hasRole('consumer'));

        $producer = new User();
        $producer->role = 'producer';
        $this->assertFalse($producer->isAdmin());
        $this->assertTrue($producer->canAccessBackOffice());
    }

    public function test_user_blocked_state(): void
    {
        $user = new User();
        $this->assertFalse($user->isBlocked());

        $user->blocked_at = now();
        $this->assertTrue($user->isBlocked());
    }

    public function test_user_home_route_maps_to_role(): void
    {
        $admin = new User();
        $admin->role = 'admin';
        $this->assertSame('admin.dashboard', $admin->homeRoute());

        $distributor = new User();
        $distributor->role = 'distributor';
        $this->assertSame('admin.dashboard', $distributor->homeRoute());

        $consumer = new User();
        $consumer->role = 'consumer';
        $this->assertSame('consumer.profile', $consumer->homeRoute());
    }
}
