<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\GmailAccount;


class GmailAccountController extends Controller
{
	public function index()
	{
		$accounts = GmailAccount::where('user_id', Auth::id())->get();
	
		return view('gmail_accounts.index', compact('accounts'));
	}

}
