<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Contracts\WhatsAppClientInterface;
use Vendor\LaravelWhatsAppCloud\Events\WorkflowExecuted;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAiWorkflow;
use Vendor\LaravelWhatsAppCloud\Services\WorkflowEngine;
use Vendor\LaravelWhatsAppCloud\Tests\Mocks\MockWhatsAppClient;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class WorkflowEngineTest extends TestCase
{
    protected function metaAccount(): WhatsAppAccount
    {
        return WhatsAppAccount::query()->create([
            'name' => 'workflow',
            'provider' => 'meta',
            'phone_number' => '923001234567',
            'phone_number_id' => 'pid',
            'access_token' => 'token-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    #[Test]
    public function it_runs_workflow_with_step_fallback(): void
    {
        Event::fake([WorkflowExecuted::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
        config(['whatsapp.openai.api_key' => '']);

        $workflow = WhatsAppAiWorkflow::query()->create([
            'account_id' => $this->metaAccount()->id,
            'name' => 'greet',
            'steps_json' => [['response' => 'Welcome!']],
            'is_active' => true,
        ]);

        $reply = app(WorkflowEngine::class)->run($workflow, '923009999999', 'Hi');

        $this->assertSame('Welcome!', $reply);
        $this->assertDatabaseHas('whatsapp_messages', [
            'to' => '923009999999',
            'message' => 'Welcome!',
        ]);

        Event::assertDispatched(WorkflowExecuted::class, function (WorkflowExecuted $event) {
            return $event->reply === 'Welcome!';
        });
    }

    #[Test]
    public function it_uses_openai_when_configured(): void
    {
        Event::fake([WorkflowExecuted::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
        config(['whatsapp.openai.api_key' => 'sk-test']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'Workflow reply']]],
            ]),
        ]);

        $workflow = WhatsAppAiWorkflow::query()->create([
            'account_id' => $this->metaAccount()->id,
            'name' => 'ai-flow',
            'system_prompt' => 'Be helpful',
            'is_active' => true,
        ]);

        $reply = app(WorkflowEngine::class)->run($workflow, '923009999999', 'Need help');

        $this->assertSame('Workflow reply', $reply);
        Event::assertDispatched(WorkflowExecuted::class);
    }

    #[Test]
    public function it_falls_back_when_openai_fails(): void
    {
        Event::fake([WorkflowExecuted::class]);
        $this->app->instance(WhatsAppClientInterface::class, new MockWhatsAppClient);
        config(['whatsapp.openai.api_key' => 'sk-test']);

        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'fail'], 500),
        ]);

        $workflow = WhatsAppAiWorkflow::query()->create([
            'account_id' => $this->metaAccount()->id,
            'name' => 'fallback',
            'steps_json' => [['response' => 'Fallback reply']],
            'is_active' => true,
        ]);

        $reply = app(WorkflowEngine::class)->run($workflow, '923009999999', 'Hi');

        $this->assertSame('Fallback reply', $reply);
        Event::assertDispatched(WorkflowExecuted::class);
    }

    #[Test]
    public function it_skips_inactive_workflows(): void
    {
        Event::fake([WorkflowExecuted::class]);

        $workflow = WhatsAppAiWorkflow::query()->create([
            'account_id' => $this->metaAccount()->id,
            'name' => 'off',
            'is_active' => false,
        ]);

        $this->assertNull(app(WorkflowEngine::class)->run($workflow, '923009999999', 'Hi'));
        Event::assertNotDispatched(WorkflowExecuted::class);
    }
}
