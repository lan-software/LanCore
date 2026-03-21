<?php

namespace App\Domain\Shop\Http\Controllers;

use App\Domain\Event\Models\Event;
use App\Domain\Shop\Actions\CreateVoucher;
use App\Domain\Shop\Actions\DeleteVoucher;
use App\Domain\Shop\Actions\UpdateVoucher;
use App\Domain\Shop\Http\Requests\StoreVoucherRequest;
use App\Domain\Shop\Http\Requests\UpdateVoucherRequest;
use App\Domain\Shop\Http\Requests\VoucherIndexRequest;
use App\Domain\Shop\Models\Voucher;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function __construct(
        private readonly CreateVoucher $createVoucher,
        private readonly UpdateVoucher $updateVoucher,
        private readonly DeleteVoucher $deleteVoucher,
    ) {}

    public function index(VoucherIndexRequest $request): Response
    {
        $this->authorize('viewAny', Voucher::class);

        $query = Voucher::with('event');

        if ($search = $request->validated('search')) {
            $query->where('code', 'ilike', "%{$search}%");
        }

        $sortColumn = $request->validated('sort') ?? 'created_at';
        $sortDirection = $request->validated('direction') ?? 'desc';
        $query->orderBy($sortColumn, $sortDirection);

        $vouchers = $query->paginate($request->validated('per_page') ?? 20)->withQueryString();

        return Inertia::render('vouchers/Index', [
            'vouchers' => $vouchers,
            'filters' => $request->only(['search', 'sort', 'direction', 'per_page']),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Voucher::class);

        return Inertia::render('vouchers/Create', [
            'events' => Event::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreVoucherRequest $request): RedirectResponse
    {
        $this->authorize('create', Voucher::class);

        $this->createVoucher->execute($request->validated());

        return redirect()->route('vouchers.index');
    }

    public function edit(Voucher $voucher): Response
    {
        $this->authorize('update', $voucher);

        return Inertia::render('vouchers/Edit', [
            'voucher' => $voucher->load('event'),
            'events' => Event::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateVoucherRequest $request, Voucher $voucher): RedirectResponse
    {
        $this->authorize('update', $voucher);

        $this->updateVoucher->execute($voucher, $request->validated());

        return back();
    }

    public function destroy(Voucher $voucher): RedirectResponse
    {
        $this->authorize('delete', $voucher);

        $this->deleteVoucher->execute($voucher);

        return redirect()->route('vouchers.index');
    }
}
