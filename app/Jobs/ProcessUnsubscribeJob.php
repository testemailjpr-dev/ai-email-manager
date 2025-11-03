<?php
namespace App\Jobs;

use GuzzleHttp\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\GmailAccount;


class ProcessUnsubscribeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $unsubscribeUrl;
    public $accountId;

    public function __construct($unsubscribeUrl, $accountId)
    {
        $this->unsubscribeUrl = $unsubscribeUrl;
        $this->accountId = $accountId;
    }

    public function handle()
    {
        if (!$this->unsubscribeUrl) return;
		if (!$this->accountId) return;

       	$account = GmailAccount::where('id', $this->accountId)->first();
		if (!$account) return;

        $client = new Client(['verify' => false, 'timeout' => 15]);

        try {
            $response = $client->get($this->unsubscribeUrl);
            $html = (string) $response->getBody();

			$crawler = new Crawler($html);
	
			$formNode = $crawler->filter('form')->first();
			if (!$formNode->count()) {
				\Log::warning("No unsubscribe form found at {$this->unsubscribeUrl}");
				return;
			}
	
			// Determine form method and action
			$formAction = $formNode->attr('action') ?? $this->unsubscribeUrl;
			$formMethod = strtolower($formNode->attr('method') ?? 'post');
			$formUrl = str_starts_with($formAction, 'http')
				? $formAction : rtrim($unsubscribeUrl, '/') . '/' . ltrim($formAction, '/');
	
			// Gather all form fields
			$userEmail = $account->email;
			$fields = [];
			$formNode->filter('input')->each(function ($input) use (&$fields, $userEmail) {
				$name = $input->attr('name');
				if (!$name) return;
	
				$type = strtolower($input->attr('type') ?? 'text');
				$value = $input->attr('value') ?? '';
	
				switch ($type) {
					case 'hidden':
					case 'text':
					case 'email':
						$fields[$name] = $value ?: $userEmail;
						break;
					case 'checkbox':
					case 'radio':
						if ($input->attr('checked')) {
							$fields[$name] = $value ?: 'on';
						}
						break;
					default:
						$fields[$name] = $value;
				}
			});
	
			// Fill common expected fields if not present
			$commonfields = [
				'email' => $userEmail,
				'unsubscribe' => 'true',
				'submit' => 'Unsubscribe',
			];
			foreach ($commonfields as $name => $value ) {
				if (!isset($fields[$name])) {
					$fields[$name] = $value;
				}
			}
	
			// Submit form
			$response = $client->request(
				$formMethod,
				$formUrl,
				['form_params' => $fields]
			);
	
			\Log::info("Auto-unsubscribe request sent to {$formUrl} (status: {$response->getStatusCode()}, Unsubscribe URL: {$this->unsubscribeUrl}, Fields: " . json_encode($fields) . ")");
        } catch (\Exception $e) {
            Log::error("Unsubscribe failed for {$this->unsubscribeUrl}: {$e->getMessage()}");
        }
    }
}
