<?php

namespace App\Support;

// Kode kelas dicetak di kartu dan diketik anak kelas 1 — hindari huruf/
// angka yang gampang tertukar (0/O, 1/I/l) supaya tidak jadi sumber
// kegagalan masuk yang membingungkan guru dan anak.
class KodeGenerator
{
    private const ABJAD = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function acak(int $panjang = 6): string
    {
        $hasil = '';
        for ($i = 0; $i < $panjang; $i++) {
            $hasil .= self::ABJAD[random_int(0, strlen(self::ABJAD) - 1)];
        }

        return $hasil;
    }

    public static function pin(): string
    {
        return (string) random_int(1000, 9999);
    }
}
