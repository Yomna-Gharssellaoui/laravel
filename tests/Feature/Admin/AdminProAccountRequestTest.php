<?php

namespace Tests\Feature\Admin;

use App\Models\ProAccountRequest;
use App\Models\User;
use App\Notifications\ProAccountRequestDecided;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProAccountRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->role('admin')->create();
    }

    /** Helper to create a ProAccountRequest without relying on a factory that may not exist. */
    private function createReq(array $attrs = [], ?User $owner = null): ProAccountRequest
    {
        $owner = $owner ?? User::factory()->role('consumer')->create();

        $req = new ProAccountRequest(array_merge([
            'requested_role' => 'producer',
            'company_name'   => 'Ferme de Test',
            'location'       => 'Béja',
            'activity_type'  => 'biologique',
            'motivation'     => 'Maraîchage bio sur 5 hectares.',
            'status'         => 'pending',
        ], $attrs));

        $owner->proAccountRequests()->save($req);

        return $req->fresh();
    }

    public function test_admin_can_list_pro_requests_with_filters(): void
    {
        $consumer = User::factory()->role('consumer')->create(['name' => 'Ali Ben']);
        $this->createReq(['company_name' => 'Ferme de Test'], $consumer);
        $this->createReq(['status' => 'approved', 'company_name' => 'Autre Société']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.pro-requests.index', ['status' => 'pending', 'q' => 'Ali']));

        $response->assertOk()->assertSee('Ferme de Test');
    }

    public function test_admin_can_show_pro_request(): void
    {
        $req = $this->createReq(['company_name' => 'Élevage du Sahel']);

        $response = $this->actingAs($this->admin)->get(route('admin.pro-requests.show', $req));

        $response->assertOk()->assertSee('Élevage du Sahel');
    }

    public function test_admin_can_approve_pro_request(): void
    {
        Notification::fake();

        $consumer = User::factory()->role('consumer')->create();
        $req = $this->createReq(['requested_role' => 'producer'], $consumer);

        $this->actingAs($this->admin)->patch(route('admin.pro-requests.approve', $req), [
            'admin_note' => 'Dossier complet.',
        ])->assertRedirect(route('admin.pro-requests.show', $req));

        $req->refresh();
        $this->assertEquals('approved', $req->status);
        $this->assertEquals($this->admin->id, $req->reviewed_by);

        $consumer->refresh();
        $this->assertEquals('producer', $consumer->role);

        Notification::assertSentTo($consumer, ProAccountRequestDecided::class);
    }

    public function test_admin_can_reject_pro_request(): void
    {
        Notification::fake();

        $consumer = User::factory()->role('consumer')->create();
        $req = $this->createReq([], $consumer);

        $this->actingAs($this->admin)->patch(route('admin.pro-requests.reject', $req), [
            'admin_note' => 'Justificatif invalide.',
        ])->assertRedirect(route('admin.pro-requests.show', $req));

        $req->refresh();
        $this->assertEquals('rejected', $req->status);
        $this->assertEquals('Justificatif invalide.', $req->admin_note);

        Notification::assertSentTo($consumer, ProAccountRequestDecided::class);
    }

    public function test_reject_requires_admin_note(): void
    {
        $req = $this->createReq();

        $this->actingAs($this->admin)->patch(route('admin.pro-requests.reject', $req), [
            'admin_note' => '',
        ])->assertSessionHasErrors('admin_note');
    }

    public function test_cannot_approve_already_processed_request(): void
    {
        $req = $this->createReq();
        $req->forceFill(['status' => 'approved'])->saveQuietly();

        $this->actingAs($this->admin)
            ->patch(route('admin.pro-requests.approve', $req))
            ->assertStatus(409);
    }

    public function test_admin_can_download_document(): void
    {
        Storage::fake('local');
        $filePath = 'pro-documents/justif.pdf';
        Storage::disk('local')->put($filePath, 'fake-pdf-content');

        $req = $this->createReq(['document_path' => $filePath]);

        $this->actingAs($this->admin)
            ->get(route('admin.pro-requests.document', $req))
            ->assertOk();
    }
}
