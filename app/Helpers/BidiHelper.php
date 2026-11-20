<?php

namespace App\Helpers;

use Cheprasov\Bidi\Bidi;

class BidiHelper
{
    /**
     * تحويل النص العربي إلى ترتيب مرئي صحيح لـ DomPDF.
     *
     * DomPDF لا يدعم Bidi، لذا نحتاج تحويل النص
     * من "منطقي" (logical) إلى "مرئي" (visual).
     */
    public static function fix(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        // لا تحويل لو النص إنجليزي فقط
        if (!preg_match('/\p{Arabic}/u', $text)) {
            return $text;
        }

        try {
            $bidi = new Bidi();
            return $bidi->convertVisual($text);
        } catch (\Throwable $e) {
            // إن فشلت المكتبة، أرجع النص كما هو
            return $text;
        }
    }
}