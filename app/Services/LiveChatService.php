<?php

namespace App\Services;

use App\Models\LiveChat;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class LiveChatService
{
    /**
     * Start a new chat session
     */
    public function startChat(array $visitorData): LiveChat
    {
        // Try to find existing contact
        $contact = null;
        if (!empty($visitorData['email'])) {
            $contact = Contact::where('email', $visitorData['email'])->first();
        }

        return LiveChat::create([
            'visitor_id' => $visitorData['visitor_id'] ?? uniqid('visitor_'),
            'contact_id' => $contact?->id,
            'status' => LiveChat::STATUS_WAITING,
            'visitor_name' => $visitorData['name'] ?? 'Anonymous',
            'visitor_email' => $visitorData['email'] ?? null,
            'visitor_ip' => request()->ip(),
            'visitor_user_agent' => request()->userAgent(),
            'visitor_location' => $visitorData['location'] ?? null,
            'page_url' => $visitorData['page_url'] ?? null,
            'referrer' => $visitorData['referrer'] ?? null,
            'started_at' => now(),
        ]);
    }

    /**
     * Assign chat to an agent
     */
    public function assignChat(LiveChat $chat, int $userId): void
    {
        $chat->update([
            'user_id' => $userId,
            'status' => LiveChat::STATUS_ACTIVE,
        ]);

        // Send notification to agent
        // This would integrate with your notification system
    }

    /**
     * End a chat session
     */
    public function endChat(LiveChat $chat, ?int $rating = null, ?string $feedback = null): void
    {
        $chat->update([
            'status' => LiveChat::STATUS_ENDED,
            'ended_at' => now(),
            'rating' => $rating,
            'feedback' => $feedback,
        ]);

        // Create or update contact if we have email
        if ($chat->visitor_email && !$chat->contact_id) {
            $contact = Contact::firstOrCreate(
                ['email' => $chat->visitor_email],
                ['name' => $chat->visitor_name]
            );

            $chat->update(['contact_id' => $contact->id]);
        }
    }

    /**
     * Send message in chat
     */
    public function sendMessage(LiveChat $chat, string $content, bool $isAgent = false): Message
    {
        return Message::create([
            'chat_id' => $chat->id,
            'sender_type' => $isAgent ? 'agent' : 'visitor',
            'sender_id' => $isAgent ? Auth::id() : null,
            'content' => $content,
            'sent_at' => now(),
        ]);
    }

    /**
     * Get active chats for an agent
     */
    public function getActiveChats(?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = LiveChat::where('status', LiveChat::STATUS_ACTIVE)
            ->with(['contact', 'user', 'messages'])
            ->orderBy('started_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    /**
     * Get waiting chats
     */
    public function getWaitingChats(): \Illuminate\Database\Eloquent\Collection
    {
        return LiveChat::where('status', LiveChat::STATUS_WAITING)
            ->orderBy('started_at', 'asc')
            ->get();
    }

    /**
     * Get chat analytics
     */
    public function getChatAnalytics(?int $userId = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = LiveChat::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('started_at', [$startDate, $endDate]);
        }

        $chats = $query->get();
        $completedChats = $chats->where('status', LiveChat::STATUS_ENDED);

        return [
            'total_chats' => $chats->count(),
            'completed_chats' => $completedChats->count(),
            'missed_chats' => $chats->where('status', LiveChat::STATUS_MISSED)->count(),
            'average_duration' => round($completedChats->avg('duration') ?? 0),
            'average_rating' => round($completedChats->whereNotNull('rating')->avg('rating'), 2),
            'contacts_created' => $chats->whereNotNull('contact_id')->count(),
        ];
    }

    /**
     * Transfer chat to another agent
     */
    public function transferChat(LiveChat $chat, int $newUserId, string $reason = null): void
    {
        $oldUserId = $chat->user_id;
        
        $chat->update(['user_id' => $newUserId]);

        // Log the transfer
        $metadata = $chat->metadata ?? [];
        $metadata['transfers'][] = [
            'from_user_id' => $oldUserId,
            'to_user_id' => $newUserId,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ];
        
        $chat->update(['metadata' => $metadata]);
    }
}
