<?php

namespace App\Enums;

enum RequestMarcommStatusEnum: string
{
    case TUNGGU     = 'tunggu';
    case KONFIRMASI = 'konfirmasi';
    case DESAIN     = 'desain';
    case CETAK      = 'cetak';
    case KIRIM      = 'kirim';
    case TERKIRIM   = 'terkirim';
    case SELESAI    = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::TUNGGU     => 'Request',
            self::KONFIRMASI => 'Konfirmasi',
            self::DESAIN     => 'Desain',
            self::CETAK      => 'Cetak',
            self::KIRIM      => 'Kirim',
            self::TERKIRIM   => 'Terkirim',
            self::SELESAI    => 'Selesai',
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
