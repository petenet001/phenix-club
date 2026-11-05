import { Download, Plus } from 'lucide-react'
import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import dayjs from 'dayjs'
import { Link } from 'react-router-dom'
import { cashbook } from '../api/resources'
import { apiError } from '../api/client'
import { Badge, ConfirmReason, ErrorBox, Field, Modal, PageHeader, Spinner, StatCard } from '../components/ui'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { endOfMonth, formatDate, formatFcfa, METHOD_LABELS, saveBlob, startOfMonth, today, TYPE_LABELS } from '../utils/format'

function ExpenseModal({ open, onClose, categories }) {
  const toast = useToast()
  const qc = useQueryClient()
  const [error, setError] = useState(null)
  const [errors, setErrors] = useState({})
  const mutation = useMutation({
    mutationFn: (fd) => cashbook.createExpense(fd),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['expenses'] }); qc.invalidateQueries({ queryKey: ['cashbook'] }); toast.success('Dépense enregistrée'); onClose() },
    onError: (e) => { const er = apiError(e); setError(er.message); setErrors(er.errors) },
  })
  return (
    <Modal open={open} title="Nouvelle dépense" onClose={onClose}>
      <form className="space-y-3" onSubmit={(e) => { e.preventDefault(); setError(null); mutation.mutate(new FormData(e.currentTarget)) }}>
        <div className="grid gap-3 md:grid-cols-2">
          <Field label="Catégorie *" error={errors.expense_category_id}><select name="expense_category_id" className="input" required>{categories.map((c) => <option key={c.id} value={c.id}>{c.name}</option>)}</select></Field>
          <Field label="Date *" error={errors.spent_on}><input name="spent_on" className="input" type="date" defaultValue={today()} required /></Field>
          <Field label="Libellé *" error={errors.label}><input name="label" className="input" required /></Field>
          <Field label="Montant (FCFA) *" error={errors.amount}><input name="amount" className="input" type="number" min={1} required /></Field>
          <Field label="Mode *"><select name="method" className="input">{Object.entries(METHOD_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select></Field>
          <Field label="Bénéficiaire"><input name="payee" className="input" /></Field>
          <Field label="Référence (facture, transaction)"><input name="reference" className="input" /></Field>
          <Field label="Justificatif (photo / PDF)"><input name="receipt" className="input" type="file" accept="image/*,application/pdf" /></Field>
        </div>
        <Field label="Notes"><textarea name="notes" className="input" rows={2} /></Field>
        <ErrorBox message={error} />
        <div className="flex justify-end gap-2"><button type="button" className="btn-secondary" onClick={onClose}>Annuler</button><button className="btn-primary" disabled={mutation.isPending}>Enregistrer</button></div>
      </form>
    </Modal>
  )
}

export default function CashbookPage() {
  const { isManager } = useAuth()
  const toast = useToast()
  const qc = useQueryClient()
  const [period, setPeriod] = useState({ from: startOfMonth(), to: endOfMonth() })
  const [tab, setTab] = useState('synthese')
  const [addOpen, setAddOpen] = useState(false)
  const [cancelId, setCancelId] = useState(null)

  const exportCsv = () => cashbook.exportCsv(period)
    .then((blob) => saveBlob(blob, `caisse-${period.from}-${period.to}.csv`))
    .catch((e) => toast.error(apiError(e).message))

  const report = useQuery({ queryKey: ['cashbook', period], queryFn: () => cashbook.report(period) })
  const categories = useQuery({ queryKey: ['expense-categories'], queryFn: () => cashbook.categories({ active: 1 }) })
  const expenses = useQuery({ queryKey: ['expenses', period], queryFn: () => cashbook.expenses({ ...period, per_page: 100 }), enabled: tab === 'depenses' })
  const cancel = useMutation({ mutationFn: ({ id, reason }) => cashbook.cancelExpense(id, reason), onSuccess: () => { qc.invalidateQueries({ queryKey: ['expenses'] }); qc.invalidateQueries({ queryKey: ['cashbook'] }); setCancelId(null); toast.success('Dépense annulée') }, onError: (e) => toast.error(apiError(e).message) })

  const quick = (type) => {
    const map = {
      today: { from: today(), to: today() },
      week: { from: dayjs().startOf('week').format('YYYY-MM-DD'), to: dayjs().endOf('week').format('YYYY-MM-DD') },
      month: { from: startOfMonth(), to: endOfMonth() },
      lastMonth: { from: dayjs().subtract(1, 'month').startOf('month').format('YYYY-MM-DD'), to: dayjs().subtract(1, 'month').endOf('month').format('YYYY-MM-DD') },
    }
    setPeriod(map[type])
  }

  const r = report.data

  return (
    <div>
      <PageHeader title="Caisse" subtitle="Entrées, sorties, solde et manquants" actions={<><button className="btn-primary" onClick={() => setAddOpen(true)}><Plus size={16} />Dépense</button>{isManager && <button className="btn-secondary" onClick={exportCsv}><Download size={16} />Exporter (Excel)</button>}</>} />

      <div className="card mb-4 flex flex-wrap items-end gap-3">
        <div><label className="label">Du</label><input className="input" type="date" value={period.from} onChange={(e) => setPeriod({ ...period, from: e.target.value })} /></div>
        <div><label className="label">Au</label><input className="input" type="date" value={period.to} onChange={(e) => setPeriod({ ...period, to: e.target.value })} /></div>
        <div className="flex gap-1">
          <button className="btn-ghost text-xs" onClick={() => quick('today')}>Aujourd'hui</button>
          <button className="btn-ghost text-xs" onClick={() => quick('week')}>Semaine</button>
          <button className="btn-ghost text-xs" onClick={() => quick('month')}>Ce mois</button>
          <button className="btn-ghost text-xs" onClick={() => quick('lastMonth')}>Mois dernier</button>
        </div>
      </div>

      {report.isLoading || !r ? <Spinner /> : (
        <>
          <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
            <StatCard label="Entrées" value={r.income.total} money sub={`${r.income.count} encaissement(s)`} tone="good" />
            <StatCard label="Sorties" value={r.expenses.total} money sub={`${r.expenses.count} dépense(s)`} tone="warn" />
            <StatCard label="Solde de la période" value={r.balance} money tone={r.balance >= 0 ? 'brand' : 'bad'} />
            <StatCard label="Solde cumulé de l'année" value={r.year_to_date.balance} money sub={`${formatFcfa(r.year_to_date.income)} entrées - ${formatFcfa(r.year_to_date.expenses)} sorties`} />
          </div>

          <div className="mt-4 flex gap-1 border-b">
            {[['synthese', 'Synthèse'], ['depenses', 'Dépenses'], ['manquants', 'Manquants']].map(([k, v]) => <button key={k} className={`px-4 py-2 text-sm font-medium transition-colors duration-200 ${tab === k ? 'border-b-2 border-phenix-500 text-phenix-700' : 'text-slate-500 hover:text-slate-800'}`} onClick={() => setTab(k)}>{v}</button>)}
          </div>

          {tab === 'synthese' && (
            <div className="mt-4 grid gap-4 lg:grid-cols-3">
              <div className="card">
                <h3 className="mb-3 font-semibold">Entrées par mode</h3>
                <ul className="space-y-2 text-sm">{Object.entries(r.income.by_method).map(([m, v]) => <li key={m} className="flex justify-between"><span>{METHOD_LABELS[m]}</span><span className="font-semibold">{formatFcfa(v)}</span></li>)}</ul>
                <h3 className="mb-2 mt-4 font-semibold">Entrées par type</h3>
                <ul className="space-y-2 text-sm">{Object.entries(r.income.by_type).map(([t, v]) => <li key={t} className="flex justify-between"><span>{TYPE_LABELS[t]}</span><span className="font-semibold">{formatFcfa(v)}</span></li>)}</ul>
              </div>
              <div className="card">
                <h3 className="mb-3 font-semibold">Sorties par catégorie</h3>
                <ul className="space-y-2 text-sm">
                  {Object.entries(r.expenses.by_category).map(([c, v]) => <li key={c} className="flex justify-between"><span>{c}</span><span className="font-semibold">{formatFcfa(v)}</span></li>)}
                  {Object.keys(r.expenses.by_category).length === 0 && <li className="text-slate-500">Aucune dépense.</li>}
                </ul>
              </div>
              <div className="card overflow-y-auto" style={{ maxHeight: 360 }}>
                <h3 className="mb-3 font-semibold">Jour par jour</h3>
                <table className="w-full text-xs">
                  <thead className="text-left text-slate-500"><tr><th>Date</th><th className="text-right">Entrées</th><th className="text-right">Sorties</th><th className="text-right">Solde</th></tr></thead>
                  <tbody>{r.days.filter((d) => d.income || d.expenses).map((d) => <tr key={d.date} className="border-t"><td className="py-1">{formatDate(d.date)}</td><td className="text-right text-emerald-700">{formatFcfa(d.income)}</td><td className="text-right text-amber-700">{formatFcfa(d.expenses)}</td><td className="text-right font-semibold">{formatFcfa(d.income - d.expenses)}</td></tr>)}</tbody>
                </table>
              </div>
            </div>
          )}

          {tab === 'depenses' && (expenses.isLoading ? <Spinner /> : (
            <div className="card mt-4 overflow-x-auto p-0">
              <table className="w-full text-sm">
                <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Date</th><th className="px-4 py-3">Catégorie</th><th className="px-4 py-3">Libellé</th><th className="px-4 py-3">Bénéficiaire</th><th className="px-4 py-3">Montant</th><th className="px-4 py-3">Mode</th><th className="px-4 py-3">Par</th><th></th></tr></thead>
                <tbody className="divide-y">
                  {(expenses.data?.data || []).map((e) => (
                    <tr key={e.id}>
                      <td className="px-4 py-2">{formatDate(e.spent_on)}</td>
                      <td className="px-4 py-2"><Badge className="bg-slate-200 text-slate-700">{e.category?.name}</Badge></td>
                      <td className="px-4 py-2">{e.label}{e.receipt_url && <a href={e.receipt_url} target="_blank" rel="noreferrer" className="ml-2 text-xs underline">justificatif</a>}</td>
                      <td className="px-4 py-2 text-slate-600">{e.payee || '-'}</td>
                      <td className="px-4 py-2 font-semibold">{formatFcfa(e.amount)}</td>
                      <td className="px-4 py-2">{e.method_label}</td>
                      <td className="px-4 py-2 text-slate-500">{e.recorded_by?.name}</td>
                      <td className="px-4 py-2 text-right">{isManager && <button className="btn-ghost text-xs text-red-600" onClick={() => setCancelId(e.id)}>Annuler</button>}</td>
                    </tr>
                  ))}
                  {expenses.data?.data?.length === 0 && <tr><td colSpan={8} className="px-4 py-8 text-center text-slate-500">Aucune dépense sur la période.</td></tr>}
                </tbody>
              </table>
            </div>
          ))}

          {tab === 'manquants' && (
            <div className="mt-4 grid gap-4 lg:grid-cols-2">
              <div className="card">
                <h3 className="mb-3 font-semibold">Abonnements commencés</h3>
                <ul className="space-y-2 text-sm">
                  <li className="flex justify-between"><span>Attendu (prix des abonnements)</span><span className="font-semibold">{formatFcfa(r.outstanding.expected)}</span></li>
                  <li className="flex justify-between"><span>Encaissé</span><span className="font-semibold text-emerald-700">{formatFcfa(r.outstanding.collected)}</span></li>
                  <li className="flex justify-between border-t pt-2"><span>Reste dû (manquants)</span><span className="font-bold text-red-600">{formatFcfa(r.outstanding.missing)}</span></li>
                  <li className="flex justify-between"><span>Abonnements non soldés</span><span>{r.outstanding.unpaid_subscriptions}</span></li>
                  <li className="flex justify-between"><span>Taux de recouvrement</span><span className="font-semibold">{Math.round(r.outstanding.recovery_rate * 100)} %</span></li>
                </ul>
              </div>
              <div className="card">
                <h3 className="mb-3 font-semibold">Retards de paiement</h3>
                <p className="text-3xl font-bold text-red-600">{formatFcfa(r.outstanding.late_amount)}</p>
                <p className="text-sm text-slate-500">{r.outstanding.late_count} adhérent(s) en retard</p>
                <Link to="/retards" className="btn-secondary mt-4">Voir la liste et relancer</Link>
              </div>
            </div>
          )}
        </>
      )}

      <ExpenseModal open={addOpen} onClose={() => setAddOpen(false)} categories={categories.data || []} />
      <ConfirmReason open={Boolean(cancelId)} title="Annuler la dépense" onClose={() => setCancelId(null)} loading={cancel.isPending} onConfirm={(reason) => cancel.mutate({ id: cancelId, reason })} />
    </div>
  )
}
