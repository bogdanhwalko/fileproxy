<?php

namespace App\Http\Controllers;

use App\Models\FilePurchase;
use App\Models\ManagedFile;
use App\Services\LemonSqueezyService;
use App\Services\ManagedFileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FilePurchaseController extends Controller
{
    public function paywall(string $token): View
    {
        $file = $this->paidFile($token);

        return view('share.paywall', [
            'file' => $file,
            'shareToken' => $token,
        ]);
    }

    public function checkout(Request $request, string $token, LemonSqueezyService $lemon): RedirectResponse
    {
        $file = $this->paidFile($token);

        if (! $lemon->isConfigured()) {
            return back()->withErrors(['payment' => 'Платіжна система ще не налаштована. Зверніться до власника файлу.']);
        }

        $purchase = DB::transaction(function () use ($file) {
            return FilePurchase::create([
                'managed_file_id' => $file->id,
                'access_token' => $this->uniqueAccessToken(),
                'status' => FilePurchase::STATUS_PENDING,
                'currency' => $file->currency ?: 'USD',
                'amount_cents' => $file->price_cents,
                'max_downloads' => $file->purchase_max_downloads,
            ]);
        });

        try {
            $checkoutUrl = $lemon->createCheckout(
                $file,
                $purchase,
                route('share.access.processing', $purchase->access_token),
            );
        } catch (\Throwable $exception) {
            report($exception);
            $purchase->delete();

            return back()->withErrors(['payment' => 'Не вдалося створити сесію оплати. Спробуйте ще раз.']);
        }

        return redirect()->away($checkoutUrl);
    }

    public function processing(string $accessToken): View|RedirectResponse
    {
        $purchase = FilePurchase::query()
            ->where('access_token', $accessToken)
            ->with('file')
            ->firstOrFail();

        if ($purchase->is_paid) {
            return redirect()->route('share.access.show', $accessToken);
        }

        return view('share.processing', [
            'purchase' => $purchase,
        ]);
    }

    public function status(string $accessToken)
    {
        $purchase = FilePurchase::query()
            ->where('access_token', $accessToken)
            ->firstOrFail();

        return response()->json([
            'status' => $purchase->status,
            'is_paid' => $purchase->is_paid,
            'access_url' => $purchase->is_paid ? route('share.access.show', $accessToken) : null,
        ]);
    }

    public function show(string $accessToken, ManagedFileStorageService $fileStorage): View
    {
        $purchase = $this->activePurchase($accessToken);
        $file = $purchase->file;

        abort_unless($fileStorage->exists($file), 404);

        $content = null;
        $isTruncated = false;

        if ($file->is_text) {
            [$content, $isTruncated] = $fileStorage->readTextPreview($file);
        }

        return view('share.access', [
            'file' => $file,
            'purchase' => $purchase,
            'content' => $content,
            'isTruncated' => $isTruncated,
            'downloadUrl' => route('share.access.download', $accessToken),
            'inlineUrl' => $file->is_image ? route('share.access.inline', $accessToken) : null,
            'rawUrl' => route('share.access.raw', $accessToken),
        ]);
    }

    public function inline(string $accessToken, ManagedFileStorageService $fileStorage)
    {
        $purchase = $this->activePurchase($accessToken);
        $file = $purchase->file;

        abort_unless($file->is_image, 404);
        abort_unless($fileStorage->exists($file), 404);

        return $fileStorage->inlineResponse($file);
    }

    public function raw(string $accessToken, ManagedFileStorageService $fileStorage, ?string $ext = null)
    {
        $purchase = $this->activePurchase($accessToken);
        $file = $purchase->file;

        abort_unless($fileStorage->exists($file), 404);

        $this->consumeDownload($purchase);

        return $fileStorage->inlineResponse($file);
    }

    public function download(string $accessToken, ManagedFileStorageService $fileStorage)
    {
        $purchase = $this->activePurchase($accessToken);
        $file = $purchase->file;

        abort_unless($fileStorage->exists($file), 404);

        $this->consumeDownload($purchase);

        return $fileStorage->downloadResponse($file);
    }

    private function paidFile(string $token): ManagedFile
    {
        $file = ManagedFile::query()
            ->with(['user'])
            ->where('share_token', $token)
            ->firstOrFail();

        abort_if($file->user?->is_blocked, 404);
        abort_unless($file->is_paid && $file->price_cents > 0, 404);
        abort_if($file->share_is_expired, 404);

        return $file;
    }

    private function activePurchase(string $accessToken): FilePurchase
    {
        $purchase = FilePurchase::query()
            ->with(['file.user'])
            ->where('access_token', $accessToken)
            ->firstOrFail();

        abort_if($purchase->file?->user?->is_blocked, 404);
        abort_unless($purchase->is_paid, 402);
        abort_if($purchase->is_expired, 410);
        abort_if($purchase->downloads_exhausted, 410);

        return $purchase;
    }

    private function consumeDownload(FilePurchase $purchase): void
    {
        if ($purchase->max_downloads === null) {
            return;
        }

        $updated = FilePurchase::query()
            ->whereKey($purchase->id)
            ->whereColumn('downloads_count', '<', 'max_downloads')
            ->increment('downloads_count');

        abort_unless($updated === 1, 410);
    }

    private function uniqueAccessToken(): string
    {
        do {
            $token = Str::random(48);
        } while (FilePurchase::query()->where('access_token', $token)->exists());

        return $token;
    }
}
