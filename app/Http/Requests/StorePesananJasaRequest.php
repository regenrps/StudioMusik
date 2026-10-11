<?php

namespace App\Http\Requests;

use App\Enums\TipeField;
use App\Models\JasaMusik;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi formulir pesanan jasa musik. Aturan untuk field dinamis
 * dibangun dari definisi formulir layanan yang dipilih.
 */
class StorePesananJasaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('peminjam') ?? false;
    }

    private function jasa(): JasaMusik
    {
        /** @var JasaMusik $jasa */
        $jasa = $this->route('jasa');

        return $jasa->loadMissing('field');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $min = (int) pengaturan('jasa_tenggat_min_hari', 3);
        $maks = (int) pengaturan('jasa_tenggat_maks_hari', 90);
        $upload = config('studio.upload');

        $rules = [
            'judul' => ['required', 'string', 'min:3', 'max:120'],
            'tenggat_produksi' => [
                'required', 'date_format:Y-m-d',
                'after_or_equal:'.today()->addDays($min)->toDateString(),
                'before_or_equal:'.today()->addDays($maks)->toDateString(),
            ],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'setuju_sk' => ['accepted'],
        ];

        foreach ($this->jasa()->field as $field) {
            $wajib = $field->is_required ? 'required' : 'nullable';

            $rules[match ($field->jenis_field) {
                TipeField::Berkas => $field->input_name,
                default => 'fields.'.$field->input_name,
            }] = match ($field->jenis_field) {
                TipeField::Teks => [$wajib, 'string', 'max:255'],
                TipeField::Paragraf => [$wajib, 'string', 'max:5000'],
                TipeField::Angka => [$wajib, 'numeric', 'min:0', 'max:1000000'],
                TipeField::Tautan => [$wajib, 'url', 'max:500'],
                TipeField::Berkas => [$wajib, 'file', 'mimes:'.implode(',', $upload['lampiran_mimes']), 'max:'.$upload['audio_maks_kb']],
            };
        }

        return $rules;
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        $attr = [
            'judul' => 'judul proyek',
            'tenggat_produksi' => 'tenggat',
            'keterangan' => 'catatan tambahan',
            'setuju_sk' => 'persetujuan syarat & ketentuan',
        ];

        foreach ($this->jasa()->field as $field) {
            $kunci = $field->jenis_field === TipeField::Berkas ? $field->input_name : 'fields.'.$field->input_name;
            $attr[$kunci] = strtolower($field->nama_field);
        }

        return $attr;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'setuju_sk.accepted' => 'Anda harus menyetujui syarat & ketentuan layanan.',
            'tenggat_produksi.after_or_equal' => 'Tenggat paling cepat :date agar tim memiliki waktu produksi yang cukup.',
            'tenggat_produksi.before_or_equal' => 'Tenggat paling lambat :date.',
        ];
    }

    /** @return array<string, \Illuminate\Http\UploadedFile> */
    public function berkasField(): array
    {
        $hasil = [];
        foreach ($this->jasa()->field as $field) {
            if ($field->jenis_field === TipeField::Berkas && $this->hasFile($field->input_name)) {
                $hasil[$field->input_name] = $this->file($field->input_name);
            }
        }

        return $hasil;
    }
}
