<?php

namespace App\Http\Controllers\Auth;

use App;
use Uuid;
use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ResetPasswordController extends Controller
{
    // GET
    # Reset Password
    public function getResetPassword() {
        # Data
        $data = array();

        $data["page_title"] = 'Request new password';

        $data["meta_description"] = '';
        $data["meta_keywords"] = '';

        $data["body_id"] = '';
        $data["body_class"] = '';

        $data["og"] = array();
        $data["og"]["title"] = '';
        $data["og"]["url"] = '';
        $data["og"]["description"] = '';
        $data["og"]["image"] = '';

        # Return
        return view('pages.recovery.reset-password')
            ->with('data', $data);
    }

    // POST
    # Reset Password (POST)
    public function postResetPassword(Request $request) {
        # Valrules
        $valrules = ['email' => 'required'];

        # Make Validator
        $validator = Validator::make($request->all(), $valrules);

        # Validator fails
        if ($validator->fails()) {
            return $validator->messages();
        } else {
            # Variables
            $email = e($request->input('email'));

            # Exists
            $check = App\Users::where('email', $email)->select('email', 'user_id')->get();
            if ($check->count()==0) {
                # Global
                $global = 'Unfortunately, there were no registered users with the mail '.$email.'. Please, make sure you entered the e-mail correct, or contact our customer service.';
            } else {
                $user = $check->first();

                # Create Key
                $key = str_random(75);

                # Delete Old Keys
                $old_keys = App\RecoveryKeys::where('user_id', $user->user_id)->delete();

                # Key ID
                $key_id = Uuid::generate();

                # Expiry Date
                $expiry_date = Carbon::now(config('platform.timezone'))->addHours(12)->format("Y-m-d H:i:s");

                # Insert New Key
                $create = App\RecoveryKeys::create([
                    'key_id'        => $key_id,
                    'user_id'       => $user->user_id,
                    'recovery_key'  => $key,
                    'expiry_date'   => $expiry_date
                ]);

                # If Created? Add New Mail Sending
                if ($create) {
                    # Create Mail Sending
                    $sending = App\MailRecoverySendings::create([
                        'sending_id' => Uuid::generate(),
                        'key_id'     => $key_id,
                        'user_id'    => $user->user_id
                    ]);

                    # If Created? Redirect
                    if ($sending) {
                        # Global
                        $global = [
                            'title' => 'Vi har sendt dig en mail!',
                            'body'  => 'Vi har nu sendt dig en ny mail med et link hvorfra du kan oprette et nyt kodeord. Bemærk at dette link udløber: '.$expiry_date.'.'
                        ];
                    } else {
                        # Global
                        $global = 'Der opstod en fejl, prøv igen...';
                    }
                } else {
                    # Global
                    $global = 'Der opstod en fejl, prøv igen...';
                }
            }

            # Redirect
            return redirect()->route('reset-password')->with('global', $global);
        }
    }
}
