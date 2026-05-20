<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function __construct(protected WebhookService $webhookService) {}

    /**
     * List all webhooks for the authenticated user's team.
     * Secrets are not included in the listing for security.
     */
    public function index(Request $request)
    {
        $webhooks = Webhook::when(
            $request->user()?->currentTeam,
            fn ($q) => $q->where('team_id', $request->user()->currentTeam->id)
        )->get();

        return response()->json($webhooks);
    }

    /**
     * Register a new webhook.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'url'       => 'required|url',
            'events'    => 'required|array|min:1',
            'events.*'  => 'string|in:' . implode(',', Webhook::EVENTS),
            'secret'    => 'nullable|string|min:8',
            'is_active' => 'boolean',
        ]);

        $data['team_id'] = $request->user()?->currentTeam?->id;

        $webhook = $this->webhookService->register($data);

        return response()->json($webhook->makeVisible('secret'), 201);
    }

    /**
     * Show a single webhook (secret exposed to owner).
     */
    public function show(Webhook $webhook)
    {
        return response()->json($webhook->makeVisible('secret'));
    }

    /**
     * Update an existing webhook.
     */
    public function update(Request $request, Webhook $webhook)
    {
        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'url'       => 'sometimes|url',
            'events'    => 'sometimes|array|min:1',
            'events.*'  => 'string|in:' . implode(',', Webhook::EVENTS),
            'is_active' => 'sometimes|boolean',
        ]);

        $webhook = $this->webhookService->update($webhook, $data);

        return response()->json($webhook);
    }

    /**
     * Delete a webhook.
     */
    public function destroy(Webhook $webhook)
    {
        $webhook->delete();
        return response()->json(null, 204);
    }

    /**
     * Regenerate the signing secret for a webhook.
     */
    public function regenerateSecret(Webhook $webhook)
    {
        $secret = $this->webhookService->regenerateSecret($webhook);

        return response()->json(['secret' => $secret]);
    }

    /**
     * Return the list of supported event types.
     */
    public function events()
    {
        return response()->json(['events' => Webhook::EVENTS]);
    }
}
