import '../../../../core/utils/formatters.dart';
import '../../domain/entities/payment.dart';

int _int(Object? v) => (v as num?)?.toInt() ?? 0;

class PaymentModels {
  const PaymentModels._();

  static Payment payment(Map<String, dynamic> j) => Payment(
        id: _int(j['id']),
        receiptNumber: j['receipt_number'] as String? ?? '',
        paidAt: Fmt.parse(j['paid_at'])!,
        amount: _int(j['amount']),
        type: j['type'] as String? ?? 'other',
        typeLabel: j['type_label'] as String? ?? '',
        methodLabel: j['method_label'] as String? ?? '',
        plan: j['plan'] as String?,
        periodStart: Fmt.parse(j['period_start']),
        periodEnd: Fmt.parse(j['period_end']),
        cancelled: j['cancelled'] == true,
      );

  static Receipt receipt(Map<String, dynamic> j) {
    final member = j['member'] as Map<String, dynamic>;
    final club = j['club'] as Map<String, dynamic>;
    return Receipt(
      receiptNumber: j['receipt_number'] as String? ?? '',
      paidAt: Fmt.parse(j['paid_at'])!,
      memberName: member['full_name'] as String? ?? '',
      memberCode: member['code'] as String? ?? '',
      typeLabel: j['type_label'] as String? ?? '',
      plan: j['plan'] as String?,
      periodStart: Fmt.parse(j['period_start']),
      periodEnd: Fmt.parse(j['period_end']),
      amount: _int(j['amount']),
      amountInWords: j['amount_in_words'] as String? ?? '',
      methodLabel: j['method_label'] as String? ?? '',
      reference: j['reference'] as String?,
      balanceDue: _int(j['balance_due']),
      receivedBy: j['received_by'] as String?,
      cancelled: j['cancelled_at'] != null,
      clubName: club['name'] as String? ?? 'Phenix Club',
      clubAddress: club['address'] as String? ?? '',
    );
  }

  static Balance balance(Map<String, dynamic> j) {
    final late = j['late'] as Map<String, dynamic>;
    return Balance(
      totalDue: _int(j['total_due']),
      items: ((j['items'] as List?) ?? const []).cast<Map<String, dynamic>>().map((i) => BalanceItem(
            type: i['type'] as String? ?? '',
            label: i['label'] as String? ?? '',
            amount: _int(i['amount']),
            periodStart: Fmt.parse(i['period_start']),
            periodEnd: Fmt.parse(i['period_end']),
            price: (i['price'] as num?)?.toInt(),
            amountPaid: (i['amount_paid'] as num?)?.toInt(),
          )).toList(),
      isLate: late['is_late'] == true,
      daysLate: _int(late['days_late']),
      lateAmount: _int(late['amount_due']),
      lateMessage: late['message'] as String?,
    );
  }
}
