<?php

namespace App\Http\Requests;

use App\Services\KetersediaanService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('peminjam') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $aturan = app(KetersediaanService::class)->aturan();
        $upload = config('studio.upload');

        return [
            'ruangan' => ['required', 'string', Rule::exists('data_ruangan', 'slug')->where('is_active', true)],
            'tgl_pinjam' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'durasi' => ['required', 'integer', 'min:'.$aturan['durasi_min_menit'], 'max:'.$aturan['durasi_maks_menit']],
            'jumlah_orang' => ['required', 'integer', 'min:1', 'max:'.$aturan['maks_orang']],
            'ket_keperluan' => ['required', 'string', 'min:10', 'max:1000'],
            'jaminan' => ['required', 'image', 'mimes:'.implode(',', $upload['gambar_mimes']), 'max:'.$upload['gambar_maks_kb']],
            'setuju_sop' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'ruangan' => 'ruangan',
            'tgl_pinjam' => 'tanggal peminjaman',
            'waktu_mulai' => 'jam mulai',
            'durasi' => 'durasi',
            'jumlah_orang' => 'jumlah orang',
            'ket_keperluan' => 'keperluan',
            'jaminan' => 'foto KTM/KTP',
            'setuju_sop' => 'persetujuan SOP',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'setuju_sop.accepted' => 'Anda harus menyetujui SOP peminjaman studio.',
            'ket_keperluan.min' => 'Jelaskan keperluan peminjaman minimal :min karakter (mis. latihan band untuk acara apa).',
        ];
    }
}
