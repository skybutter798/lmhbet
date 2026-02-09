<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycSubmission;
use App\Models\KycProfile;
use App\Support\AdminAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminKycController extends Controller
{
    public function index()
    {
        $subs = $this->applyFilters(KycSubmission::query()->with('user'), request())
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admins.kyc.index', compact('subs'));
    }

    public function search(Request $request)
    {
        $subs = $this->applyFilters(KycSubmission::query()->with('user'), $request)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return response()->json([
            'html' => view('admins.kyc.partials.table', compact('subs'))->render(),
            'pagination' => $subs->links()->render(),
            'total' => $subs->total(),
        ]);
    }

    public function approve(KycSubmission $submission)
    {
        return DB::transaction(function () use ($submission) {
            $submission->status = KycSubmission::STATUS_APPROVED;
            $submission->verified_at = now();
            $submission->save();

            // upsert profile
            KycProfile::updateOrCreate(
                ['user_id' => $submission->user_id],
                [
                    'status' => KycProfile::STATUS_APPROVED,
                    'remark' => 'Approved by admin',
                ]
            );

            AdminAudit::log('kyc.approve', $submission->user_id, 'kyc_submissions', $submission->id, [
                'submission_id' => $submission->id,
            ]);

            return response()->json(['ok' => true]);
        });
    }

    public function reject(Request $request, KycSubmission $submission)
    {
        $data = $request->validate([
            'remarks' => ['nullable','string','max:255'],
        ]);

        return DB::transaction(function () use ($submission, $data) {
            $submission->status = KycSubmission::STATUS_REJECTED;
            $submission->remarks = $data['remarks'] ?? 'Rejected by admin';
            $submission->verified_at = now();
            $submission->save();

            KycProfile::updateOrCreate(
                ['user_id' => $submission->user_id],
                [
                    'status' => KycProfile::STATUS_REJECTED,
                    'remark' => $submission->remarks,
                ]
            );

            AdminAudit::log('kyc.reject', $submission->user_id, 'kyc_submissions', $submission->id, [
                'submission_id' => $submission->id,
                'remarks' => $submission->remarks,
            ]);

            return response()->json(['ok' => true]);
        });
    }

    private function applyFilters($query, Request $request)
    {
        $q = trim((string)$request->query('q',''));
        $status = $request->query('status','pending'); // pending default

        if ($q !== '') {
            $query->whereHas('user', function ($u) use ($q) {
                $u->where('username','like',"%{$q}%")
                  ->orWhere('email','like',"%{$q}%")
                  ->orWhere('phone','like',"%{$q}%");
            });
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        return $query;
    }
}
