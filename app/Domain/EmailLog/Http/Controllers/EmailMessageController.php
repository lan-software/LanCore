<?php

namespace App\Domain\EmailLog\Http\Controllers;

use App\Domain\EmailLog\Models\EmailMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmailLog\EmailMessageIndexRequest;
use Inertia\Inertia;
use Inertia\Response;

class EmailMessageController extends Controller
{
    public function index(EmailMessageIndexRequest $request): Response
    {
        $this->authorize('viewAny', EmailMessage::class);

        $query = EmailMessage::query();

        if ($search = $request->validated('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('from_address', 'like', "%{$search}%")
                    ->orWhereJsonContains('to_addresses', ['address' => $search]);
            });
        }

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        if ($source = $request->validated('source')) {
            $query->where('source', $source);
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $messages = $query
            ->paginate($request->validated('per_page') ?? 20)
            ->withQueryString();

        $sources = EmailMessage::query()
            ->select('source', 'source_label')
            ->whereNotNull('source')
            ->groupBy('source', 'source_label')
            ->orderBy('source')
            ->get();

        return Inertia::render('admin/emails/Index', [
            'messages' => $messages,
            'filters' => $request->only(['search', 'status', 'source', 'sort', 'direction', 'per_page']),
            'sources' => $sources,
        ]);
    }

    public function show(EmailMessage $emailMessage): Response
    {
        $this->authorize('view', $emailMessage);

        return Inertia::render('admin/emails/Show', [
            'message' => $emailMessage->toArray(),
        ]);
    }
}
