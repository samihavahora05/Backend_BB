<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkImportStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage students');
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,txt|mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,application/excel,application/vnd.ms-excel,application/vnd.msexcel,text/anytext,application/octet-stream,application/txt|max:5120',
        ];
    }
}
