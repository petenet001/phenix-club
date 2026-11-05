<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Payment;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reçu (UC-08) : données pour l'affichage dans l'application (ReceiptResource) et PDF A5
 * si barryvdh/laravel-dompdf est installé (composer require barryvdh/laravel-dompdf).
 */
class ReceiptService
{
    public function club(): array
    {
        return [
            'name' => Setting::get('club_name', 'Phenix Club'),
            'address' => Setting::get('club_address', ''),
            'phone' => Setting::get('club_phone', ''),
        ];
    }

    public function view(Payment $payment): View
    {
        $payment->loadMissing(['member', 'subscription.plan', 'receiver']);

        return view('receipts.payment', [
            'payment' => $payment,
            'logoSrc' => public_path('images/logo-phenix.png'),
            'club' => $this->club(),
            'amountInWords' => $this->amountInWords($payment->amount),
        ]);
    }

    public function pdf(Payment $payment): Response
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            throw new BusinessRuleException('pdf_unavailable', 'PDF indisponible.');
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadHTML($this->view($payment)->render())
            ->setPaper('a5', 'portrait')
            ->download('recu-'.$payment->receipt_number.'.pdf');
    }

    /** Montant en lettres (français), ex : 15000 -> quinze mille. */
    public function amountInWords(int $n): string
    {
        if ($n === 0) {
            return 'zéro';
        }

        $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        $below100 = function (int $x) use ($units, $tens): string {
            if ($x < 20) {
                return $units[$x];
            }
            $t = intdiv($x, 10);
            $u = $x % 10;
            if ($t === 7 || $t === 9) {
                $u += 10;
            }
            $word = $tens[$t];
            if ($t === 8 && $u === 0) {
                return 'quatre-vingts';
            }
            if ($u === 1 && $t !== 8 && $t !== 9) {
                return $word.' et un';
            }
            if ($u === 11 && $t === 7) {
                return 'soixante et onze';
            }

            return $u > 0 ? $word.'-'.$units[$u] : $word;
        };

        $below1000 = function (int $x) use ($below100): string {
            $h = intdiv($x, 100);
            $r = $x % 100;
            $word = '';
            if ($h === 1) {
                $word = 'cent';
            } elseif ($h > 1) {
                $word = $below100($h).' cent'.($r === 0 ? 's' : '');
            }

            return trim($word.' '.($r > 0 ? $below100($r) : ''));
        };

        $parts = [];
        $millions = intdiv($n, 1000000);
        $thousands = intdiv($n % 1000000, 1000);
        $rest = $n % 1000;

        if ($millions > 0) {
            $parts[] = ($millions === 1 ? 'un million' : $below1000($millions).' millions');
        }
        if ($thousands > 0) {
            $parts[] = ($thousands === 1 ? 'mille' : $below1000($thousands).' mille');
        }
        if ($rest > 0) {
            $parts[] = $below1000($rest);
        }

        return implode(' ', $parts);
    }
}
