<?php

namespace Tests\Feature;

use App\Models\ProAccountRequest;
use App\Models\User;
use App\Notifications\ProAccountRequestDecided;
use App\Notifications\ProAccountRequestSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProAccountRequestTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'requested_role' => 'producer',
            'company_name' => 'Ferme du Lac',
            'location' => 'Bizerte',
            'activity_type' => 'biologique',
            'phone' => '+216 98 000 111',
            'motivation' => 'Maraîchage biologique sur 5 hectares, 20 tonnes par an.',
            'terms' => '1',
        ];
    }

    private function pendingRequest(User $user, string $role = 'producer', string $activity = 'biologique'): ProAccountRequest
    {
        return $user->proAccountRequests()->create([
            'requested_role' => $role, 'company_name' => 'Ferme du Lac', 'location' => 'Bizerte',
            'activity_type' => $activity, 'motivation' => 'Maraîchage biologique sur 5 hectares.',
        ]);
    }

    public function test_consumer_sees_the_button_and_the_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('consumer.profile'))->assertOk()->assertSee('Demander un compte professionnel');
        $this->actingAs($user)->get(route('profile.edit'))->assertOk()->assertSee('Demander un compte professionnel');
        $this->actingAs($user)->get(route('pro-account.create'))->assertOk()->assertSee('Envoyer ma demande');
    }

    public function test_consumer_submits_a_request_and_admins_are_notified(): void
    {
        Notification::fake();
        Storage::fake('local');
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('pro-account.store'), $this->payload([
            'document' => UploadedFile::fake()->create('registre.pdf', 200, 'application/pdf'),
        ]))->assertRedirect(route('pro-account.create'));

        $request = ProAccountRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame('consumer', $user->fresh()->role);
        Storage::disk('local')->assertExists($request->document_path);
        Notification::assertSentTo($admin, ProAccountRequestSubmitted::class);

        $this->actingAs($user)->get(route('pro-account.create'))->assertSee('Demande en cours d’examen');
    }

    public function test_validation_rules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('pro-account.store'), $this->payload([
            'requested_role' => 'admin', 'activity_type' => 'grossiste', 'motivation' => 'court', 'terms' => null,
        ]))->assertSessionHasErrors(['requested_role', 'activity_type', 'motivation', 'terms']);

        // An activity from another role is refused
        $this->actingAs($user)->post(route('pro-account.store'), $this->payload(['activity_type' => 'grossiste']))
            ->assertSessionHasErrors('activity_type');

        $this->assertDatabaseCount('pro_account_requests', 0);
    }

    public function test_only_one_pending_request_and_only_for_consumers(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->pendingRequest($user);

        $this->actingAs($user)->post(route('pro-account.store'), $this->payload())->assertSessionHasErrors('requested_role');
        $this->assertDatabaseCount('pro_account_requests', 1);

        $producer = User::factory()->role('producer')->create();
        $this->actingAs($producer)->post(route('pro-account.store'), $this->payload())->assertForbidden();
    }

    public function test_unverified_users_cannot_apply(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('pro-account.create'))->assertRedirect(route('verification.notice'));
    }

    public function test_user_cancels_own_pending_request_only(): void
    {
        $user = User::factory()->create();
        $request = $this->pendingRequest($user);

        $this->actingAs(User::factory()->create())->delete(route('pro-account.destroy', $request))->assertForbidden();
        $this->actingAs($user)->delete(route('pro-account.destroy', $request))->assertRedirect();
        $this->assertDatabaseCount('pro_account_requests', 0);
    }

    public function test_admin_approves_a_producer_request(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $request = $this->pendingRequest($user);

        $this->actingAs($admin)->get(route('admin.pro-requests.index'))->assertOk()->assertSee('Ferme du Lac');
        $this->actingAs($admin)->patch(route('admin.pro-requests.approve', $request), ['admin_note' => 'Bienvenue'])->assertRedirect();

        $user->refresh();
        $this->assertSame('producer', $user->role);
        $this->assertSame('Ferme du Lac', $user->producer->company_name);
        $this->assertSame('biologique', $user->producer->farming_method);
        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($admin->id, $request->fresh()->reviewed_by);
        Notification::assertSentTo($user, ProAccountRequestDecided::class);

        // The new producer reaches the back-office
        $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_approves_transformer_and_distributor_requests(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $transformer = User::factory()->create();
        $distributor = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.pro-requests.approve', $this->pendingRequest($transformer, 'transformer', 'conserverie')));
        $this->actingAs($admin)->patch(route('admin.pro-requests.approve', $this->pendingRequest($distributor, 'distributor', 'grossiste')));

        $this->assertSame('conserverie', $transformer->fresh()->transformer->transformation_type);
        $this->assertSame('grossiste', $distributor->fresh()->distributor->distributor_type);
    }

    public function test_reject_requires_a_reason_and_allows_a_new_request(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $request = $this->pendingRequest($user);

        $this->actingAs($admin)->patch(route('admin.pro-requests.reject', $request), ['admin_note' => ''])->assertSessionHasErrors('admin_note');
        $this->actingAs($admin)->patch(route('admin.pro-requests.reject', $request), ['admin_note' => 'Justificatif illisible'])->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('consumer', $user->fresh()->role);
        Notification::assertSentTo($user, ProAccountRequestDecided::class);

        $this->actingAs($user)->get(route('pro-account.create'))->assertSee('Justificatif illisible')->assertSee('Envoyer ma demande');
        $this->actingAs($user)->post(route('pro-account.store'), $this->payload())->assertSessionHasNoErrors();
    }

    public function test_a_decided_request_cannot_be_decided_again(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $request = $this->pendingRequest(User::factory()->create());
        $this->actingAs($admin)->patch(route('admin.pro-requests.approve', $request));

        $this->actingAs($admin)->patch(route('admin.pro-requests.reject', $request), ['admin_note' => 'Trop tard'])->assertStatus(409);
    }

    public function test_only_admins_review_requests_and_download_documents(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $request = $this->pendingRequest($user);
        $request->forceFill(['document_path' => UploadedFile::fake()->create('k.pdf', 10)->store('pro-requests', 'local')])->save();

        foreach ([$user, User::factory()->role('producer')->create()] as $other) {
            $this->actingAs($other)->get(route('admin.pro-requests.index'))->assertForbidden();
            $this->actingAs($other)->get(route('admin.pro-requests.document', $request))->assertForbidden();
            $this->actingAs($other)->patch(route('admin.pro-requests.approve', $request))->assertForbidden();
        }

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.pro-requests.document', $request))->assertOk()->assertDownload();
    }
}
