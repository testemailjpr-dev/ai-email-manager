<?php
namespace App\Jobs;

use App\Models\Email;
use App\Models\Category;
use App\Models\User;
use OpenAI\Laravel\Facades\OpenAI;
use Google\Service\Gmail\ModifyMessageRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GmailService;
use Illuminate\Support\Facades\Log;

class ProcessEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $accountId;
    protected $messageId;
    protected $subject;
    protected $from;
    protected $body;

    public function __construct($userId, $accountId, $messageId, $subject, $from, $body)
    {
        $this->userId = $userId;
        $this->accountId = $accountId;
        $this->messageId = $messageId;
        $this->subject = $subject;
        $this->from = $from;
        $this->body = $body;
    }

    public function handle(): void
    {
        try {
            $categories = Category::where('user_id', $this->userId)->pluck('name')->toArray();
            $categoryList = implode(', ', $categories);

            $prompt = "You are an AI assistant that summarizes and classifies emails.
Classify into one of: {$categoryList}. If none fits, use 'Uncategorized'.
Return JSON with keys: summary, category.
Email Subject: ".strip_tags($this->subject)."
Email Body: ".strip_tags($this->body);

            $response = OpenAI::chat()->create([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'Summarize and classify emails.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.3,
            ]);

			$content = $response->choices[0]->message->content ?? '';
			$json = $this->parseJsonFromOpenAI($content, $this->messageId);
            if (!$json) {
                Log::warning("Invalid AI JSON for email {$this->messageId}");
                return;
            }

            $summary = $json['summary'] ?? '';
            $categoryName = $json['category'] ?? 'Uncategorized';

            $category = Category::where('user_id', $this->userId)
                ->where('name', $categoryName)
                ->first();

            $categoryId = isset($category->id) ? $category->id : null;

            // Update or insert the email
            Email::updateOrCreate(
                ['gmail_id' => $this->messageId],
                [
                    'user_id' => $this->userId,
                    'category_id' => $categoryId,
                    'from' => $this->from,
                    'subject' => $this->subject,
                    'body' => $this->body,
                    'summary' => $summary,
                ]
            );

            // Archive in Gmail
            $gmail = new GmailService($this->accountId);
			$request = new ModifyMessageRequest([
			    'removeLabelIds' => ['INBOX'], // remove Inbox label
			]);
			$gmail->service->users_messages->modify('me', $this->messageId, $request);
        } catch (\Throwable $e) {
            Log::error("ProcessEmailJob failed for {$this->messageId}: " . $e->getMessage());
        }
    }

	private function parseJsonFromOpenAI($content, $messageId)
	{
		// Try direct decode first
		$json = json_decode($content, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
			return $json;
		}
	
		// Remove code fences like ```json ... ```
		$content = preg_replace('/```(json)?/i', '', $content);
	
		// Extract first JSON object using regex
		if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $content, $matches)) {
			$json = json_decode($matches[0], true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $json;
			}
		}
	
		// Log the invalid response for debugging
		Log::warning("Invalid AI JSON for email {$messageId}: " . substr($content, 0, 300));
	
		return null;
	}
}
