<?php

namespace App\Http\Controllers\Auth;

use App;
use Validator;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Webpatser\Uuid\Uuid;

class AccountController extends Controller
{
    protected $redirectTo = '/wow';

    // GET
    # Subscriptions
    public function getSubscriptions() {
        # Subscriptions
        $subscriptions = App\Subscriptions::join('customers', 'customers.customer_id', '=', 'subscriptions.customer_id')
            ->where('customers.user_id', Auth::user()->user_id)
            ->orderBy('subscriptions.created_at', 'desc')
            ->get();

        # Data
        $data = array();

        $data["page_title"] = 'Orders';

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
        return view('pages.account.subscriptions.all')
            ->with('subscriptions', $subscriptions)
            ->with('data', $data);
    }

    # Subscription
    public function getSubscription($subscription_id) {
        # Subscription
        $subscription = App\Subscriptions::where('subscription_id', $subscription_id)
            ->join('customers', 'customers.customer_id', '=', 'subscriptions.customer_id')
            ->where('customers.user_id', Auth::user()->user_id)
            ->with('customer')
            ->get();

        if ($subscription->count()==0) {
            abort(404);
        }
        $subscription = $subscription->first();

        # Customer
        if ($subscription->customer) {
            $customer = $subscription->customer;
        } else {
            abort(404);
        }

        # Data
        $data = array();
        $data["page_title"] = 'Medlemsskab';

        $data["meta_description"] = '';
        $data["meta_keywords"] = '';

        $data["body_id"] = '';
        $data["body_class"] = 'account';

        $data["og"] = array();
        $data["og"]["title"] = '';
        $data["og"]["url"] = '';
        $data["og"]["description"] = '';
        $data["og"]["image"] = '';

        # Return
        return view('pages.account.subscriptions.single')
            ->with('subscription', $subscription)
            ->with('customer', $customer)
            ->with('data', $data);
    }

    # Invoices
    public function getInvoices() {
        # Invoices
        $invoices = App\Invoices::join('customers', 'customers.customer_id', '=', 'invoices.customer_id')
            ->where('customers.user_id', Auth::user()->user_id)
            ->orderBy('invoices.created_at', 'desc')
            ->get();

        # Data
        $data = array();

        $data["page_title"] = 'Orders';

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
        return view('pages.account.invoices')
            ->with('invoices', $invoices)
            ->with('data', $data);
    }

    # Login
    public function getLogin() {
        # Data
        $data = array();

        $data["page_title"] = 'Login';

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
        return view('pages.account.login')
            ->with('data', $data);
    }

    # Log Out
    public function getLogout() {
        # Log Out
        if (Auth::check()) {
            Auth::logout();
        }

        # Return
        return redirect()->route('account');
    }

    # Account
    public function getAccount() {
        # Data
        $data = array();

        $data["page_title"] = trans('account.home.heading');

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
        return view('pages.account.index')
            ->with('data', $data);
    }

    // POST
    # Update Subscription (POST)
    public function postUpdateSubscription(Request $request, $subscription_id) {
        # Subscription
        $subscription = App\Subscriptions::where('subscription_id', $subscription_id)
            ->join('customers', 'customers.customer_id', '=', 'subscriptions.customer_id')
            ->where('customers.user_id', Auth::user()->user_id)
            ->with('customer')
            ->get();

        if ($subscription->count()==0) {
            abort(404);
        }
        $subscription = $subscription->first();

        # Valrules
        $valrules = ['renewal' => 'required'];

        # Make Validator
        $validator = Validator::make($request->all(), $valrules);

        # Validator fails
        if ($validator->fails()) {
            return $validator->messages()->toArray();
        } else {
            # Variables
            $renewal = e($request->input('renewal'));

            # Update
            if ($renewal=='off') {
                $subscription->renew = 0;
            } else {
                $subscription->renew = 1;
            }

            # Save & Return
            if ($subscription->save()) {
                # Global
                $global = 'Congratulations! Your changes was saved.';
            } else {
                # Global
                $global = 'Oops! An error occured. Please, try again.';
            }

            # Return
            return redirect()->route('account-subscription', $subscription->subscription_id)
                ->with('global', $global);
        }
    }

    # Login (POST)
    public function postLogin(Request $request) {
        # Valrules
        $valrules = [
            'email'     => 'required|email',
            'password'  => 'required'
        ];

        # Make Validator
        $validator = Validator::make($request->all(), $valrules);

        # Validator fails
        if ($validator->fails()) {
            return $validator->messages();
        } else {
            # Variables
            $email = e($request->input('email'));
            $password = $request->input('password');

            # Authenticate
            if (Auth::attempt(['email' => $email, 'password' => $password])) {
                # Store Login
                $login = App\UserLogins::create([
                    'login_id'      => (string)Uuid::generate(),
                    'user_id'       => Auth::user()->user_id,
                    'client_ip'     => $request->getClientIp(),
                    'platform_id'   => pf('platform_id')
                ]);

                # Redirect
                return redirect()->intended(route('account'));
            } else {
                # Global
                $global = 'De indtastede oplysninger blev ikke fundet...';

                # Return
                return redirect()->route('account-login')->with('global', $global);
            }
        }
    }
}
