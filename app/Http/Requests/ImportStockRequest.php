<?php

namespace App\Http\Requests;

use App\Models\Import;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal' => [
                'required',
                'date',
                Rule::unique('imports', 'tanggal_import')->where(function ($query) {
                    return $query->where('status', '!=', 'failed'); // Abaikan impor gagal
                }),
            ],
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // Maksimal 10MB
        ];
    }

    public function messages()
    {
        return [
            'tanggal.required' => 'Tanggal data wajib diisi',
            'tanggal.date' => 'Format tanggal tidak valid',
            'tanggal.unique' => 'Data untuk tanggal ini sudah diimpor. Pilih tanggal lain atau hapus data sebelumnya.',
            'file.required' => 'File data stok wajib diupload',
            'file.file' => 'Upload harus berupa file',
            'file.mimes' => 'File harus dalam format Excel (xlsx, xls) atau CSV',
            'file.max' => 'Ukuran file maksimal 10MB',
        ];
    }
}