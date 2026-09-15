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
        $assessment = $this->resolveAssessment($assessmentId);

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

        $perPage = $request->input('per_page', 100);
        if ($perPage === 'all' || (int)$perPage >= 1000) {
            $allQuestions = $query->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->get();
            $allQuestions->transform(function ($q) {
                $q->makeVisible(['correct_answer']);
                return $q;
            });
            $questions = [
                'data'         => $allQuestions,
                'total'        => $allQuestions->count(),
                'per_page'     => $allQuestions->count(),
                'current_page' => 1,
                'last_page'    => 1,
                'from'         => 1,
                'to'           => $allQuestions->count(),
            ];
        } else {
            $perPageInt = max(1, min(500, (int)$perPage));
            $paginated = $query->orderBy('order', 'asc')
                ->orderBy('id', 'asc')
                ->paginate($perPageInt);

            // Make correct_answer visible for admin management
            $paginated->getCollection()->transform(function ($q) {
                $q->makeVisible(['correct_answer']);
                return $q;
            });
            $questions = $paginated;
        }

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
        $assessment = $this->resolveAssessment($assessmentId);

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
            'Question ID',
            'Category',
            'Difficulty',
            'Question Text',
            'Option A',
            'Option B',
            'Option C',
            'Option D',
            'Correct Answer',
            'Explanation',
            'Marks'
        ];
        $sheet->fromArray([$headers], null, 'A1');

        $sampleData = [
            [
                1,
                'Web Development',
                'Easy',
                'Which HTML5 element is used to specify a header for a document or section?',
                '<top>',
                '<header>',
                '<head>',
                '<section-head>',
                'B',
                'The <header> element represents introductory content.',
                1
            ],
            [
                2,
                'Digital Marketing',
                'Easy',
                'What does SEO stand for in digital marketing?',
                'Search Engine Optimization',
                'Social Engine Operation',
                'Systematic Electronic Outreach',
                'Site Efficiency Organization',
                'A',
                'SEO stands for Search Engine Optimization.',
                1
            ],
            [
                3,
                'Graphic Designing',
                'Medium',
                'Which color model is used for digital screens and web displays?',
                'CMYK',
                'RGB',
                'Pantone',
                'Monochrome',
                'B',
                'RGB is an additive color model for electronic displays.',
                1
            ]
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // Style headers
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment; filename="100_MCQ_Question_Bank_Template.xlsx"');
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
            'file' => 'required|file|max:20480',
        ]);

        $assessment = $this->resolveAssessment($assessmentId);
        $file = $request->file('file');
        $rawRows = $this->parseUploadedFileToRows($file);

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

        foreach ($rawRows as $index => $row) {
            $rowNum = $index + 2;
            $cat = trim((string)($row['category'] ?? 'General'));
            if (empty($cat)) $cat = 'General';

            $qText = trim((string)($row['question'] ?? ''));
            $optA = trim((string)($row['option_a'] ?? ''));
            $optB = trim((string)($row['option_b'] ?? ''));
            $optC = trim((string)($row['option_c'] ?? ''));
            $optD = trim((string)($row['option_d'] ?? ''));
            $rawAns = trim((string)($row['correct_answer'] ?? ''));
            $correctAns = $this->resolveCorrectAnswer($rawAns, $optA, $optB, $optC, $optD);
            $marks = is_numeric($row['marks'] ?? null) && (int)$row['marks'] > 0 ? (int)$row['marks'] : 1;
            $exp = trim((string)($row['explanation'] ?? ''));

            $errors = [];
            if (empty($qText)) $errors[] = "Question text is required.";
            if (empty($optA)) $errors[] = "Option A is required.";
            if (empty($optB)) $errors[] = "Option B is required.";
            if (empty($optC)) $errors[] = "Option C is required.";
            if (empty($optD)) $errors[] = "Option D is required.";
            if (!in_array($correctAns, ['A', 'B', 'C', 'D'])) {
                $errors[] = "Correct Answer must be A, B, C, or D (Got: '{$rawAns}').";
            }

            $isDuplicate = isset($existingQuestions[strtolower($qText)]);

            $rowPayload = [
                'row_number'     => $rowNum,
                'category'       => $cat,
                'question'       => $qText,
                'option_a'       => $optA,
                'option_b'       => $optB,
                'option_c'       => $optC,
                'option_d'       => $optD,
                'correct_answer' => $correctAns ?: 'A',
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
                'total_found'      => count($rawRows),
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

        $assessment = $this->resolveAssessment($assessmentId);
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

                $optA = trim((string)($item['option_a'] ?? ''));
                $optB = trim((string)($item['option_b'] ?? ''));
                $optC = trim((string)($item['option_c'] ?? ''));
                $optD = trim((string)($item['option_d'] ?? ''));
                $rawAns = trim((string)($item['correct_answer'] ?? 'A'));
                $correctAns = $this->resolveCorrectAnswer($rawAns, $optA, $optB, $optC, $optD) ?: 'A';

                $existing = AssessmentQuestion::where('assessment_id', $assessment->id)
                    ->where('question', $qText)
                    ->first();

                $payload = [
                    'category'       => trim((string)($item['category'] ?? 'General')) ?: 'General',
                    'question'       => $qText,
                    'option_a'       => $optA,
                    'option_b'       => $optB,
                    'option_c'       => $optC,
                    'option_d'       => $optD,
                    'correct_answer' => $correctAns,
                    'explanation'    => !empty($item['explanation']) ? trim((string)$item['explanation']) : null,
                    'marks'          => is_numeric($item['marks'] ?? null) && (int)$item['marks'] > 0 ? (int)$item['marks'] : 1,
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
     * Helper to parse any uploaded file (.csv, .xlsx, .xls, .json) into normalized rows
     */
    private function parseUploadedFileToRows($file): array
    {
        $path = $file->getRealPath();
        $ext = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($path);

        // 1. JSON parsing
        if ($ext === 'json' || (str_starts_with(trim($content), '{') || str_starts_with(trim($content), '['))) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                if (isset($decoded['questions']) && is_array($decoded['questions'])) {
                    $decoded = $decoded['questions'];
                } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
                    $decoded = $decoded['data'];
                }
                return $this->normalizeArrayOfObjects($decoded);
            }
        }

        // 2. Spreadsheet / CSV / XLS parsing via PhpSpreadsheet
        $rowsData = [];
        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getActiveSheet();
            $rowsData = $sheet->toArray(null, true, true, false); // Returns 0-indexed numeric array of rows
        } catch (\Throwable $e) {
            // Fallback: parse CSV manually using str_getcsv
            if ($ext === 'csv' || $ext === 'txt') {
                $lines = preg_split('/\r\n|\r|\n/', trim($content));
                foreach ($lines as $line) {
                    if (trim($line) === '') continue;
                    $rowsData[] = str_getcsv($line);
                }
            }
        }

        if (empty($rowsData)) {
            return [];
        }

        // Filter out empty rows
        $rowsData = array_values(array_filter($rowsData, function ($r) {
            return is_array($r) && count(array_filter($r, fn($v) => $v !== null && trim((string)$v) !== '')) > 0;
        }));

        if (empty($rowsData)) {
            return [];
        }

        // Header detection
        $firstRow = $rowsData[0];
        $headerMap = $this->detectHeaderColumns($firstRow);

        if (!empty($headerMap)) {
            // We identified recognized headers in row 0
            array_shift($rowsData);
            $normalized = [];
            foreach ($rowsData as $rowVals) {
                $item = [
                    'category'       => isset($headerMap['category']) ? ($rowVals[$headerMap['category']] ?? '') : 'General',
                    'question'       => isset($headerMap['question']) ? ($rowVals[$headerMap['question']] ?? '') : '',
                    'option_a'       => isset($headerMap['option_a']) ? ($rowVals[$headerMap['option_a']] ?? '') : '',
                    'option_b'       => isset($headerMap['option_b']) ? ($rowVals[$headerMap['option_b']] ?? '') : '',
                    'option_c'       => isset($headerMap['option_c']) ? ($rowVals[$headerMap['option_c']] ?? '') : '',
                    'option_d'       => isset($headerMap['option_d']) ? ($rowVals[$headerMap['option_d']] ?? '') : '',
                    'correct_answer' => isset($headerMap['correct_answer']) ? ($rowVals[$headerMap['correct_answer']] ?? 'A') : 'A',
                    'marks'          => isset($headerMap['marks']) ? ($rowVals[$headerMap['marks']] ?? 1) : 1,
                    'explanation'    => isset($headerMap['explanation']) ? ($rowVals[$headerMap['explanation']] ?? '') : '',
                ];
                $normalized[] = $item;
            }
            return $normalized;
        }

        // Positional fallback based on column count and characteristics
        return $this->parsePositionalRows($rowsData);
    }

    /**
     * Detect column indices from header row
     */
    private function detectHeaderColumns(array $headerRow): array
    {
        $map = [];
        $patterns = [
            'category'       => ['category', 'cat', 'track', 'subject', 'topic', 'section', 'domain', 'module', 'stream'],
            'question'       => ['question', 'questiontext', 'prompt', 'title', 'query', 'questiondescription', 'problem', 'qtext', 'q'],
            'option_a'       => ['optiona', 'option1', 'opta', 'choicea', 'choice1', 'ans1', 'opt1', 'firstoption'],
            'option_b'       => ['optionb', 'option2', 'optb', 'choiceb', 'choice2', 'ans2', 'opt2', 'secondoption'],
            'option_c'       => ['optionc', 'option3', 'optc', 'choicec', 'choice3', 'ans3', 'opt3', 'thirdoption'],
            'option_d'       => ['optiond', 'option4', 'optd', 'choiced', 'choice4', 'ans4', 'opt4', 'fourthoption'],
            'correct_answer' => ['correctanswer', 'correct', 'answer', 'ans', 'correctoption', 'correctopt', 'rightanswer', 'solutionkey', 'key', 'correctans'],
            'marks'          => ['marks', 'mark', 'score', 'points', 'point', 'weight', 'weightage'],
            'explanation'    => ['explanation', 'solution', 'rationale', 'description', 'notes', 'note', 'reason', 'why', 'details', 'exp'],
        ];

        foreach ($headerRow as $idx => $cell) {
            $clean = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$cell));
            if ($clean === '') continue;

            foreach ($patterns as $field => $aliases) {
                if (isset($map[$field])) continue;

                if (in_array($clean, $aliases, true)) {
                    $map[$field] = $idx;
                    break;
                }

                // Check exact single letter options like 'A', 'B', 'C', 'D' if labeled as such
                if ($field === 'option_a' && ($clean === 'a' || $clean === 'opta')) { $map[$field] = $idx; break; }
                if ($field === 'option_b' && ($clean === 'b' || $clean === 'optb')) { $map[$field] = $idx; break; }
                if ($field === 'option_c' && ($clean === 'c' || $clean === 'optc')) { $map[$field] = $idx; break; }
                if ($field === 'option_d' && ($clean === 'd' || $clean === 'optd')) { $map[$field] = $idx; break; }
            }
        }

        // Require at least 'question' or ('option_a' and 'correct_answer') to consider it a valid header
        if (isset($map['question']) || (isset($map['option_a']) && isset($map['correct_answer']))) {
            return $map;
        }

        return [];
    }

    /**
     * Positional row mapping when headers are missing
     */
    private function parsePositionalRows(array $rowsData): array
    {
        $normalized = [];
        foreach ($rowsData as $rowVals) {
            $rowVals = array_values($rowVals);
            $colCount = count($rowVals);

            // Layout 1: 10 columns -> [ID, Category, Difficulty, Question, OptA, OptB, OptC, OptD, CorrectAnswer, Explanation]
            if ($colCount >= 10) {
                $normalized[] = [
                    'category'       => $rowVals[1] ?? 'General',
                    'question'       => $rowVals[3] ?? '',
                    'option_a'       => $rowVals[4] ?? '',
                    'option_b'       => $rowVals[5] ?? '',
                    'option_c'       => $rowVals[6] ?? '',
                    'option_d'       => $rowVals[7] ?? '',
                    'correct_answer' => $rowVals[8] ?? 'A',
                    'marks'          => 1,
                    'explanation'    => $rowVals[9] ?? '',
                ];
            }
            // Layout 2: 9 columns -> [Category, Question, OptA, OptB, OptC, OptD, CorrectAnswer, Marks, Explanation]
            elseif ($colCount === 9) {
                $normalized[] = [
                    'category'       => $rowVals[0] ?? 'General',
                    'question'       => $rowVals[1] ?? '',
                    'option_a'       => $rowVals[2] ?? '',
                    'option_b'       => $rowVals[3] ?? '',
                    'option_c'       => $rowVals[4] ?? '',
                    'option_d'       => $rowVals[5] ?? '',
                    'correct_answer' => $rowVals[6] ?? 'A',
                    'marks'          => $rowVals[7] ?? 1,
                    'explanation'    => $rowVals[8] ?? '',
                ];
            }
            // Layout 3: 8 columns -> [Category, Question, OptA, OptB, OptC, OptD, CorrectAnswer, Explanation]
            elseif ($colCount === 8) {
                $normalized[] = [
                    'category'       => $rowVals[0] ?? 'General',
                    'question'       => $rowVals[1] ?? '',
                    'option_a'       => $rowVals[2] ?? '',
                    'option_b'       => $rowVals[3] ?? '',
                    'option_c'       => $rowVals[4] ?? '',
                    'option_d'       => $rowVals[5] ?? '',
                    'correct_answer' => $rowVals[6] ?? 'A',
                    'marks'          => 1,
                    'explanation'    => $rowVals[7] ?? '',
                ];
            }
            // Layout 4: 7 columns -> [Category, Question, OptA, OptB, OptC, OptD, CorrectAnswer]
            elseif ($colCount >= 7) {
                $normalized[] = [
                    'category'       => $rowVals[0] ?? 'General',
                    'question'       => $rowVals[1] ?? '',
                    'option_a'       => $rowVals[2] ?? '',
                    'option_b'       => $rowVals[3] ?? '',
                    'option_c'       => $rowVals[4] ?? '',
                    'option_d'       => $rowVals[5] ?? '',
                    'correct_answer' => $rowVals[6] ?? 'A',
                    'marks'          => 1,
                    'explanation'    => '',
                ];
            }
        }
        return $normalized;
    }

    /**
     * Normalize objects from JSON payload
     */
    private function normalizeArrayOfObjects(array $items): array
    {
        $normalized = [];
        foreach ($items as $obj) {
            if (!is_array($obj)) continue;

            $map = [];
            foreach ($obj as $k => $v) {
                $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string)$k));
                $map[$cleanKey] = $v;
            }

            $cat = $map['category'] ?? $map['track'] ?? $map['subject'] ?? 'General';
            $qText = $map['question'] ?? $map['questiontext'] ?? $map['prompt'] ?? $map['title'] ?? '';
            $optA = $map['optiona'] ?? $map['option1'] ?? $map['opta'] ?? $map['choicea'] ?? '';
            $optB = $map['optionb'] ?? $map['option2'] ?? $map['optb'] ?? $map['choiceb'] ?? '';
            $optC = $map['optionc'] ?? $map['option3'] ?? $map['optc'] ?? $map['choicec'] ?? '';
            $optD = $map['optiond'] ?? $map['option4'] ?? $map['optd'] ?? $map['choiced'] ?? '';
            $ans = $map['correctanswer'] ?? $map['correct'] ?? $map['answer'] ?? $map['ans'] ?? 'A';
            $marks = $map['marks'] ?? $map['mark'] ?? $map['score'] ?? 1;
            $exp = $map['explanation'] ?? $map['solution'] ?? $map['notes'] ?? '';

            $normalized[] = [
                'category'       => (string)$cat,
                'question'       => (string)$qText,
                'option_a'       => (string)$optA,
                'option_b'       => (string)$optB,
                'option_c'       => (string)$optC,
                'option_d'       => (string)$optD,
                'correct_answer' => (string)$ans,
                'marks'          => $marks,
                'explanation'    => (string)$exp,
            ];
        }
        return $normalized;
    }

    /**
     * Resolve correct answer into 'A', 'B', 'C', or 'D'
     */
    private function resolveCorrectAnswer(?string $ans, string $optA = '', string $optB = '', string $optC = '', string $optD = ''): ?string
    {
        if ($ans === null) return null;
        $clean = strtoupper(trim($ans));
        $clean = trim($clean, "()[]{}:., \t\n\r\0\x0B");

        if (in_array($clean, ['A', 'B', 'C', 'D'], true)) {
            return $clean;
        }

        if ($clean === '1') return 'A';
        if ($clean === '2') return 'B';
        if ($clean === '3') return 'C';
        if ($clean === '4') return 'D';

        if (preg_match('/^(?:OPTION|CHOICE)\s*([A-D])/i', $clean, $matches)) {
            return strtoupper($matches[1]);
        }

        // Match against option text if the full text was entered as the answer
        $target = strtolower(trim($ans));
        if ($target !== '') {
            if (strtolower(trim($optA)) === $target) return 'A';
            if (strtolower(trim($optB)) === $target) return 'B';
            if (strtolower(trim($optC)) === $target) return 'C';
            if (strtolower(trim($optD)) === $target) return 'D';
        }

        return null;
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

    /**
     * Gracefully resolve assessment by ID or fallback to the primary default assessment
     */
    private function resolveAssessment($assessmentId): Assessment
    {
        $assessment = is_numeric($assessmentId) && $assessmentId > 0 ? Assessment::find($assessmentId) : null;
        if (!$assessment) {
            $assessment = Assessment::first();
        }
        if (!$assessment) {
            $assessment = Assessment::create([
                'title'              => '100 MCQ Internship Assessment',
                'slug'               => '100-mcq-internship-assessment',
                'description'        => 'Comprehensive 100 MCQ Assessment covering Web Development, Digital Marketing, Graphic Designing, and more.',
                'total_questions'    => 0,
                'total_marks'        => 0,
                'passing_percentage' => 40,
                'duration_minutes'   => 60,
                'status'             => 'active',
            ]);
        }
        return $assessment;
    }
}

