<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu {{ $payment->receipt_number }}</title>
    <style>
        @page { size: A5 portrait; margin: 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; margin: 0; }
        .header { text-align: center; border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 12px; }
        .logo { display: block; height: 60px; margin: 0 auto 6px; }
        .header h1 { margin: 0; font-size: 18px; letter-spacing: 1px; }
        .header p { margin: 2px 0; font-size: 11px; }
        .title { text-align: center; font-size: 15px; font-weight: bold; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 5px 4px; vertical-align: top; }
        td.label { width: 38%; color: #555; }
        .amount { font-size: 20px; font-weight: bold; text-align: center; border: 1px solid #111; padding: 8px; margin: 12px 0; }
        .words { font-style: italic; text-align: center; margin-bottom: 12px; }
        .footer { margin-top: 18px; font-size: 10px; color: #555; display: flex; justify-content: space-between; }
        .cancelled { position: absolute; top: 40%; left: 10%; font-size: 48px; color: #c00; opacity: .35; transform: rotate(-20deg); font-weight: bold; }
    </style>
</head>
<body>
@if($payment->cancelled_at)
    <div class="cancelled">ANNULÉ</div>
@endif
<div class="header">
    <img src="{{ $logoSrc }}" alt="{{ $club['name'] }}" class="logo">
    <h1>{{ $club['name'] }}</h1>
    <p>{{ $club['address'] }}@if($club['phone']) - Tél. {{ $club['phone'] }}@endif</p>
</div>
<div class="title">REÇU N° {{ $payment->receipt_number }}</div>
<table>
    <tr><td class="label">Date</td><td>{{ $payment->paid_at->format('d/m/Y à H\hi') }}</td></tr>
    <tr><td class="label">Reçu de</td><td><strong>{{ $payment->member->full_name }}</strong> ({{ $payment->member->code }})</td></tr>
    <tr><td class="label">Téléphone</td><td>{{ $payment->member->phone }}</td></tr>
    <tr><td class="label">Objet</td><td>{{ $payment->type->label() }}@if($payment->subscription) - {{ $payment->subscription->plan->name }}@endif</td></tr>
    @if($payment->period_start)
    <tr><td class="label">Période couverte</td><td>du {{ $payment->period_start->format('d/m/Y') }} au {{ $payment->period_end?->format('d/m/Y') }}</td></tr>
    @endif
    <tr><td class="label">Mode de paiement</td><td>{{ $payment->method->label() }}@if($payment->reference) - réf. {{ $payment->reference }}@endif</td></tr>
    @if($payment->subscription && $payment->subscription->balance_due > 0)
    <tr><td class="label">Reste à payer</td><td>{{ number_format($payment->subscription->balance_due, 0, ',', ' ') }} FCFA</td></tr>
    @endif
</table>
<div class="amount">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>
<div class="words">Arrêté le présent reçu à la somme de {{ $amountInWords }} francs CFA</div>
@if($payment->notes)
<p><em>{{ $payment->notes }}</em></p>
@endif
<div class="footer">
    <span>Encaissé par : {{ $payment->receiver?->name ?? '-' }}</span>
    <span>Signature et cachet</span>
</div>
</body>
</html>
