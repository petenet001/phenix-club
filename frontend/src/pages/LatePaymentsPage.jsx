import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { recovery } from '../api/resources'
import PaymentModal from '../components/PaymentModal'
import ReceiptModal from '../components/ReceiptModal'
import ReminderModal from '../components/ReminderModal'
import { PageHeader, Spinner, StatCard } from '../components/ui'
import { formatDate, formatDateTime, formatFcfa } from '../utils/format'

export default function LatePaymentsPage() {
  const [minDays, setMinDays] = useState(0)
  const [remind, setRemind] = useState(null)
  const [pay, setPay] = useState(null)
  const [receiptIds, setReceiptIds] = useState(null)
  const { data, isLoading } = useQuery({ queryKey: ['late-payments', minDays], queryFn: () => recovery.latePayments({ min_days: minDays }) })

  return (
    <div>
      <PageHeader title="Retards de paiement" subtitle="Échéance dépassée au-delà du seuil, sans renouvellement ni solde réglé" />
      {data && (
        <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
          <StatCard label="Adhérents en retard" value={data.summary.count} tone={data.summary.count ? 'bad' : 'good'} />
          <StatCard label="Montant attendu" value={data.summary.amount} money tone="warn" />
          <div className="card lg:col-span-2">
            <label className="label">Retard minimum (jours)</label>
            <input className="input" type="number" min={0} value={minDays} onChange={(e) => setMinDays(Number(e.target.value) || 0)} />
          </div>
        </div>
      )}
      {isLoading ? <Spinner /> : (
        <div className="card overflow-x-auto p-0">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Adhérent</th><th className="px-4 py-3">Téléphone</th><th className="px-4 py-3">Formule</th><th className="px-4 py-3">Échéance</th><th className="px-4 py-3">Retard</th><th className="px-4 py-3">Montant</th><th className="px-4 py-3">Dernière relance</th><th></th></tr></thead>
            <tbody className="divide-y">
              {(data?.data || []).map((r) => (
                <tr key={r.member.id}>
                  <td className="px-4 py-2"><Link to={`/adherents/${r.member.id}`} className="font-medium hover:underline">{r.member.full_name}</Link><span className="block text-xs text-slate-500">{r.member.code}</span></td>
                  <td className="px-4 py-2">{r.member.phone}</td>
                  <td className="px-4 py-2">{r.plan}<span className="block text-xs text-slate-500">{r.reason === 'unpaid_balance' ? 'solde impayé' : 'renouvellement attendu'}</span></td>
                  <td className="px-4 py-2">{formatDate(r.end_date)}</td>
                  <td className="px-4 py-2 font-semibold text-red-600">{r.days_late} j</td>
                  <td className="px-4 py-2 font-semibold">{formatFcfa(r.amount_due)}</td>
                  <td className="px-4 py-2 text-slate-500">{r.last_reminder_at ? formatDateTime(r.last_reminder_at) : '-'}</td>
                  <td className="px-4 py-2 text-right whitespace-nowrap">
                    <button className="btn-primary text-xs" onClick={() => setPay(r.member)}>Encaisser</button>
                    <button className="btn-ghost text-xs" onClick={() => setRemind(r.member)}>Relancer</button>
                  </td>
                </tr>
              ))}
              {data?.data?.length === 0 && <tr><td colSpan={8} className="px-4 py-8 text-center text-emerald-700">Aucun retard, tout le monde est à jour.</td></tr>}
            </tbody>
          </table>
        </div>
      )}
      <ReminderModal member={remind} type="late_payment" open={Boolean(remind)} onClose={() => setRemind(null)} />
      <PaymentModal member={pay} open={Boolean(pay)} onClose={() => setPay(null)} onPaid={(list) => { setPay(null); if (list[0]) setReceiptIds(list.map((p) => p.id)) }} />
      <ReceiptModal paymentId={receiptIds?.[0]} paymentIds={receiptIds} open={Boolean(receiptIds)} onClose={() => setReceiptIds(null)} />
    </div>
  )
}
