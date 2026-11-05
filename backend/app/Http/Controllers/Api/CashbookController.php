<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CashbookService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashbookController extends Controller
{
    public function __construct(private readonly CashbookService $cashbook) {}

    /**
     * GET /api/cashbook?from=&to= (défaut : mois en cours)
     */
    public function index(Request $request): JsonResponse
    {
        [$from, $to] = $this->period($request);

        return response()->json($this->cashbook->report($from, $to));
    }

    /**
     * GET /api/cashbook/export?from=&to= [manager] - CSV lisible par Excel (séparateur ;)
     */
    public function export(Request $request): StreamedResponse
    {
        [$from, $to] = $this->period($request);
        $report = $this->cashbook->report($from, $to);
        $filename = sprintf('caisse-%s-%s.csv', $from->format('Ymd'), $to->format('Ymd'));

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $w = fn (array $row) => fputcsv($out, $row, ';');

            $w(['Caisse Phenix Club', 'du '.$report['period']['from'].' au '.$report['period']['to']]);
            $w([]);
            $w(['ENTRÉES', 'Montant (FCFA)']);
            foreach ($report['income']['by_method'] as $m => $v) {
                $w([$m, $v]);
            }
            $w(['Total entrées', $report['income']['total']]);
            $w([]);
            $w(['SORTIES', 'Montant (FCFA)']);
            foreach ($report['expenses']['by_category'] as $c => $v) {
                $w([$c, $v]);
            }
            $w(['Total sorties', $report['expenses']['total']]);
            $w([]);
            $w(['SOLDE DE LA PÉRIODE', $report['balance']]);
            $w(['Solde cumulé année', $report['year_to_date']['balance']]);
            $w([]);
            $w(['MANQUANTS']);
            $w(['Attendu', $report['outstanding']['expected']]);
            $w(['Encaissé', $report['outstanding']['collected']]);
            $w(['Reste dû', $report['outstanding']['missing']]);
            $w(['Retards (nombre)', $report['outstanding']['late_count']]);
            $w(['Retards (montant)', $report['outstanding']['late_amount']]);
            $w([]);
            $w(['Date', 'Entrées', 'Sorties', 'Solde du jour']);
            foreach ($report['days'] as $d) {
                $w([$d['date'], $d['income'], $d['expenses'], $d['income'] - $d['expenses']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function period(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to)->endOfDay() : now()->endOfMonth();

        return [$from, $to];
    }
}
