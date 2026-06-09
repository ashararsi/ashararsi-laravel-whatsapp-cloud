<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTag;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class V2ContactNotesTagsTest extends TestCase
{
    protected function seedContact(): WhatsAppContact
    {
        $account = WhatsAppAccount::query()->create([
            'name' => 'crm',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => '123',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        return WhatsAppContact::query()->create([
            'account_id' => $account->id,
            'phone' => '923009999999',
            'name' => 'CRM User',
        ]);
    }

    #[Test]
    public function it_shows_notes_and_tags_on_contact_page(): void
    {
        $contact = $this->seedContact();
        $tag = WhatsAppTag::query()->create([
            'account_id' => $contact->account_id,
            'name' => 'VIP',
            'color' => '#ff0000',
        ]);
        $contact->tags()->attach($tag->id);
        $contact->notes()->create(['body' => 'Important client', 'author' => 'Agent']);

        $this->get("/admin/whatsapp/contacts/{$contact->id}")
            ->assertOk()
            ->assertSee('Important client')
            ->assertSee('VIP')
            ->assertSee('Add Note')
            ->assertSee('assign tag');
    }

    #[Test]
    public function it_stores_notes_via_admin_form(): void
    {
        $contact = $this->seedContact();

        $this->post("/admin/whatsapp/contacts/{$contact->id}/notes", [
            'body' => 'Called back today',
            'author' => 'Support',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_contact_notes', [
            'contact_id' => $contact->id,
            'body' => 'Called back today',
            'author' => 'Support',
        ]);
    }

    #[Test]
    public function it_creates_and_assigns_tags(): void
    {
        $contact = $this->seedContact();

        $this->post("/admin/whatsapp/contacts/{$contact->id}/tags/create", [
            'name' => 'Lead',
            'color' => '#0d6efd',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('whatsapp_tags', [
            'account_id' => $contact->account_id,
            'name' => 'Lead',
        ]);

        $tag = WhatsAppTag::query()->where('name', 'Lead')->firstOrFail();
        $this->assertDatabaseHas('whatsapp_contact_tag', [
            'contact_id' => $contact->id,
            'tag_id' => $tag->id,
        ]);
    }

    #[Test]
    public function it_syncs_and_detaches_tags(): void
    {
        $contact = $this->seedContact();
        $tagA = WhatsAppTag::query()->create([
            'account_id' => $contact->account_id,
            'name' => 'A',
        ]);
        $tagB = WhatsAppTag::query()->create([
            'account_id' => $contact->account_id,
            'name' => 'B',
        ]);

        $this->post("/admin/whatsapp/contacts/{$contact->id}/tags", [
            'tags' => [$tagA->id, $tagB->id],
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertCount(2, $contact->fresh()->tags);

        $this->delete("/admin/whatsapp/contacts/{$contact->id}/tags/{$tagA->id}")
            ->assertRedirect()
            ->assertSessionHas('success');

        $contact->refresh();
        $this->assertCount(1, $contact->tags);
        $this->assertTrue($contact->tags->contains('id', $tagB->id));
    }
}
