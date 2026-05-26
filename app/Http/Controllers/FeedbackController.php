<?php

namespace App\Http\Controllers;

use App\Models\FeedbackMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    /**
     * Show the feedback form.
     */
    public function create(Request $request): View
    {
        return view('feedback.create', [
            'types' => FeedbackMessage::TYPES,
            'recentCount' => $request->user()->feedbackMessages()->count(),
        ]);
    }

    /**
     * Save a feedback message from the user.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Rate limit: 5 messages per hour per user — prevent spam
        $key = 'feedback:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withInput()
                ->withErrors(['message' => 'Занадто багато повідомлень. Спробуйте через '.ceil($seconds / 60).' хв.']);
        }

        $validated = $request->validate([
            'type'    => ['required', Rule::in(array_keys(FeedbackMessage::TYPES))],
            'subject' => ['required', 'string', 'max:200', 'min:3'],
            'message' => ['required', 'string', 'max:5000', 'min:10'],
            'contact' => ['nullable', 'string', 'max:200'],
        ], [
            'type.required'    => 'Оберіть тип повідомлення.',
            'subject.required' => 'Введіть короткий заголовок.',
            'subject.min'      => 'Заголовок занадто короткий.',
            'message.required' => 'Опишіть проблему чи ідею.',
            'message.min'      => 'Повідомлення занадто коротке — додайте хоч кілька деталей.',
            'message.max'      => 'Максимум 5000 символів.',
        ]);

        FeedbackMessage::create([
            'user_id'    => $user->id,
            'type'       => $validated['type'],
            'subject'    => $validated['subject'],
            'message'    => $validated['message'],
            'contact'    => $validated['contact'] ?? null,
            'status'     => 'new',
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'ip'         => $request->ip(),
        ]);

        RateLimiter::hit($key, 3600); // 1 hour window

        return redirect()
            ->route('feedback.create')
            ->with('status', 'Дякуємо! Повідомлення надіслано — ми переглянемо найближчим часом.');
    }

    /**
     * Admin: list all feedback messages.
     */
    public function adminIndex(Request $request): View
    {
        abort_unless($request->user()->is_admin, 403);

        $statusFilter = $request->query('status', 'all');
        $typeFilter = $request->query('type', 'all');

        $query = FeedbackMessage::with('user')->latest();

        if (in_array($statusFilter, array_keys(FeedbackMessage::STATUSES), true)) {
            $query->where('status', $statusFilter);
        }
        if (in_array($typeFilter, array_keys(FeedbackMessage::TYPES), true)) {
            $query->where('type', $typeFilter);
        }

        $messages = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => FeedbackMessage::count(),
            'new' => FeedbackMessage::where('status', 'new')->count(),
        ];

        return view('admin.feedback.index', [
            'messages'     => $messages,
            'types'        => FeedbackMessage::TYPES,
            'statuses'     => FeedbackMessage::STATUSES,
            'statusFilter' => $statusFilter,
            'typeFilter'   => $typeFilter,
            'counts'       => $counts,
        ]);
    }

    /**
     * Admin: update message status / notes.
     */
    public function adminUpdate(Request $request, FeedbackMessage $message): RedirectResponse
    {
        abort_unless($request->user()->is_admin, 403);

        $validated = $request->validate([
            'status'      => ['required', Rule::in(array_keys(FeedbackMessage::STATUSES))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $message->update($validated);

        return back()->with('status', 'Збережено.');
    }
}
