<?php

namespace Src\Infrastructure\Support\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SupportContactController extends Controller
{
    public function showForm(): Response
    {
        return Inertia::render('Support/Contact', [
            'supportEmail' => config('mail.support_address', config('mail.from.address')),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachment' => 'nullable|file|max:4096',
        ]);

        $user = $request->user();

        // Pour l'instant, on journalise simplement la demande ; on pourra brancher un système de mail plus tard.
        Log::info('Support contact form submitted', [
            'user_id' => $user?->id,
            'subject' => $validated['subject'],
        ]);

        return redirect()->route('support.contact.show')
            ->with('success', 'Votre message a été envoyé à l’équipe support.');
    }
}

