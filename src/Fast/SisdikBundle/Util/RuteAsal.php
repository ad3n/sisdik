<?php

namespace Langgas\SisdikBundle\Util;
/**
 * Utiliti penentuan rute
 */
class RuteAsal
{
    /**
     * Menyetel rute untuk kembali ke
     * info siswa atau pendaftar berdasarkan path
     */
    public static function ruteAsalSiswaPendaftar($path) {
        if (strpos($path, 'pendaftar')) {
            $rute = "pendaftar";
        } else if (strpos($path, "siswa")) {
            $rute = "siswa";
        } else {
            $rute = "";
        }

        return $rute;
    }
}
