import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { payments } from '../api/resources'
import { apiError } from '../api/client'
import MemberSearch from '../components/MemberSearch'
import PaymentModal from '../components/PaymentModal'
import ReceiptModal from '../components/ReceiptModal'
import { Badge, ConfirmReason, PageHeader, Pagination, Spinner, StatCard } from '../components/ui'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { formatDateTime, formatFcfa, METHOD_LABELS, today, TYPE_LABELS } from '../utils/format'

export default function PaymentsPage() {
  const { isManager } = useAuth()
  const toast = useToast()
  const qc = useQueryClient()
  const [filters, setFilters] = useState({ from: today(), to: today(), method: '', type: '', search: '', page: 1 })
  const [member, setMember] = useState(null)
  const [cancelId, setCancelId] = useState(null)
  const [receiptIds, setReceiptIds] = useState(null)

  const { data, isLoading } = useQuery({ queryKey: ['payments', filters], queryFn: () => payments.list({ ...filters, per_page: 30 }), keepPreviousData: true })
  const cancel = useMutation({ mutationFn: ({ id, reason }) => payments.cancel(id, reason), onSuccess: () => { qc.invalidateQueries({ queryKey: ['payments'] }); setCancelId(null); toast.success('Paiement annulé') }, onError: (e) => toast.error(apiError(e).message) })

  const totals = data?.meta?.totals
  const set = (k, v) => setFilters({ ...filters, [k]: v, page: k === 'page' ? v : 1 })

  return (
    <div>
      <PageHeader title="Paiements" subtitle="Journal de caisse des encaissements" />

      <div className="card mb-4">
        <p className="label">Encaisser un adhérent</p>
        <MemberSearch onSelect={setMember} placeholder="Rechercher l'adhérent à encaisser..." />
      </div>

      {totals && (
        <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
          <StatCard label="Total période" value={totals.total} money sub={`${totals.count} paiement(s)`} tone="good" />
          {Object.entries(totals.by_method).map(([m, v]) => <StatCard key={m} label={METHOD_LABELS[m]} value={v} money />)}
          <StatCard label="Frais d'inscription" value={totals.by_type.registration} money />
        </div>
      )}

      <div className="card mb-4 grid gap-3 md:grid-cols-6">
        <input className="input" type="date" value={filters.from} onChange={(e) => set('from', e.target.value)} />
        <input className="input" type="date" value={filters.to} onChange={(e) => set('to', e.target.value)} />
        <select className="input" value={filters.method} onChange={(e) => set('method', e.target.value)}><option value="">Tous les modes</option>{Object.entries(METHOD_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select>
        <select className="input" value={filters.type} onChange={(e) => set('type', e.target.value)}><option value="">Tous les types</option>{Object.entries(TYPE_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select>
        <input className="input md:col-span-2" placeholder="N° de reçu, référence, adhérent" value={filters.search} onChange={(e) => set('search', e.target.value)} />
      </div>

      {isLoading ? <Spinner /> : (
        <div className="card overflow-x-auto p-0">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Reçu</th><th className="px-4 py-3">Date</th><th className="px-4 py-3">Adhérent</th><th className="px-4 py-3">Objet</th><th className="px-4 py-3">Montant</th><th className="px-4 py-3">Mode</th><th className="px-4 py-3">Par</th><th></th></tr></thead>
            <tbody className="divide-y">
              {(data?.data || []).map((p) => (
                <tr key={p.id} className={p.cancelled_at ? 'text-slate-400' : ''}>
                  <td className="px-4 py-2 font-mono text-xs">{p.receipt_number}{p.cancelled_at && <Badge className="ml-1 bg-red-100 text-red-700">annulé</Badge>}</td>
                  <td className="px-4 py-2">{formatDateTime(p.paid_at)}</td>
                  <td className="px-4 py-2"><Link to={`/adherents/${p.member.id}`} className="hover:underline">{p.member.full_name}</Link></td>
                  <td className="px-4 py-2">{p.type_label}{p.subscription && ` - ${p.subscription.plan}`}</td>
                  <td className="px-4 py-2 font-semibold">{formatFcfa(p.amount)}</td>
                  <td className="px-4 py-2">{p.method_label}</td>
                  <td className="px-4 py-2 text-slate-500">{p.received_by?.name}</td>
                  <td className="px-4 py-2 text-right whitespace-nowrap">
                    <button onClick={() => setReceiptIds([p.id])} className="btn-ghost text-xs">Reçu</button>
                    {isManager && !p.cancelled_at && <button className="btn-ghost text-xs text-red-600" onClick={() => setCancelId(p.id)}>Annuler</button>}
                  </td>
                </tr>
              ))}
              {data?.data?.length === 0 && <tr><td colSpan={8} className="px-4 py-8 text-center text-slate-500">Aucun paiement sur la période.</td></tr>}
            </tbody>
          </table>
        </div>
      )}
      <Pagination meta={data?.meta} onPage={(p) => set('page', p)} />

      <PaymentModal member={member} open={Boolean(member)} onClose={() => setMember(null)} onPaid={(list) => { setMember(null); if (list[0]) setReceiptIds(list.map((p) => p.id)) }} />
      <ReceiptModal paymentId={receiptIds?.[0]} paymentIds={receiptIds} open={Boolean(receiptIds)} onClose={() => setReceiptIds(null)} />
      <ConfirmReason open={Boolean(cancelId)} title="Annuler le paiement" onClose={() => setCancelId(null)} loading={cancel.isPending} onConfirm={(reason) => cancel.mutate({ id: cancelId, reason })} />
    </div>
  )
}
