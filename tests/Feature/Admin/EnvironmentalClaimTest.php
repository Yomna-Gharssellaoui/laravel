<?php

namespace Tests\Feature\Admin;

use App\Jobs\AnalyzeClaimJob;
use App\Models\EnvironmentalClaim;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnvironmentalClaimTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_filter_claims_by_title_and_claim_type_query(): void
    {
        $prod = Product::factory()->create();

        EnvironmentalClaim::create([
            'product_id' => $prod->id,
            'claim_type' => 'Empreinte carbone',
            'title' => 'Neutre en carbone d’ici 2030',
            'status' => 'pending',
        ]);
        EnvironmentalClaim::create([
            'product_id' => $prod->id,
            'claim_type' => 'Emballage',
            'title' => 'Bouteille 100% verre recyclé',
            'status' => 'pending',
        ]);

        // Search by title
        $this->actingAs($this->admin)->get('/admin/environmental-claims?q=verre')
            ->assertOk()
            ->assertSee('Bouteille 100% verre recyclé')
            ->assertDontSee('Neutre en carbone d’ici 2030');

        // Search by claim type
        $this->actingAs($this->admin)->get('/admin/environmental-claims?q=carbone')
            ->assertOk()
            ->assertSee('Neutre en carbone d’ici 2030')
            ->assertDontSee('Bouteille 100% verre recyclé');
    }

    public function test_admin_can_filter_claims_by_status(): void
    {
        $prod = Product::factory()->create();

        EnvironmentalClaim::create([
            'product_id' => $prod->id,
            'claim_type' => 'Bio',
            'title' => 'Déclaration Validée',
            'status' => 'verified',
        ]);
        EnvironmentalClaim::create([
            'product_id' => $prod->id,
            'claim_type' => 'Local',
            'title' => 'Déclaration En Attente',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get('/admin/environmental-claims?status=verified')
            ->assertOk()
            ->assertSee('Déclaration Validée')
            ->assertDontSee('Déclaration En Attente');
    }

    public function test_admin_can_view_create_show_edit_screens(): void
    {
        $prod = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $prod->id,
            'claim_type' => 'Bio',
            'title' => 'Produit respectueux de la faune',
            'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get('/admin/environmental-claims/create')
            ->assertOk();

        $this->actingAs($this->admin)->get("/admin/environmental-claims/{$claim->id}")
            ->assertOk()
            ->assertSee('Produit respectueux de la faune');

        $this->actingAs($this->admin)->get("/admin/environmental-claims/{$claim->id}/edit")
            ->assertOk()
            ->assertSee('Produit respectueux de la faune');
    }

    public function test_admin_can_create_a_claim_with_document_and_dispatches_analysis_job(): void
    {
        Queue::fake();
        Storage::fake('public');

        $product = Product::factory()->create();
        $file = UploadedFile::fake()->create('justificatif.pdf', 60, 'application/pdf');

        $response = $this->actingAs($this->admin)->post('/admin/environmental-claims', [
            'product_id' => $product->id,
            'claim_type' => 'Zéro Déchet',
            'title' => 'Emballage compostable à domicile',
            'description' => 'Certifié OK Compost Home.',
            'status' => 'pending',
            'document' => $file,
        ]);

        $claim = EnvironmentalClaim::where('title', 'Emballage compostable à domicile')->firstOrFail();

        $response->assertRedirect(route('admin.environmental-claims.show', $claim))
            ->assertSessionHas('success', 'Déclaration environnementale créée. L’analyse de greenwashing a été lancée.');

        $this->assertNotNull($claim->document_path);
        Storage::disk('public')->assertExists($claim->document_path);

        Queue::assertPushed(AnalyzeClaimJob::class, function ($job) use ($claim) {
            return $job->claim->id === $claim->id;
        });
    }

    public function test_admin_can_update_a_claim_and_dispatches_job_on_change(): void
    {
        Queue::fake();
        Storage::fake('public');

        $product = Product::factory()->create();
        $oldDoc = 'claims/old_doc.pdf';
        Storage::disk('public')->put($oldDoc, 'old content');

        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Local',
            'title' => 'Origine France',
            'status' => 'pending',
            'document_path' => $oldDoc,
        ]);

        $newFile = UploadedFile::fake()->create('nouveau_justificatif.pdf', 80, 'application/pdf');

        $response = $this->actingAs($this->admin)->put("/admin/environmental-claims/{$claim->id}", [
            'product_id' => $product->id,
            'claim_type' => 'Ultra-Local',
            'title' => 'Origine Région Occitanie',
            'status' => 'pending',
            'document' => $newFile,
        ]);

        $response->assertRedirect(route('admin.environmental-claims.show', $claim))
            ->assertSessionHas('success', 'Déclaration environnementale mise à jour.');

        Storage::disk('public')->assertMissing($oldDoc);

        $fresh = $claim->fresh();
        $this->assertSame('Origine Région Occitanie', $fresh->title);
        $this->assertSame('Ultra-Local', $fresh->claim_type);
        $this->assertNotNull($fresh->document_path);
        Storage::disk('public')->assertExists($fresh->document_path);

        Queue::assertPushed(AnalyzeClaimJob::class);
    }

    public function test_admin_can_delete_a_claim_and_its_document(): void
    {
        Storage::fake('public');
        $docPath = 'claims/delete_claim.pdf';
        Storage::disk('public')->put($docPath, 'sample');

        $product = Product::factory()->create();
        $claim = EnvironmentalClaim::create([
            'product_id' => $product->id,
            'claim_type' => 'Bio',
            'title' => 'Titre à supprimer',
            'status' => 'pending',
            'document_path' => $docPath,
        ]);

        $this->actingAs($this->admin)->delete("/admin/environmental-claims/{$claim->id}")
            ->assertRedirect(route('admin.environmental-claims.index'))
            ->assertSessionHas('success', 'Déclaration environnementale supprimée.');

        Storage::disk('public')->assertMissing($docPath);
        $this->assertModelMissing($claim);
    }
}
