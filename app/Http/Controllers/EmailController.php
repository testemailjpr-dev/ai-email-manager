<?php
namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\Category;
use App\Services\GmailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessUnsubscribeJob;


class EmailController extends Controller
{
    public function index($categoryId)
    {
		$user = auth()->user();
		$accounts = $user->gmailAccounts()->get();
        $showAccount = count($accounts) > 1 ? 1 : 0;

		$emails = Email::select( 'emails.*', 'google_accounts.email as account_email' )->leftJoin('google_accounts', 'emails.account_id', '=', 'google_accounts.id')->where('emails.user_id', $user->id)->where('emails.category_id', $categoryId)->paginate(10);
			
		$category = Category::where('user_id', $user->id)->findOrFail($categoryId);
		
        return view('emails.cat_index', compact('emails', 'category', 'showAccount'));
    }
	
	public function bulkAction(Request $request)
	{
		$action = $request->input('action');
		$emailIds = $request->input('email_ids', []);
	
		if (empty($emailIds)) {
			return back()->with('error', 'No emails selected.');
		}
	
		$user = auth()->user();
	
		$emailsGrouped = Email::whereIn('gmail_id', $emailIds)->where('user_id', $user->id)
		   ->get()->groupBy('account_id')->toArray();

		try {
			if ($action === 'delete') {
				if (count($emailsGrouped)) {
					foreach ($emailsGrouped as $account_id => $emails) {
						$gmailIds = $emails->pluck('gmail_id')->toArray();
				
						// Initialize Gmail service for that account
						$gmailService = new GmailService($account_id);
				
						// Move all emails for this account to trash
						$gmailService->moveMessagesToTrash($gmailIds);
					}
				}
	
				// Remove local records
				Email::whereIn('gmail_id', $emailIds)->delete();
	
				return back()->with('success', 'Selected emails moved to Trash successfully.');
			}
	
			if ($action === 'unsubscribe') {
				$unsubscribeCount = 0;
				if (count($emailsGrouped)) {
					foreach ($emailsGrouped as $account_id => $emails) {
						foreach ($emails as $email) {
							$unsubscribeUrl = null;
							if (preg_match('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(?:Unsubscribe|unsubscribe)[^<]*<\/a>/i', $email['body'], $m)) {
								$unsubscribeUrl = $m[1];
							} elseif (preg_match('/https?:\/\/[^ \n]*unsubscribe[^ \n>]*/i', $email['body'], $m)) {
								$unsubscribeUrl = $m[0];
							}
							if ($unsubscribeUrl) {
								$unsubscribeCount++;

								// Dispatch background processing
								ProcessUnsubscribeJob::dispatch($unsubscribeUrl, $account_id);
							}
						}
					}
				}

				if ($unsubscribeCount) {
					return back()->with('success', $unsubscribeCount.' emails queued for unsubscribe processing.');
				} else {
					return back()->with('success', 'No emails found that contains unsubscribe link.');
				}
			}
	
			return back()->with('error', 'Invalid action.');
		} catch (\Exception $e) {
			\Log::error('Bulk Gmail action failed: ' . $e->getMessage());
			return back()->with('error', 'An error occurred while processing Gmail actions.' . $e->getMessage());
		}
	}

    public function actionTrash($id)
    {
        $email = Email::where('user_id', auth()->id())->where('gmail_id', $id)->first();
		if (!$email) {
        	return redirect()->back()->with('error', 'Email not found.');
    	}
		try {
			// Initialize Gmail API client
			$gmail = new GmailService($email->account_id);
	
			// Move message to Trash in Gmail
			$gmail->service->users_messages->trash('me', $email->gmail_id);
	
			// Optionally, mark as deleted in your DB
			$email->delete();
	
			return redirect()
				->route('category.emails.index', $email->category_id)
				->with('success', 'Email moved to Trash successfully.');
		} catch (\Exception $e) {
			\Log::error("Gmail trash error: " . $e->getMessage());
			return redirect()->back()
				->with('error', 'Failed to move email to Trash: ' . $e->getMessage());
		}
    }

    public function actionUnsubscribe($id)
    {
        $email = Email::where('user_id', auth()->id())->where('gmail_id', $id)->first();
		if (!$email) {
        	return redirect()->back()->with('error', 'Email not found.');
    	}
		try {
			$unsubscribeUrl = null;
			if (preg_match('/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(?:Unsubscribe|unsubscribe)[^<]*<\/a>/i', $email->body, $m)) {
				$unsubscribeUrl = $m[1];
			} elseif (preg_match('/https?:\/\/[^ \n]*unsubscribe[^ \n>]*/i', $email['body'], $m)) {
				$unsubscribeUrl = $m[0];
			}
			if ($unsubscribeUrl) {
				// Dispatch background processing
				ProcessUnsubscribeJob::dispatch($unsubscribeUrl, $email->account_id);

				return redirect()->back()
					->with('success', 'Email queued for unsubscribe processing.');
			} else {
				return redirect()->back()
					->with('error', 'Not found any unsubscribe link.');
			}
		} catch (\Exception $e) {
			\Log::error("Gmail trash error: " . $e->getMessage());
			return redirect()->back()
				->with('error', 'Failed to queued for unsubscribe processing.: ' . $e->getMessage());
		}
    }
}
