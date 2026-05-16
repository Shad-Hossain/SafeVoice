<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ComplaintEvidence;
use App\Models\SosEvidence;

class EvidenceController extends Controller
{
    // POST /api/upload_complaint_evidence
    public function uploadComplaint(Request $request)
    {
        $request->validate([
            'complaint_id' => 'required|string',
            'evidence'     => 'required|array',
            'evidence.*'   => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
        ]);

        $complaintId = $request->complaint_id;
        $uploaded    = [];

        foreach ($request->file('evidence') as $file) {
            $filename = 'ev_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads'), $filename);
            $path = 'uploads/' . $filename;

            ComplaintEvidence::create([
                'complaint_id' => $complaintId,
                'file_path'    => $path,
                'file_name'    => $file->getClientOriginalName(),
            ]);

            $uploaded[] = ['file_name' => $file->getClientOriginalName(), 'file_path' => $path];
        }

        return response()->json([
            'success' => true,
            'message' => count($uploaded) . ' file(s) uploaded successfully.',
            'files'   => $uploaded,
        ]);
    }

    // GET /api/get_complaints_evidence?complaint_id=SV-xxx
    public function getComplaintEvidence(Request $request)
    {
        $files = ComplaintEvidence::where('complaint_id', $request->complaint_id)
            ->orderByDesc('uploaded_at')
            ->get();

        return response()->json(['success' => true, 'files' => $files]);
    }

    // POST /api/upload_sos_evidence
    public function uploadSos(Request $request)
    {
        $request->validate([
            'sos_id'     => 'required|integer',
            'evidence'   => 'required|array',
            'evidence.*' => 'file|max:10240',
        ]);

        $uploaded = [];
        foreach ($request->file('evidence') as $file) {
            $filename  = 'sos_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/sos'), $filename);
            $path = 'uploads/sos/' . $filename;

            SosEvidence::create([
                'sos_id'    => $request->sos_id,
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
            ]);

            $uploaded[] = $path;
        }

        return response()->json(['success' => true, 'files' => $uploaded]);
    }
}
