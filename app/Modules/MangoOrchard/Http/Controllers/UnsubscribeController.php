<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * One-click newsletter unsubscribe. Reached via a signed URL embedded
 * in every {@see \App\Modules\MangoOrchard\Notifications\NewsletterIssued}
 * email: the route middleware (`signed`) rejects tampered URLs out of
 * hand, so a click just lands here, flips `subscribe_newsletter` off,
 * and renders a confirmation page. No login required — the signature
 * is the proof.
 */
class UnsubscribeController extends Controller
{
    public function newsletter(Request $request, User $user): View
    {
        // The `signed` middleware already verified the URL signature;
        // we just have to flip the preference. Recording the flip is
        // intentional (so the existing telemetry observer fires).
        if ($user->subscribe_newsletter) {
            $user->forceFill(['subscribe_newsletter' => false])->save();
        }

        return view('mango-orchard.unsubscribe', ['user' => $user]);
    }
}
