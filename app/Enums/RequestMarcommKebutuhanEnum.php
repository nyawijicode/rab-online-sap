<?php

namespace App\Enums;

enum RequestMarcommKebutuhanEnum: string
{
    case KARTU_NAMA = 'kartu_nama';
    case ID_CARD    = 'id_card';
    case LANYARD    = 'lanyard';
    case AMPLOP     = 'amplop';
    case KATALOG    = 'katalog';

    public function label(): string
    {
        return match ($this) {
            self::KARTU_NAMA => 'Kartu Nama',
            self::ID_CARD    => 'ID Card',
            self::LANYARD    => 'Lanyard',
            self::AMPLOP     => 'Amplop',
            self::KATALOG    => 'Katalog',
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
