<?php

namespace App\Services;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Email;
use App\Models\Category;
use App\Models\GmailAccount;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessEmailJob;

class GmailService
{
    public $client;
    public $service;
    public $account;

    public function __construct($account_id = 0)
    {
        $this->client = new Client();
        $this->client->setAuthConfig(storage_path('app/google/client_secret.json'));
        $this->client->addScope(Gmail::MAIL_GOOGLE_COM);
        $this->client->addScope(Gmail::GMAIL_READONLY);
        $this->client->addScope(Gmail::GMAIL_MODIFY);
        $this->client->setAccessType('offline');
		$this->account = null;

        // Current user tokens (you should save these from OAuth callback)
		if( is_numeric($account_id) && $account_id ) {
        	$account = GmailAccount::where('id', $account_id)->first();
		}
        if ($account && $account->google_token) {
			$this->account = $account;
			if ( !is_array($account->google_token) ){
				$account->google_token = json_decode($account->google_token, true);
			}
            $this->client->setAccessToken($account->google_token);

			// Refresh token if expired
			if ($this->client->isAccessTokenExpired()) {
				$currentToken = $account->google_token ?? [];
				$refreshToken = $currentToken['refresh_token'] ?? null;
				if ($refreshToken) {
					$newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
			
					// Preserve the old refresh token if Google doesn't return a new one
					if (!isset($newToken['refresh_token'])) {
						$newToken['refresh_token'] = $refreshToken;
					} else {
						$refreshToken = $newToken['refresh_token'];
					}
			
					// Update database and re-apply token
					$account->update([
						'google_token' => json_encode($newToken),
						'google_refresh_token' => $refreshToken,
					]);
			
					$this->client->setAccessToken($newToken);
				} else {
					\Log::warning("No refresh token found for account {$account->id}");
				}
			}
        }

        $this->service = new Gmail($this->client);
    }

	public function listMessages($pageToken = null, $maxResults = 5)
	{
		$user = Auth::user();
		$params = ['maxResults' => $maxResults];
		if ($pageToken) $params['pageToken'] = $pageToken;
	
		$messagesResponse = $this->service->users_messages->listUsersMessages('me', $params);
	
		$messages = [];
		if ( !$this->account )
			return $messages;
	
		if ($messagesResponse->getMessages()) {
			foreach ($messagesResponse->getMessages() as $msg) {
				$messageId = $msg->getId();
	
				$existing = Email::where('gmail_id', $messageId)->first();
				if ($existing) {
					$messages[] = [
						'id' => $existing->gmail_id,
						'subject' => $existing->subject,
						'from' => $existing->from,
						'summary' => $existing->summary ?? '(processing...)',
						'category' => optional($existing->category)->name ?? 'Uncategorized',
						'date' => $existing->created_at->format('Y-m-d H:i'),
					];
					continue;
				}
	
				// Get metadata for new message
				$message = $this->service->users_messages->get('me', $messageId, ['format' => 'full']);
				$headers = collect($message->getPayload()->getHeaders())->pluck('value', 'name');
				$subject = $headers['Subject'] ?? '(no subject)';
				$from = $headers['From'] ?? '';
				$dateHeader = $headers['Date'] ?? null;

				// Convert "Date" header (RFC 2822) into proper MySQL timestamp
				$parsedDate = null;
				if ($dateHeader) {
					try {
						$parsedDate = \Carbon\Carbon::parse($dateHeader)->setTimezone('UTC');
					} catch (\Exception $e) {
						\Log::warning("Invalid email date format: {$dateHeader}");
					}
				}	

				// Extract body text (simplified)
				$body = $this->getBodyFromPayload($message->getPayload());
	
				// Insert placeholder record
				Email::create([
					'user_id' => $user->id,
					'gmail_id' => $messageId,
					'account_id' => $this->account->id,
					'from' => $from,
					'subject' => $subject,
					'body' => $body,
					'summary' => null, // pending
					'created_at'=> $parsedDate ?? now(),
				]);
	
				// Dispatch background processing
				ProcessEmailJob::dispatch($user->id, $this->account->id, $messageId, $subject, $from, $body);
	
				$messages[] = [
					'id' => $messageId,
					'subject' => $subject,
					'from' => $from,
					'summary' => '(processing...)',
					'category' => 'Pending',
					'date' => $parsedDate ? $parsedDate->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
				];
			}
		}
	
		return [
			'messages' => $messages,
			'nextPageToken' => $messagesResponse->getNextPageToken(),
		];
	}

	/**
	 * Extract body text recursively from Gmail message payload.
	 */
	private function getBodyFromPayload($payload)
	{
		$body = '';
	
		// Case 1: simple message (no parts)
		if ($payload->getBody() && $payload->getBody()->getData()) {
			$body .= base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
		}
	
		// Case 2: multipart message
		if ($payload->getParts()) {
			foreach ($payload->getParts() as $part) {
				// Prefer plain text
				$mimeType = $part->getMimeType();
				if (in_array($mimeType, ['text/plain', 'text/html'])) {
					if ($part->getBody() && $part->getBody()->getData()) {
						$decoded = base64_decode(strtr($part->getBody()->getData(), '-_', '+/'));
						$body .= "\n" . $decoded;
					}
				} elseif ($part->getParts()) {
					// Recursively go deeper
					$body .= $this->getBodyFromPayload($part);
				}
			}
		}
	
		return $body;
	}

    /**
     * Fetch full email content by message ID
     */
    public function getMessage($messageId)
    {
        $message = $this->service->users_messages->get('me', $messageId, ['format' => 'full']);
        $payload = $message->getPayload();

        $headers = collect($payload->getHeaders())->pluck('value', 'name');

        // Decode body
        $body = '';
        if ($payload->getBody()->getData()) {
            $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload->getBody()->getData()));
        } else {
            foreach ($payload->getParts() ?? [] as $part) {
                if ($part->getMimeType() === 'text/html') {
                    $body = base64_decode(str_replace(['-', '_'], ['+', '/'], $part->getBody()->getData()));
                    break;
                }
            }
        }

        return [
            'id' => $messageId,
            'subject' => $headers['Subject'] ?? '(no subject)',
            'from' => $headers['From'] ?? '',
            'date' => $headers['Date'] ?? '',
            'body' => $body,
        ];
    }

    /** Archive message (remove INBOX label) */
    public function archiveMessage($id)
    {
        $mods = new Gmail\ModifyMessageRequest([
            'removeLabelIds' => ['INBOX'],
        ]);
        return $this->service->users_messages->modify('me', $id, $mods);
    }

	/**
	 * Archive multiple Gmail messages (remove INBOX label)
	 */
	public function archiveMessages(array $messageIds)
	{
		if (empty($messageIds)) return false;
	
		try {
			$mods = new \Google_Service_Gmail_BatchModifyMessagesRequest([
				'removeLabelIds' => ['INBOX'],
				'ids' => $messageIds,
			]);
			$this->service->users_messages->batchModify('me', $mods);
			return true;
		} catch (\Exception $e) {
			\Log::error("Failed to batch archive Gmail messages: " . $e->getMessage());
			return false;
		}
	}
	
	/**
	 * Move multiple Gmail messages to Trash
	 */
	public function moveMessagesToTrash(array $messageIds)
	{
		if (empty($messageIds)) return false;
	
		try {
			$mods = new \Google_Service_Gmail_BatchModifyMessagesRequest([
				'addLabelIds' => ['TRASH'],
				'ids' => $messageIds,
			]);
			$this->service->users_messages->batchModify('me', $mods);
			return true;
		} catch (\Exception $e) {
			\Log::error("Failed to batch move Gmail messages to trash: " . $e->getMessage());
			return false;
		}
	}
}
