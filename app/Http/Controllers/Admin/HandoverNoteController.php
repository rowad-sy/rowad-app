<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Logistics\HandoverNote;
use App\Models\Admin\Logistics\PurchaseRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

/*
 * محضر تسليم عهدة (GRN) — يُنشأ من طلب شراء مُنفَّذ ويسلّم بنوده إلى
 * مستلم (مستخدم مسجل أو اسم يدوي)، ويُطبع PDF عبر window.print أو Excel
 * بهوية المؤسسة، وفق نموذج RCD-OPR-PS-F04 المرجعي.
 */
class HandoverNoteController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\HandoverNote,view')->only(['show', 'print', 'export']);
        $this->middleware('permission:App\Models\Admin\Logistics\HandoverNote,create')->only(['create', 'store']);
    }

    public function create(PurchaseRequest $purchaseRequest)
    {
        $this->ensureVisible($purchaseRequest);
        abort_if($purchaseRequest->status !== 'executed', 403, 'محضر التسليم يُنشأ فقط لطلب شراء مُنفَّذ.');

        $me = auth()->user();

        $items = $purchaseRequest->items->map(fn ($item) => [
            'item_id' => $item->id,
            'description' => $item->description,
            'serial' => '',
            'unit' => $item->unit,
            'quantity' => $item->quantity,
            'status' => 'جيدة',
        ])->all();

        $users = User::where('type', 'employee')->orderBy('name')->get();

        return view('admin.handover-notes.form', [
            'purchaseRequest' => $purchaseRequest,
            'items' => old('items', $items),
            'users' => $users,
            'deliveredName' => old('delivered_by_name', $me->name),
            'deliveredPosition' => old('delivered_by_position', $me->jobTitle?->title_ar ?? ''),
        ]);
    }

    public function store(Request $request, PurchaseRequest $purchaseRequest)
    {
        $this->ensureVisible($purchaseRequest);
        abort_if($purchaseRequest->status !== 'executed', 403, 'محضر التسليم يُنشأ فقط لطلب شراء مُنفَّذ.');

        $validated = $request->validate([
            'grn_number' => ['required', 'string', 'max:60', Rule::unique('handover_notes', 'grn_number')],
            'handover_date' => 'required|date',
            'delivered_by_name' => 'required|string|max:255',
            'delivered_by_position' => 'nullable|string|max:255',
            'received_by_user_id' => 'nullable|exists:users,id',
            'received_by_name' => 'required_without:received_by_user_id|nullable|string|max:255',
            'received_by_position' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:2000',
            'items.*.serial' => 'nullable|string|max:100',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.status' => 'nullable|string|max:50',
        ]);

        if (! empty($validated['received_by_user_id'])) {
            $receiver = User::find($validated['received_by_user_id']);
            $validated['received_by_name'] = ($validated['received_by_name'] ?? null) ?: $receiver->name;
            $validated['received_by_position'] = ($validated['received_by_position'] ?? null) ?: ($receiver->jobTitle?->title_ar ?? '');
        }

        $note = HandoverNote::create([
            'purchase_request_id' => $purchaseRequest->id,
            'grn_number' => $validated['grn_number'],
            'handover_date' => $validated['handover_date'],
            'delivered_by_user_id' => auth()->id(),
            'delivered_by_name' => $validated['delivered_by_name'],
            'delivered_by_position' => $validated['delivered_by_position'] ?? null,
            'received_by_user_id' => $validated['received_by_user_id'] ?? null,
            'received_by_name' => $validated['received_by_name'],
            'received_by_position' => $validated['received_by_position'] ?? null,
            'note' => $validated['note'] ?? null,
            'items' => array_values(array_map(fn ($row) => [
                'item_id' => $row['item_id'] ?? null,
                'description' => $row['description'],
                'serial' => $row['serial'] ?? '',
                'unit' => $row['unit'] ?? '',
                'quantity' => $row['quantity'] ?? '',
                'status' => $row['status'] ?? '',
            ], $validated['items'])),
            'created_by' => auth()->id(),
        ]);

        $note->logWorkflow('create', null, 'تم إنشاء محضر تسليم رقم '.$note->grn_number.' من الطلب '.$purchaseRequest->request_number);

        return redirect()->route('admin.logistics.handover-notes.show', $note)
            ->with('success', 'تم إنشاء محضر التسليم رقم '.$note->grn_number);
    }

    public function show(HandoverNote $handoverNote)
    {
        $this->ensureVisibleNote($handoverNote);

        $handoverNote->load(['purchaseRequest.center', 'purchaseRequest.project', 'receivedByUser', 'deliveredByUser', 'creator', 'workflowActions.fromUser']);

        return view('admin.handover-notes.show', compact('handoverNote'));
    }

    public function print(HandoverNote $handoverNote)
    {
        $this->ensureVisibleNote($handoverNote);
        $handoverNote->load('purchaseRequest.center', 'purchaseRequest.project');

        return view('admin.handover-notes.print', compact('handoverNote'));
    }

    public function export(HandoverNote $handoverNote)
    {
        $this->ensureVisibleNote($handoverNote);
        $handoverNote->load('purchaseRequest.center', 'purchaseRequest.project');

        return Excel::download(
            new \App\Exports\HandoverNoteExport($handoverNote),
            'GRN-'.$handoverNote->grn_number.'.xlsx',
        );
    }

    public function destroy(HandoverNote $handoverNote)
    {
        $user = auth()->user();
        abort_if($user->type !== 'super-admin' && (int) $handoverNote->created_by !== (int) $user->id, 403, 'حذف المحضر متاح لمنشئه فقط.');

        $prId = $handoverNote->purchase_request_id;
        $handoverNote->delete();

        return redirect()->route('admin.logistics.purchase-requests.show', $prId)
            ->with('success', 'تم حذف محضر التسليم.');
    }

    private function ensureVisible(PurchaseRequest $purchaseRequest): void
    {
        $user = auth()->user();
        if ($user->type !== 'super-admin' && ! $purchaseRequest->isVisibleToUserId($user->id)) {
            abort(403, 'هذا الطلب ليس موجهًا إليك');
        }
    }

    private function ensureVisibleNote(HandoverNote $handoverNote): void
    {
        $handoverNote->loadMissing('purchaseRequest');
        $this->ensureVisible($handoverNote->purchaseRequest);
    }
}
