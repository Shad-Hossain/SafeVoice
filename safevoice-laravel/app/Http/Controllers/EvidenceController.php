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
            'evidence'   => 'nullable|array',
            'evidence.*' => 'file|max:10240',
        ]);

        // crime_type ও description SosAlert এ update করব
        $sosId = $request->sos_id;
        $updateData = [];
        if ($request->filled('crime_type'))    $updateData['crime_type']    = $request->crime_type;
        if ($request->filled('description'))   $updateData['description']   = $request->description;
        // Anonymous user এর phone/name update
        if ($request->filled('contact_phone')) $updateData['contact_phone'] = $request->contact_phone;
        if ($request->filled('contact_name'))  $updateData['contact_name']  = $request->contact_name;
        if (!empty($updateData)) {
            \App\Models\SosAlert::where('id', $sosId)->update($updateData);
        }

        // Evidence files (optional)
        $uploaded = [];
        if ($request->hasFile('evidence')) {
            if (!file_exists(public_path('uploads/sos'))) {
                mkdir(public_path('uploads/sos'), 0755, true);
            }
            foreach ($request->file('evidence') as $file) {
                $filename  = 'sos_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/sos'), $filename);
                $path = 'uploads/sos/' . $filename;

                SosEvidence::create([
                    'sos_id'    => $sosId,
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                ]);

                $uploaded[] = $path;
            }
        }

        return response()->json(['success' => true, 'files' => $uploaded]);
    }
}
