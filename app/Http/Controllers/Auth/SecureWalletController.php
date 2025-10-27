<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmSeedPhraseRequest;
use Carbon\Carbon;
use Exception;
use FurqanSiddiqui\BIP39\BIP39;
use FurqanSiddiqui\BIP39\Exception\WordListException;
use FurqanSiddiqui\BIP39\WordList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Inertia\Response;

class SecureWalletController extends Controller
{
    /**
     * Send a successful login response.
     */
    protected function sendLoginResponse(): RedirectResponse
    {
        $notification = $this->notify('success', __('Wallet secured. Redirecting to your dashboard.'));
        if (Gate::allows('access-admin-dashboard')) {
            return $notification->toRoute('admin.dashboard');
        } elseif (Gate::allows('access-user-dashboard')) {
            return $notification->toRoute('user.dashboard');
        } else {
            Auth::logout();
            return $this->notifyErrorWithValidation(
                'Access Denied',
                ['email' => __('Your account does not have access to any dashboard.')]
            );
        }
    }

    /**
     * Display the secure wallet introduction page.
     */
    public function index()
    {
        return Inertia::render('Auth/SecureWallet/Index');
    }

    /**
     * Handle the action of skipping the seed phrase backup process.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function skip(Request $request): RedirectResponse
    {
        $now = Carbon::now();
        $request->user()->profile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'seed_phrase_status' => 'skipped',
                'seed_phrase_skipped_at' => $now,
                'seed_phrase_expires_at' => $now->copy()->addDays(7),
            ]
        );

        // MODIFIED: Use the modified sendLoginResponse
        return $this->sendLoginResponse();
    }

    /**
     * Show the page where the user is asked to write down their seed phrase.
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function show(Request $request): RedirectResponse|Response
    {
        // Check if the phrase is already stored in session
        if (!$request->session()->has('seed_phrase_words')) {

            try {
                $wordlist = Wordlist::English();
                $bip39 = new BIP39(12, $wordlist);
                $mnemonic = $bip39->Generate();
            } catch (WordListException $e) {
                Log::error('BIP39 Wordlist failed to load/read: ' . $e->getMessage());
                return $this->notify('error', 'Critical wallet generation error: Wordlist failed to load. Please contact support.')->toBack();
            } catch (Exception $e) {
                Log::error('BIP39 Mnemonic generation failed: ' . $e->getMessage());
                return $this->notify('error', 'Critical wallet generation error. Please contact support.')->toBack();
            }

            // Store the phrase in the session
            $request->session()->put('seed_phrase_words', $mnemonic->words);
        }

        // Retrieve the phrase from session
        $phrase = $request->session()->get('seed_phrase_words');

        return Inertia::render('Auth/SecureWallet/Show', [
            'phrase' => $phrase,
        ]);
    }

    /**
     * Show the page where the user is asked to confirm their seed phrase.
     *
     * @param Request $request
     * @return Response
     */
    public function confirm(Request $request)
    {
        // Retrieve the phrase from session
        $phrase = $request->session()->get('seed_phrase_words');

        return Inertia::render('Auth/SecureWallet/Confirm', [
            'phrase' => $phrase,
        ]);
    }

    /**
     * Store the user's entered seed phrase securely.
     *
     * @param ConfirmSeedPhraseRequest $request
     * @return RedirectResponse
     */
    public function update(ConfirmSeedPhraseRequest $request)
    {
        $originalPhrase = Session::get('seed_phrase_words');

        try {

            $encryptedPhrase = Crypt::encryptString(implode(' ', $originalPhrase));
            $request->user()->profile()->updateOrCreate(
                ['user_id' => $request->user()->id],
                [
                    'seed_phrase' => $encryptedPhrase,
                    'seed_phrase_status' => 'generated',
                    'seed_phrase_expires_at' => null
                ]
            );

            Session::forget('seed_phrase_words');
            return $this->sendLoginResponse();
        } catch (Exception $e) {
            Log::error('Seed confirmation failed: ' . $e->getMessage());
            return $this->notify('error', 'Wallet setup failed due to a server error.')->toBack();
        }
    }
}
