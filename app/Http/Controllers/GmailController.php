<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GmailService;
use Illuminate\Support\Facades\Auth;
use App\Models\Email;
use Illuminate\Support\Facades\DB;


class GmailController extends Controller
{
	public function index(Request $request)
	{
		$user = auth()->user();
		$accounts = $user->gmailAccounts()->get();
        $pageToken = $request->get('pageToken');

		$tokens = array();
		if (!empty($pageToken)) {
			$tmps = explode( '__', $pageToken );
			if( count($tmps) ){
				foreach( $tmps as $tmp ){
					$tmp2 = explode( '_', $tmp );
					if( isset($tmp2[0]) && !empty($tmp2[0]) && isset($tmp2[1]) && !empty($tmp2[1]) ){
						$tokens[$tmp2[1]] = $tmp2[0];
						
					}
				}
			}
		}
	
		$combined = [];
		$nextPageToken = null;
		$nextPageTokens = array();
		$account_counter = 0;
		foreach ($accounts as $account) {
			try {
				$gmail = new GmailService($account->id);
				$token = isset($tokens[$account->id]) ? $tokens[$account->id] : null;
				$res = $gmail->listMessages($token);
				foreach ($res['messages'] as $m) {
					$m['account_id'] = $account->id;
					$m['account_email'] = $account->email;
					$combined[] = $m;
				}
				$nextPageTokens[] = $res['nextPageToken'].'_'.$account->id;
			} catch (\Throwable $e) {
				\Log::error("Error fetching for account {$account->id}: ".$e->getMessage());
			}
		}
	
		usort($combined, fn($a,$b) => strtotime($b['date']) <=> strtotime($a['date']));

		if( count($nextPageTokens) ){
			$nextPageToken = implode( '__', $nextPageTokens );
		}

        return view('emails.index', [
            'emails' => $combined,
            'nextPageToken' => $nextPageToken,
            'showAccount' => count($accounts) > 1 ? 1 : 0,
        ]);
	}

    public function show($id)
    {
		$user = auth()->user();
		$accounts = $user->gmailAccounts()->get();
        $showAccount = count($accounts) > 1 ? 1 : 0;

		$email = Email::select( 'emails.*', 'google_accounts.email as account_email' )->leftJoin('google_accounts', 'emails.account_id', '=', 'google_accounts.id')->where('emails.user_id', $user->id)->where('emails.gmail_id', $id)->first();

		if( $email ){
			$email->body = preg_replace('/(^[^<]+|[^>]+$)/', '', $email->body);
		}

        return view('emails.show', compact('email', 'showAccount'));
    }

}
