<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminAssessmentQuestionController extends Controller
{
    /**
     * List questions for an assessment with filters & search
     * GET /api/admin/assessments/{assessmentId}/questions
     */
    public function index(Request $request, $assessmentId)
    {
        $assessment = Assessment::findOrFail($assessmentId);

        $query = AssessmentQuestion::where('assessment_id', $assessment->id);

        if ($request->filled('category') && $request->category !== 'All') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('question', 'like', "%{$search}%")
                  ->orWhere('option_a', 'like', "%{$search}%")
                  ->orWhere('option_b', 'like', "%{$search}%")
                  ->orWhere('option_c', 'like', "%{$search}%")
                  ->orWhere('option_d', 'like', "%{$search}%");
            });
        }

        $questions = $query->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($request->input('per_page', 20));

        // Make correct_answer visible for admin management
        $questions->getCollection()->transform(function ($q) {
            $q->makeVisible(['correct_answer']);
            return $q;
        });

        $categories = AssessmentQuestion::where('assessment_id', $assessment->id)
            ->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $questions,
            'assessment' => $assessment,
            'categories' => $categories,
        ]);
    }

    /**
     * Create a single question
     * POST /api/admin/assessments/{assessmentId}/questions
     */
    public function store(Request $request, $assessmentId)
    {
        $assessment = Assessment::findOrFail($assessmentId);

        $validated = $request->validate([
            'category'       => 'required|string|max:100',
            'question'       => 'required|string|max:5000',
            'option_a'       => 'required|string|max:1000',
            'option_b'       => 'required|string|max:1000',
            'option_c'       => 'required|string|max:1000',
            'option_d'       => 'required|string|max:1000',
            'correct_answer' => 'required|in:A,B,C,D,a,b,c,d',
            'explanation'    => 'nullable|string|max:5000',
            'marks'          => 'nullable|integer|min:1|max:100',
            'order'          => 'nullable|integer|min:1',
        ]);

        $maxOrder = AssessmentQuestion::where('assessment_id', $assessment->id)->max('order') ?? 0;

        $question = AssessmentQuestion::create([
            'assessment_id'  => $assessment->id,
            'order'          => $validated['order'] ?? ($maxOrder + 1),
            'category'       => trim($validated['category']),
            'question'       => trim($validated['question']),
            'option_a'       => trim($validated['option_a']),
            'option_b'       => trim($validated['option_b']),
            'option_c'       => trim($validated['option_c']),
            'option_d'       => trim($validated['option_d']),
            'correct_answer' => strtoupper($validated['correct_answer']),
            'explanation'    => !empty($validated['explanation']) ? trim($validated['explanation']) : null,
            'marks'          => $validated['marks'] ?? 1,
        ]);

        $this->syncAssessmentMetrics($assessment);

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully.',
            'data'    => $question->makeVisible(['correct_answer']),
        ], 201);
    }

    /**
     * Show single question detail (including correct_answer for admin)
     * GET /api/admin/assessments/questions/{id}
     */
    public function show($id)
    {
        $question = AssessmentQuestion::with('assessment')->findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $question->makeVisible(['correct_answer']),
        ]);
    }

    /**
     * Update question
     * PUT /api/admin/assessments/questions/{id}
     */
    public function update(Request $request, $id)
    {
        $question = AssessmentQuestion::findOrFail($id);

        $validated = $request->validate([
            'category'       => 'sometimes|required|string|max:100',
            'question'       => 'sometimes|required|string|max:5000',
            'option_a'       => 'sometimes|required|string|max:1000',
            'option_b'       => 'sometimes|required|string|max:1000',
            'option_c'       => 'sometimes|required|string|max:1000',
            'option_d'       => 'sometimes|required|string|max:1000',
            'correct_answer' => 'sometimes|required|in:A,B,C,D,a,b,c,d',
            'explanation'    => 'nullable|string|max:5000',
            'marks'          => 'nullable|integer|min:1|max:100',
            'order'          => 'nullable|integer|min:1',
        ]);

        if (isset($validated['correct_answer'])) {
            $validated['correct_answer'] = strtoupper($validated['correct_answer']);
        }

        $question->update($validated);

        if ($question->assessment) {
            $this->syncAssessmentMetrics($question->assessment);
        }

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data'    => $question->makeVisible(['correct_answer']),
        ]);
    }

    /**
     * Delete question
     * DELETE /api/admin/assessments/questions/{id}
     */
    public function destroy($id)
    {
        $question = AssessmentQuestion::findOrFail($id);
        $assessment = $question->assessment;
        $question->delete();

        if ($assessment) {
            $this->syncAssessmentMetrics($assessment);
        }

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
        ]);
    }

    /**
     * Download pre-formatted Excel template for importing questions
     * GET /api/admin/assessments/questions/sample-template
     */
    public function sampleTemplate(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Questions Template');

        $headers = [
            'Category',
            'Question',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Correct Answer (A/B/C/D)',
            'Marks',
            'Explanation'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $sampleData = [
            [
                'Web Development',
                'Which HTML5 element is used to specify a header for a document or section?',
                '<top>',
                '<header>',
                '<head>',
                '<section-head>',
                'B',
                1,
                'The <header> element represents introductory content.'
            ],
            [
                'Digital Marketing',
                'What does SEO stand for in digital marketing?',
                'Search Engine Optimization',
                'Social Engine Operation',
                'Systematic Electronic Outreach',
                'Site Efficiency Organization',
                'A',
                1,
                'SEO stands for Search Engine Optimization.'
            ],
            [
                'Graphic Designing',
                'Which color model is used for digital screens and web displays?',
                'CMYK',
                'RGB',
                'Pantone',
                'Monochrome',
                'B',
                1,
                'RGB is an additive color model for electronic displays.'
            ]
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Style headers
        $sheet->getStyle('A1:I1')->getFont()->setBold(true);
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="assessment_questions_template.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Preview and validate uploaded questions file before inserting
     * POST /api/admin/assessments/{assessmentId}/questions/preview-import
     */
    public function previewImport(Request $request, $assessmentId)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,json,txt|max:10240',
        ]);

        $assessment = Assessment::findOrFail($assessmentId);
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $rows = [];
        if ($extension === 'json') {
            $jsonContent = file_get_contents($file->getRealPath());
            $decoded = json_decode($jsonContent, true);
            $rows = is_array($decoded) ? $decoded : [];
        } else {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray(null, true, true, true);

            if (!empty($data)) {
                // Header row check
                $firstRow = array_values(array_shift($data));
                foreach ($data as $r) {
                    $rowVals = array_values($r);
                    if (empty(array_filter($rowVals))) continue;

                    $rows[] = [
                        'category'       => $rowVals[0] ?? '',
                        'question'       => $rowVals[1] ?? '',
                        'option_a'       => $rowVals[2] ?? '',
                        'option_b'       => $rowVals[3] ?? '',
                        'option_c'       => $rowVals[4] ?? '',
                        'option_d'       => $rowVals[5] ?? '',
                        'correct_answer' => $rowVals[6] ?? '',
                        'marks'          => $rowVals[7] ?? 1,
                        'explanation'    => $rowVals[8] ?? '',
                    ];
                }
            }
        }

        // Validate rows
        $validRows = [];
        $invalidRows = [];
        $duplicateRows = [];
        $categoryCounts = [];

        // Existing questions in database for duplicate detection
        $existingQuestions = AssessmentQuestion::where('assessment_id', $assessment->id)
            ->pluck('id', 'question')
            ->mapWithKeys(fn($id, $q) => [strtolower(trim($q)) => $id])
            ->toArray();

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2;
            $cat = trim((string)($row['category'] ?? ''));
            $qText = trim((string)($row['question'] ?? ''));
            $optA = trim((string)($row['option_a'] ?? ''));
            $optB = trim((string)($row['option_b'] ?? ''));
            $optC = trim((string)($row['option_c'] ?? ''));
            $optD = trim((string)($row['option_d'] ?? ''));
            $correctAns = strtoupper(trim((string)($row['correct_answer'] ?? '')));
            $marks = is_numeric($row['marks'] ?? null) ? (int)$row['marks'] : 1;
            $exp = trim((string)($row['explanation'] ?? ''));

            $errors = [];
            if (empty($cat)) $errors[] = "Category is required.";
            if (empty($qText)) $errors[] = "Question text is required.";
            if (empty($optA)) $errors[] = "Option A is required.";
            if (empty($optB)) $errors[] = "Option B is required.";
            if (empty($optC)) $errors[] = "Option C is required.";
            if (empty($optD)) $errors[] = "Option D is required.";
            if (!in_array($correctAns, ['A', 'B', 'C', 'D'])) $errors[] = "Correct Answer must be A, B, C, or D (Got: '{$correctAns}').";

            $isDuplicate = isset($existingQuestions[strtolower($qText)]);

            $rowPayload = [
                'row_number'     => $rowNum,
                'category'       => $cat,
                'question'       => $qText,
                'option_a'       => $optA,
                'option_b'       => $optB,
                'option_c'       => $optC,
                'option_d'       => $optD,
                'correct_answer' => $correctAns,
                'marks'          => $marks,
                'explanation'    => $exp,
                'is_duplicate'   => $isDuplicate,
                'existing_id'    => $isDuplicate ? $existingQuestions[strtolower($qText)] : null,
                'errors'         => $errors,
            ];

            if (!empty($errors)) {
                $invalidRows[] = $rowPayload;
            } else {
                $validRows[] = $rowPayload;
                if ($isDuplicate) {
                    $duplicateRows[] = $rowPayload;
                }
                $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'total_found'      => count($rows),
                'valid_count'      => count($validRows),
                'invalid_count'    => count($invalidRows),
                'duplicate_count'  => count($duplicateRows),
                'category_counts'  => $categoryCounts,
                'valid_rows'       => $validRows,
                'invalid_rows'     => $invalidRows,
                'duplicate_rows'   => $duplicateRows,
            ]
        ]);
    }

    /**
     * Execute transactional import of questions into database
     * POST /api/admin/assessments/{assessmentId}/questions/import
     */
    public function import(Request $request, $assessmentId)
    {
        $request->validate([
            'questions'          => 'required|array|min:1',
            'duplicate_strategy' => 'nullable|in:skip,update,create_new',
        ]);

        $assessment = Assessment::findOrFail($assessmentId);
        $strategy = $request->input('duplicate_strategy', 'update');
        $rawQuestions = $request->input('questions', []);

        $importedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        DB::transaction(function () use (
            $assessment, $rawQuestions, $strategy,
            &$importedCount, &$updatedCount, &$skippedCount
        ) {
            $maxOrder = AssessmentQuestion::where('assessment_id', $assessment->id)->max('order') ?? 0;

            foreach ($rawQuestions as $item) {
                $qText = trim((string)($item['question'] ?? ''));
                if (empty($qText)) continue;

                $existing = AssessmentQuestion::where('assessment_id', $assessment->id)
                    ->where('question', $qText)
                    ->first();

                $payload = [
                    'category'       => trim((string)($item['category'] ?? 'General')),
                    'question'       => $qText,
                    'option_a'       => trim((string)($item['option_a'] ?? '')),
                    'option_b'       => trim((string)($item['option_b'] ?? '')),
                    'option_c'       => trim((string)($item['option_c'] ?? '')),
                    'option_d'       => trim((string)($item['option_d'] ?? '')),
                    'correct_answer' => strtoupper(trim((string)($item['correct_answer'] ?? 'A'))),
                    'explanation'    => !empty($item['explanation']) ? trim((string)$item['explanation']) : null,
                    'marks'          => is_numeric($item['marks'] ?? null) ? (int)$item['marks'] : 1,
                ];

                if ($existing) {
                    if ($strategy === 'skip') {
                        $skippedCount++;
                        continue;
                    } elseif ($strategy === 'update') {
                        $existing->update($payload);
                        $updatedCount++;
                        continue;
                    }
                }

                // Create new
                $maxOrder++;
                $payload['assessment_id'] = $assessment->id;
                $payload['order'] = $maxOrder;
                AssessmentQuestion::create($payload);
                $importedCount++;
            }

            $this->syncAssessmentMetrics($assessment);
        });

        return response()->json([
            'success' => true,
            'message' => "Import completed: {$importedCount} created, {$updatedCount} updated, {$skippedCount} skipped.",
            'data' => [
                'imported_count' => $importedCount,
                'updated_count'  => $updatedCount,
                'skipped_count'  => $skippedCount,
                'total_in_db'    => AssessmentQuestion::where('assessment_id', $assessment->id)->count(),
            ]
        ]);
    }

    /**
     * Helper to keep assessment metadata synchronized with its questions
     */
    private function syncAssessmentMetrics(Assessment $assessment): void
    {
        $questions = AssessmentQuestion::where('assessment_id', $assessment->id)->get();
        $totalQ = $questions->count();
        $totalMarks = $questions->sum('marks');

        $breakdown = $questions->groupBy('category')->map(function ($items, $cat) {
            return [
                'category' => $cat,
                'count'    => $items->count(),
                'marks'    => $items->sum('marks'),
            ];
        })->values()->toArray();

        $assessment->update([
            'total_questions'    => $totalQ,
            'total_marks'        => $totalMarks,
            'category_breakdown' => $breakdown,
        ]);
    }
}
