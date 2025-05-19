<?php

namespace App\Http\Controllers;

use App\Models\Import;
use App\Imports\StockImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Http\Requests\ImportStockRequest;

class ImportController extends Controller
{
    public function create()
    {
        $recentImports = Import::orderByDesc('tanggal_import')->get();
        return view('imports.create', compact('recentImports'));
    }

    public function store(ImportStockRequest $request)
    {
        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();

        Log::info('Tanggal impor dari request: ' . $request->post('tanggal'));
        Log::info('File yang diupload: ' . $file->getClientOriginalName() . ', ukuran: ' . $file->getSize() . ' bytes');

        // Buat entri impor
        $import = Import::create([
            'nama_file' => $fileName,
            'tanggal_import' => $request->post('tanggal'),
            'status' => 'processing',
        ]);

        try {
            // Gunakan WithHeadingRow untuk membaca header dengan benar
            $rows = Excel::toCollection(new class implements WithHeadingRow {}, $file)->first();
            if (!$rows || $rows->isEmpty()) {
                throw new \Exception('File Excel kosong atau tidak dapat dibaca.');
            }

            Log::info('Isi rows mentah (dengan header): ' . json_encode($rows->take(2)->toArray()));

            $headers = $rows->first()->keys()->map(fn($key) => strtolower(trim($key)))->toArray();
            Log::info("Header ditemukan di file: " . json_encode($headers));

            $requiredHeaders = ['kode', 'bahan_baku', 'stok', 'satuan'];
            if (!empty(array_diff($requiredHeaders, $headers))) {
                throw new \Exception('File tidak memiliki kolom wajib: kode, bahan_baku, stok, satuan. Header yang ditemukan: ' . implode(', ', $headers));
            }

            // Proses impor
            Excel::import(new StockImport($import->id, $request->tanggal), $file);

            $import->update(['status' => 'success']);

            return redirect()->route('imports.create')
                ->with('success', 'Data stok berhasil diimpor!');
        } catch (\Exception $e) {
            $import->update(['status' => 'failed']);
            Log::error("Gagal impor ID {$import->id}: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal mengimpor data: ' . $e->getMessage());
        }
    }

    public function destroySelected(Request $request)
    {
        $ids = $request->input('selected_imports');

        if (!$ids) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih untuk dihapus.');
        }

        Import::whereIn('id', $ids)->delete();

        return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
    }
}