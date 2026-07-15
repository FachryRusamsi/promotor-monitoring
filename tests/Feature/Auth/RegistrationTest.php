<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $role = \App\Models\Role::factory()->create(['name' => 'promotor']);
    $region = \App\Models\Region::create(['name' => 'Jakarta']);
    $area = \App\Models\Area::create(['name' => 'Jaksel', 'region_id' => $region->id]);

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'phone' => '081234567890',
        'region_id' => $region->id,
        'area_id' => $area->id,
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('promotor.dashboard', absolute: false));
});
