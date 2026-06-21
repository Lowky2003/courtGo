<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_courtgo_branding_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Book your next game in seconds.')
            ->assertSee('For court owners')
            ->assertSee('Get started')
            ->assertDontSee('Laravel has an incredibly rich ecosystem');
    }

    public function test_landing_page_has_a_search_bar_and_curated_sport_tiles(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Find a court')     // the search button
            ->assertSee('Browse by sport')  // the tiles heading
            ->assertSee('Badminton')        // a curated category
            ->assertSee('Pickleball')       // shown even with no venues yet
            ->assertSee('for-business');    // link to the owner marketing page
    }

    public function test_landing_nav_is_role_aware_for_authenticated_users(): void
    {
        // A customer gets browse + bookings links and a profile menu, not a dashboard.
        $this->actingAs(User::factory()->create());
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('My bookings')
            ->assertSee('Log out')          // profile dropdown in the shared header
            ->assertDontSee('Go to dashboard');

        // An owner is kept in their sidebar area — redirected away from the public homepage.
        $this->actingAs(User::factory()->create(['role' => UserRole::Owner]));
        $this->get(route('home'))->assertRedirect(route('dashboard'));
    }
}
