<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

// ─────────────────────────────────────────────────────────────
//  POST /api/lawyer/ocr-extract
//  Bar Council card image থেকে ID ও name extract করে
//  Tesseract OCR ব্যবহার করে — server এ tesseract-ocr install থাকতে হবে
//  যদি না থাকে তাহলে fallback: user manually type করবে
// ─────────────────────────────────────────────────────────────
class LawyerOcrController extends Controller
{
    public function extract(Request $request)
    {
        $request->validate([
            'bar_council_photo' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        $file = $request->file('bar_council_photo');
        $ext  = strtolower($file->getClientOriginalExtension());

        // temp এ save করো
        $tmpPath = sys_get_temp_dir() . '/bc_ocr_' . uniqid() . '.' . $ext;
        $file->move(sys_get_temp_dir(), basename($tmpPath));

        try {
            // tesseract আছে কিনা check করো
            exec('which tesseract', $out, $code);
            if ($code !== 0) {
                return $this->fallback();
            }

            // PDF হলে image তে convert করো (imagemagick দরকার)
            $imgPath = $tmpPath;
            if ($ext === 'pdf') {
                $pngPath = sys_get_temp_dir() . '/bc_ocr_' . uniqid() . '.png';
                exec("convert -density 200 {$tmpPath}[0] -quality 100 {$pngPath}", $o, $c);
                if ($c === 0 && file_exists($pngPath)) {
                    $imgPath = $pngPath;
                } else {
                    return $this->fallback();
                }
            }

            // OCR চালাও
            $txtPath = sys_get_temp_dir() . '/bc_ocr_out_' . uniqid();
            exec("tesseract {$imgPath} {$txtPath} -l eng 2>/dev/null");
            $text = file_exists($txtPath . '.txt') ? file_get_contents($txtPath . '.txt') : '';

            // cleanup
            @unlink($tmpPath);
            @unlink($imgPath);
            @unlink($txtPath . '.txt');

            if (empty(trim($text))) return $this->fallback();

            // ── Extract Bar Council ID ─────────────────────────────────
            // Bangladesh Bar Council ID format: BCD-YYYY-NNNNN or similar
            $barId   = null;
            $name    = null;

            // Pattern: BCD or BC followed by numbers/dashes
            if (preg_match('/\b(BCD?[-\/]?\d{4}[-\/]?\d{4,6})\b/i', $text, $m)) {
                $barId = strtoupper(preg_replace('/[^A-Z0-9\-]/', '', $m[1]));
            }
            // fallback: any ID-like string with letters and numbers
            if (!$barId && preg_match('/\b([A-Z]{2,4}[-\/]?\d{4}[-\/]?\d{3,6})\b/', $text, $m)) {
                $barId = $m[1];
            }

            // ── Extract Name ───────────────────────────────────────────
            // Common pattern: "Advocate [Name]" or "Adv. [Name]" or "Name: [Name]"
            if (preg_match('/(?:Advocate|Adv\.?|Name\s*:)\s+([A-Z][a-zA-Z\s\.]+?)(?:\n|$|Reg|Bar|Enr)/i', $text, $m)) {
                $name = trim($m[1]);
            }
            // Fallback: longest capitalized word sequence
            if (!$name) {
                preg_match_all('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,3})\b/', $text, $matches);
                if (!empty($matches[1]) && is_array($matches[1])) {
                    usort($matches[1], fn(string $a, string $b): int => strlen($b) - strlen($a));
                    $name = $matches[1][0];
                }
            }

            if (!$barId && !$name) return $this->fallback();

            return response()->json([
                'success'        => true,
                'bar_council_id' => $barId,
                'name'           => $name,
                'raw_text'       => substr($text, 0, 500), // debug only
            ]);

        } catch (\Exception $e) {
            @unlink($tmpPath);
            return $this->fallback();
        }
    }

    private function fallback()
    {
        return response()->json([
            'success' => false,
            'message' => 'Could not auto-extract. Please fill in manually.',
        ], 422);
    }
}