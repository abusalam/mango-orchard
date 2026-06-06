<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchemeMonitoring\Models\Attachment;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Permissions;
use App\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller implements HasMiddleware
{
    /**
     * 10 MB ceiling on uploads. Adjust if the team accumulates a need for
     * bigger blobs — bumping it also requires nginx/php_ini tweaks on the
     * server, so keep both in sync.
     */
    private const MAX_KILOBYTES = 10 * 1024;

    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_VIEW])];
    }

    public function storeForScheme(Request $request, Scheme $scheme): RedirectResponse
    {
        Gate::authorize('update', $scheme);

        $this->storeFor($request, $scheme);

        return back()->with('status', 'Attachment uploaded.');
    }

    public function storeForTask(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $this->storeFor($request, $task);

        return back()->with('status', 'Attachment uploaded.');
    }

    public function destroy(Attachment $attachment): RedirectResponse
    {
        $user = request()->user();
        $parent = $attachment->attachable;

        // The uploader can always delete their own. Otherwise the user
        // needs the same authority they'd need to edit the parent, OR the
        // module-management permission / superuser bypass.
        $canDelete = $attachment->uploaded_by === $user->id
            || $user->hasRole(Roles::SUPERUSER)
            || $user->can(Permissions::MONITORING_MANAGE)
            || ($parent !== null && Gate::allows('update', $parent));

        abort_unless($canDelete, 403);

        $attachment->delete();

        return back()->with('status', 'Attachment removed.');
    }

    private function storeFor(Request $request, Scheme|Task $attachable): void
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:'.self::MAX_KILOBYTES],
        ]);

        /** @var \Illuminate\Http\UploadedFile $upload */
        $upload = $data['file'];

        $path = $upload->store('monitoring-attachments', 'public');

        $attachable->attachments()->create([
            'uploaded_by' => $request->user()->id,
            'original_name' => $upload->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $upload->getClientMimeType(),
            'size_bytes' => $upload->getSize(),
        ]);
    }
}
