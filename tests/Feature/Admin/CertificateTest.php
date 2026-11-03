<?php

namespace Tests\Feature\Admin;

use App\Models\Certificate;
use App\Models\Producer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_certificates_by_issuer_number_and_product(): void
    {
        $prod1 = Product::factory()->create(['name' => 'Miel Bio']);
        $prod2 = Product::factory()->create(['name' => 'Huile Vierge']);

        $cert1 = Certificate::factory()->for($prod1)->create([
            'issuer' => 'Ecocert France',
            'certificate_number' => 'CERT-ECO-99',
        ]);
        $cert2 = Certificate::factory()->for($prod2)->create([
            'issuer' => 'Bureau Veritas',
            'certificate_number' => 'CERT-BV-01',
        ]);

        // Search by issuer
        $this->actingAs($this->admin)->get('/admin/certificates?q=Ecocert')
            ->assertOk()
            ->assertSee('Ecocert France')
            ->assertDontSee('Bureau Veritas');

        // Search by certificate number
        $this->actingAs($this->admin)->get('/admin/certificates?q=CERT-ECO-99')
            ->assertOk()
            ->assertSee('CERT-ECO-99')
            ->assertDontSee('CERT-BV-01');

        // Search by product name
        $this->actingAs($this->admin)->get('/admin/certificates?q=Miel')
            ->assertOk()
            ->assertSee('Miel Bio')
            ->assertDontSee('Huile Vierge');
    }

    public function test_admin_can_filter_certificates_by_type_and_status(): void
    {
        $certBio = Certificate::factory()->create([
            'certificate_type' => 'Bio AB',
            'status' => 'verified',
        ]);
        $certFairtrade = Certificate::factory()->create([
            'certificate_type' => 'Fairtrade',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get('/admin/certificates?certificate_type=Bio AB')
            ->assertOk()
            ->assertSee('Bio AB')
            ->assertDontSee('Fairtrade');

        $this->actingAs($this->admin)->get('/admin/certificates?status=verified')
            ->assertOk()
            ->assertSee('Bio AB');
    }

    public function test_admin_can_view_create_show_edit_screens(): void
    {
        $cert = Certificate::factory()->create();

        $this->actingAs($this->admin)->get('/admin/certificates/create')
            ->assertOk();

        $this->actingAs($this->admin)->get("/admin/certificates/{$cert->id}")
            ->assertOk()
            ->assertSee($cert->certificate_number);

        $this->actingAs($this->admin)->get("/admin/certificates/{$cert->id}/edit")
            ->assertOk()
            ->assertSee($cert->certificate_number);
    }

    public function test_admin_can_create_a_certificate_with_document(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $file = UploadedFile::fake()->create('certificat.pdf', 50, 'application/pdf');

        $response = $this->actingAs($this->admin)->post('/admin/certificates', [
            'product_id' => $product->id,
            'certificate_type' => 'Bio AB',
            'issuer' => 'Certipaq Bio',
            'certificate_number' => 'BIO-2026-999',
            'issue_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
            'status' => 'verified',
            'document' => $file,
        ]);

        $cert = Certificate::where('certificate_number', 'BIO-2026-999')->firstOrFail();

        $response->assertRedirect(route('admin.certificates.show', $cert))
            ->assertSessionHas('success', 'Certification enregistrée (statut non vérifié automatiquement).');

        $this->assertSame('verified', $cert->status);
        $this->assertNotNull($cert->document_path);
        Storage::disk('public')->assertExists($cert->document_path);
    }

    public function test_producer_creating_certificate_is_forced_to_pending_status(): void
    {
        $producerUser = User::factory()->role('producer')->create();
        $producer = Producer::factory()->create(['user_id' => $producerUser->id]);
        $product = Product::factory()->create(['producer_id' => $producer->id]);

        $response = $this->actingAs($producerUser)->post('/admin/certificates', [
            'product_id' => $product->id,
            'certificate_type' => 'AOP',
            'issuer' => 'INAO',
            'certificate_number' => 'AOP-777',
            'status' => 'verified', // Producer tries to set verified
        ]);

        $cert = Certificate::where('certificate_number', 'AOP-777')->firstOrFail();

        $response->assertRedirect(route('admin.certificates.show', $cert));
        // Non-admin is forced to pending
        $this->assertSame('pending', $cert->status);
    }

    public function test_cannot_create_certificate_for_unowned_product(): void
    {
        $producerUser = User::factory()->role('producer')->create();
        Producer::factory()->create(['user_id' => $producerUser->id]);
        $otherProduct = Product::factory()->create(); // Belongs to another producer

        $this->actingAs($producerUser)->post('/admin/certificates', [
            'product_id' => $otherProduct->id,
            'certificate_type' => 'Bio AB',
            'status' => 'pending',
        ])->assertSessionHasErrors('product_id');
    }

    public function test_admin_can_update_a_certificate_and_replace_document(): void
    {
        Storage::fake('public');
        $oldPath = 'certificates/old_cert.pdf';
        Storage::disk('public')->put($oldPath, 'old content');

        $cert = Certificate::factory()->create([
            'document_path' => $oldPath,
            'certificate_type' => 'Bio AB',
        ]);

        $newFile = UploadedFile::fake()->create('new_cert.pdf', 80, 'application/pdf');

        $response = $this->actingAs($this->admin)->put("/admin/certificates/{$cert->id}", [
            'product_id' => $cert->product_id,
            'certificate_type' => 'Bio AB Renouvelé',
            'status' => 'verified',
            'document' => $newFile,
        ]);

        $response->assertRedirect(route('admin.certificates.show', $cert))
            ->assertSessionHas('success', 'Certification mise à jour.');

        Storage::disk('public')->assertMissing($oldPath);

        $fresh = $cert->fresh();
        $this->assertSame('Bio AB Renouvelé', $fresh->certificate_type);
        $this->assertNotNull($fresh->document_path);
        Storage::disk('public')->assertExists($fresh->document_path);
    }

    public function test_admin_can_delete_a_certificate_and_its_document(): void
    {
        Storage::fake('public');
        $docPath = 'certificates/delete_me.pdf';
        Storage::disk('public')->put($docPath, 'sample doc');

        $cert = Certificate::factory()->create([
            'document_path' => $docPath,
        ]);

        $this->actingAs($this->admin)->delete("/admin/certificates/{$cert->id}")
            ->assertRedirect(route('admin.certificates.index'))
            ->assertSessionHas('success', 'Certification supprimée.');

        Storage::disk('public')->assertMissing($docPath);
        $this->assertModelMissing($cert);
    }
}
