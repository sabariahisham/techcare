<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Document;
use App\Models\KnowledgeBase;
use App\Models\Module;
use App\Models\RefCategory;
use App\Models\RefSubCategory;
use App\Models\RefTicketStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

class UuidGenerationTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Test that User model generates UUID on creation
   */
  public function test_user_generates_uuid_on_creation(): void
  {
    $user = User::factory()->create();

    $this->assertNotNull($user->uuid);
    $this->assertTrue($this->isValidUuid($user->uuid));
    $this->assertEquals(36, strlen($user->uuid)); // UUID v4 format is 36 characters
  }

  /**
   * Test that Ticket model generates UUID on creation
   */
  public function test_ticket_generates_uuid_on_creation(): void
  {
    // Create required related models
    $user = User::factory()->create();
    $category = RefCategory::create([
      'cat_name' => 'Test Category',
      'cat_desc' => 'Test Description',
      'cat_order' => 1,
      'cat_status' => 1,
      'created_by' => $user->id,
    ]);
    $subCategory = RefSubCategory::create([
      'cat_id' => $category->id,
      'subcat_name' => 'Test SubCategory',
      'subcat_desc' => 'Test Description',
      'subcat_order' => 1,
      'subcat_status' => 1,
      'created_by' => $user->id,
    ]);
    $status = RefTicketStatus::create([
      'st_name' => 'Open',
      'st_desc' => 'Ticket is open',
      'st_order' => 1,
      'st_color' => 'primary',
    ]);

    $ticket = Ticket::create([
      'user_id' => $user->id,
      'cat_id' => $category->id,
      'subcat_id' => $subCategory->id,
      'tick_status' => $status->id,
      'ticket_title' => 'Test Ticket',
      'ticket_desc' => 'Test Description',
      'ticket_type' => 'internal',
      'created_by' => $user->id,
    ]);

    $this->assertNotNull($ticket->uuid);
    $this->assertTrue($this->isValidUuid($ticket->uuid));
    $this->assertNotNull($ticket->ticket_nolog);
    $this->assertStringStartsWith('HELP-', $ticket->ticket_nolog);
  }

  /**
   * Test that Document model generates UUID on creation
   */
  public function test_document_generates_uuid_on_creation(): void
  {
    $user = User::factory()->create();

    $document = Document::create([
      'doc_id' => 1,
      'doc_mime' => 'application/pdf',
      'doc_name' => 'test.pdf',
      'doc_storage_nm' => 'test_storage.pdf',
      'doc_jenis' => 'TICKET',
      'created_by' => $user->id,
    ]);

    $this->assertNotNull($document->uuid);
    $this->assertTrue($this->isValidUuid($document->uuid));
  }

  /**
   * Test that KnowledgeBase model generates UUID on creation
   */
  public function test_knowledge_base_generates_uuid_on_creation(): void
  {
    $user = User::factory()->create();
    $category = RefCategory::create([
      'cat_name' => 'Test Category',
      'cat_desc' => 'Test Description',
      'cat_order' => 1,
      'cat_status' => 1,
      'created_by' => $user->id,
    ]);
    $subCategory = RefSubCategory::create([
      'cat_id' => $category->id,
      'subcat_name' => 'Test SubCategory',
      'subcat_desc' => 'Test Description',
      'subcat_order' => 1,
      'subcat_status' => 1,
      'created_by' => $user->id,
    ]);

    $kb = KnowledgeBase::create([
      'cat_id' => $category->id,
      'subcat_id' => $subCategory->id,
      'kb_title' => 'Test KB',
      'kb_desc' => 'Test Description',
      'kb_status' => 'aktif',
      'created_by' => $user->id,
    ]);

    $this->assertNotNull($kb->uuid);
    $this->assertTrue($this->isValidUuid($kb->uuid));
  }

  /**
   * Test that Module model generates UUID on creation
   */
  public function test_module_generates_uuid_on_creation(): void
  {
    $user = User::factory()->create();

    $module = Module::create([
      'module_name' => 'Test Module',
      'module_description' => 'Test Description',
      'icon' => 'mdi-test',
      'route' => 'test.route',
      'is_active' => true,
      'created_by' => $user->id,
    ]);

    $this->assertNotNull($module->uuid);
    $this->assertTrue($this->isValidUuid($module->uuid));
  }

  /**
   * Test that UUIDs are unique across multiple records
   */
  public function test_uuids_are_unique(): void
  {
    $users = User::factory()->count(10)->create();

    $uuids = $users->pluck('uuid')->toArray();
    $uniqueUuids = array_unique($uuids);

    $this->assertCount(10, $uniqueUuids);
    $this->assertEquals(count($uuids), count($uniqueUuids));
  }

  /**
   * Test that UUID columns exist in database
   */
  public function test_uuid_columns_exist_in_tables(): void
  {
    $this->assertTrue(Schema::hasColumn('users', 'uuid'));
    $this->assertTrue(Schema::hasColumn('tickets', 'uuid'));
    $this->assertTrue(Schema::hasColumn('documents', 'uuid'));
    $this->assertTrue(Schema::hasColumn('knowledge_base', 'uuid'));
    $this->assertTrue(Schema::hasColumn('module_list', 'uuid'));
  }

  /**
   * Test that models don't override manually set UUIDs
   */
  public function test_manually_set_uuid_is_not_overridden(): void
  {
    $customUuid = '12345678-1234-1234-1234-123456789012';

    $user = User::factory()->create([
      'uuid' => $customUuid
    ]);

    $this->assertEquals($customUuid, $user->uuid);
  }

  /**
   * Helper function to validate UUID format
   */
  private function isValidUuid(string $uuid): bool
  {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
  }
}
