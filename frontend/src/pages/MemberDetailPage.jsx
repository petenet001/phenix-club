import { Banknote, MessageCircle, Pencil, RefreshCw } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import { members, payments, programs, subscriptions } from '../api/resources'
import { apiError } from '../api/client'
import { Badge, ConfirmReason, ErrorBox, Modal, PageHeader, Spinner } from '../components/ui'
import SubscribeModal from '../components/SubscribeModal'
import PaymentModal from '../components/PaymentModal'
import ReceiptModal from '../components/ReceiptModal'
import AppAccessCard from '../components/AppAccessCard'
import ReminderModal from '../components/ReminderModal'
import ProgramForm from '../components/ProgramForm'
import ProgramView from '../components/ProgramView'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { formatDate, formatDateTime, formatFcfa, STATUS_COLORS, STATUS_LABELS, SUB_STATUS_LABELS } from '../utils/format'

const TABS = ['abonnements', 'paiements', 'presences', 'programmes', 'relances']

export default function MemberDetailPage() {
  const { id } = useParams()
  const [params, setParams] = useSearchParams()
  const { isManager, isCoach, canReviewPrograms } = useAuth()
  const toast = useToast()
  const qc = useQueryClient()
  const [tab, setTab] = useState('abonnements')
  const [modal, setModal] = useState(null) // 'souscrire' | 'encaisser' | 'relancer' | 'statut' | 'programme' | { cancelSub } | { cancelPay }
  const [error, setError] = useState(null)
  const [receiptIds, setReceiptIds] = useState(null)

  const member = useQuery({ queryKey: ['member', id], queryFn: () => members.get(id) })
  const subs = useQuery({ queryKey: ['member-subscriptions', Number(id)], queryFn: () => members.subscriptions(id), enabled: tab === 'abonnements' })
  const pays = useQuery({ queryKey: ['payments', 'member', id], queryFn: () => members.payments(id, { per_page: 50 }), enabled: tab === 'paiements' })
  const atts = useQuery({ queryKey: ['attendances', 'member', id], queryFn: () => members.attendances(id, { per_page: 60 }), enabled: tab === 'presences' })
  const progs = useQuery({ queryKey: ['programs', 'member', id], queryFn: () => members.programs(id), enabled: tab === 'programmes' })
  const rems = useQuery({ queryKey: ['member-reminders', Number(id)], queryFn: () => members.reminders(id), enabled: tab === 'relances' })

  useEffect(() => {
    const action = params.get('action')
    if (action) {
      setModal(action)
      setParams({}, { replace: true })
    }
  }, [params, setParams])

  const invalidate = () => {
    qc.invalidateQueries({ queryKey: ['member', id] })
    qc.invalidateQueries({ queryKey: ['members'] })
    qc.invalidateQueries({ queryKey: ['member-subscriptions', Number(id)] })
    qc.invalidateQueries({ queryKey: ['payments'] })
    qc.invalidateQueries({ queryKey: ['outstanding', Number(id)] })
  }

  const validate = useMutation({ mutationFn: () => members.validate(id), onSuccess: () => { invalidate(); toast.success('Adhésion validée') }, onError: (e) => toast.error(apiError(e).message) })
  const status = useMutation({ mutationFn: (payload) => members.updateStatus(id, payload), onSuccess: () => { invalidate(); setModal(null); toast.success('Statut mis à jour') }, onError: (e) => setError(apiError(e).message) })
  const cancelSub = useMutation({ mutationFn: ({ subId, reason }) => subscriptions.cancel(subId, reason), onSuccess: () => { invalidate(); setModal(null); toast.success('Abonnement annulé') }, onError: (e) => toast.error(apiError(e).message) })
  const cancelPay = useMutation({ mutationFn: ({ payId, reason }) => payments.cancel(payId, reason), onSuccess: () => { invalidate(); setModal(null); toast.success('Paiement annulé') }, onError: (e) => toast.error(apiError(e).message) })
  const createProgram = useMutation({ mutationFn: ({ payload, impose }) => programs.create(id, { ...payload, impose }), onSuccess: () => { qc.invalidateQueries({ queryKey: ['programs'] }); setModal(null); toast.success('Programme enregistré') }, onError: (e) => toast.error(apiError(e).message) })
  const programAction = useMutation({
    mutationFn: ({ action, pid, note }) => (action === 'approve' ? programs.approve(pid, note) : action === 'decline' ? programs.decline(pid, note) : programs.archive(pid)),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['programs'] }); setModal(null); toast.success('Programme mis à jour') },
    onError: (e) => toast.error(apiError(e).message),
  })

  if (member.isLoading) return <Spinner />
  if (member.error) return <ErrorBox message={apiError(member.error).message} />
  const m = member.data
  const current = m.current_subscription

  return (
    <div>
      <PageHeader
        title={m.full_name}
        subtitle={`${m.code} - inscrit le ${formatDate(m.joined_at)}${m.registration_source === 'online' ? ' (en ligne)' : ''}`}
        actions={
          <>
            <button className="btn-primary" onClick={() => setModal('encaisser')}><Banknote size={16} />Encaisser</button>
            <button className="btn-secondary" onClick={() => setModal('souscrire')} disabled={m.status === 'suspended'}><RefreshCw size={16} />{current ? 'Renouveler' : 'Souscrire'}</button>
            <button className="btn-secondary" onClick={() => setModal('relancer')}><MessageCircle size={16} />Relancer</button>
            <Link to={`/adherents/${id}/modifier`} className="btn-secondary"><Pencil size={16} />Modifier</Link>
          </>
        }
      />

      {m.status === 'pending' && (
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4">
          <p className="text-sm text-amber-900"><strong>Demande d'adhésion en attente.</strong> Elle sera validée automatiquement au premier encaissement, ou manuellement ici.</p>
          <div className="flex gap-2">
            <button className="btn-primary" onClick={() => setModal('encaisser')}>Encaisser</button>
            <button className="btn-secondary" onClick={() => validate.mutate()} disabled={validate.isPending}>Valider sans paiement</button>
          </div>
        </div>
      )}

      <div className="grid gap-4 lg:grid-cols-3">
        <div className="card flex gap-4">
          {m.photo_url ? <img src={m.photo_url} alt="" className="h-24 w-24 rounded-xl object-cover" /> : <div className="flex h-24 w-24 items-center justify-center rounded-xl bg-slate-200 text-2xl font-bold">{m.first_name[0]}{m.last_name[0]}</div>}
          <div className="text-sm">
            <Badge className={STATUS_COLORS[m.status]}>{STATUS_LABELS[m.status]}</Badge>
            {m.is_late && <Badge className="ml-1 bg-red-100 text-red-800">Retard {m.days_late} j</Badge>}
            <p className="mt-2">{m.phone}</p>
            {m.email && <p className="text-slate-500">{m.email}</p>}
            {m.address && <p className="text-slate-500">{m.address}</p>}
            {m.birth_date && <p className="text-slate-500">Né(e) le {formatDate(m.birth_date)}</p>}
            {m.emergency_contact_name && <p className="mt-1 text-xs text-slate-500">Urgence : {m.emergency_contact_name} {m.emergency_contact_phone}</p>}
            {isManager && <button className="btn-ghost mt-2 px-0 text-xs underline" onClick={() => setModal('statut')}>Changer le statut</button>}
          </div>
        </div>
        <div className="card text-sm">
          <p className="label">Abonnement courant</p>
          {current ? (
            <>
              <p className="text-lg font-bold">{current.plan?.name}</p>
              <p>Du {formatDate(current.start_date)} au <strong>{formatDate(current.end_date)}</strong> <Badge className="ml-1 bg-slate-200 text-slate-700">{SUB_STATUS_LABELS[current.status]}</Badge></p>
              {current.sessions_count && <p>{current.sessions_count - current.sessions_used} séance(s) restante(s) sur {current.sessions_count}</p>}
              <p className={current.balance_due > 0 ? 'mt-1 font-semibold text-red-600' : 'mt-1 text-emerald-600'}>{current.balance_due > 0 ? `Reste dû : ${formatFcfa(current.balance_due)}` : 'Soldé'}</p>
            </>
          ) : <p className="text-slate-500">Aucun abonnement</p>}
        </div>
        <div className="card text-sm">
          <p className="label">Dernière présence</p>
          <p className="text-lg font-bold">{m.last_attendance_at ? formatDateTime(m.last_attendance_at) : 'Jamais'}</p>
          {m.is_late && <p className="mt-2 text-red-600">Montant attendu : <strong>{formatFcfa(m.amount_due)}</strong></p>}
          {m.validated_at && <p className="mt-2 text-xs text-slate-500">Adhésion validée le {formatDate(m.validated_at)}</p>}
          <div className="mt-3 rounded-lg bg-slate-50 p-2 text-center text-xs text-slate-500">QR : <span className="font-mono">{m.qr_token}</span></div>
        </div>
        {!isCoach && <AppAccessCard member={m} />}
      </div>

      <div className="mt-6 flex gap-1 overflow-x-auto border-b">
        {TABS.map((t) => (
          <button key={t} className={`px-4 py-2 text-sm font-medium capitalize transition-colors duration-200 ${tab === t ? 'border-b-2 border-phenix-500 text-phenix-700' : 'text-slate-500 hover:text-slate-800'}`} onClick={() => setTab(t)}>{t}</button>
        ))}
      </div>

      <div key={tab} className="mt-4 animate-fade-in">
        {tab === 'abonnements' && (subs.isLoading ? <Spinner /> : (
          <div className="card overflow-x-auto p-0">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-2">Formule</th><th className="px-4 py-2">Période</th><th className="px-4 py-2">Prix</th><th className="px-4 py-2">Payé</th><th className="px-4 py-2">Statut</th><th></th></tr></thead>
              <tbody className="divide-y">
                {(subs.data || []).map((s) => (
                  <tr key={s.id}>
                    <td className="px-4 py-2">{s.plan?.name}{s.sessions_count && <span className="block text-xs text-slate-500">{s.sessions_used}/{s.sessions_count} séances</span>}</td>
                    <td className="px-4 py-2">{formatDate(s.start_date)} - {formatDate(s.end_date)}</td>
                    <td className="px-4 py-2">{formatFcfa(s.price_at_subscription)}</td>
                    <td className="px-4 py-2">{formatFcfa(s.amount_paid)}{s.balance_due > 0 && <span className="block text-xs text-red-600">reste {formatFcfa(s.balance_due)}</span>}</td>
                    <td className="px-4 py-2"><Badge className="bg-slate-200 text-slate-700">{SUB_STATUS_LABELS[s.status]}</Badge>{s.cancel_reason && <span className="block text-xs text-slate-500">{s.cancel_reason}</span>}</td>
                    <td className="px-4 py-2 text-right">{isManager && s.status !== 'cancelled' && <button className="btn-ghost text-xs text-red-600" onClick={() => setModal({ cancelSub: s.id })}>Annuler</button>}</td>
                  </tr>
                ))}
                {subs.data?.length === 0 && <tr><td colSpan={6} className="px-4 py-6 text-center text-slate-500">Aucun abonnement.</td></tr>}
              </tbody>
            </table>
          </div>
        ))}

        {tab === 'paiements' && (pays.isLoading ? <Spinner /> : (
          <div className="card overflow-x-auto p-0">
            <table className="w-full text-sm">
              <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-2">Reçu</th><th className="px-4 py-2">Date</th><th className="px-4 py-2">Objet</th><th className="px-4 py-2">Montant</th><th className="px-4 py-2">Mode</th><th></th></tr></thead>
              <tbody className="divide-y">
                {(pays.data?.data || []).map((p) => (
                  <tr key={p.id} className={p.cancelled_at ? 'text-slate-400 line-through' : ''}>
                    <td className="px-4 py-2 font-mono text-xs">{p.receipt_number}</td>
                    <td className="px-4 py-2">{formatDateTime(p.paid_at)}</td>
                    <td className="px-4 py-2">{p.type_label}{p.subscription && ` - ${p.subscription.plan}`}</td>
                    <td className="px-4 py-2 font-semibold">{formatFcfa(p.amount)}</td>
                    <td className="px-4 py-2">{p.method_label}{p.reference && <span className="block text-xs">{p.reference}</span>}</td>
                    <td className="px-4 py-2 text-right whitespace-nowrap">
                      <button onClick={() => setReceiptIds([p.id])} className="btn-ghost text-xs">Reçu</button>
                      {isManager && !p.cancelled_at && <button className="btn-ghost text-xs text-red-600" onClick={() => setModal({ cancelPay: p.id })}>Annuler</button>}
                    </td>
                  </tr>
                ))}
                {pays.data?.data?.length === 0 && <tr><td colSpan={6} className="px-4 py-6 text-center text-slate-500">Aucun paiement.</td></tr>}
              </tbody>
            </table>
          </div>
        ))}

        {tab === 'presences' && (atts.isLoading ? <Spinner /> : (
          <div className="card">
            <ul className="divide-y text-sm">
              {(atts.data?.data || []).map((a) => (
                <li key={a.id} className="flex justify-between py-2"><span>{formatDateTime(a.checked_in_at)}</span><span className="text-slate-500">{{ qr: 'QR', app: 'Application', manual: 'Manuel' }[a.method] ?? a.method}{a.forced && <Badge className="ml-2 bg-red-100 text-red-700">forcé</Badge>}{a.subscription?.plan && ` - ${a.subscription.plan}`}</span></li>
              ))}
              {atts.data?.data?.length === 0 && <li className="py-6 text-center text-slate-500">Aucune présence.</li>}
            </ul>
          </div>
        ))}

        {tab === 'programmes' && (progs.isLoading ? <Spinner /> : (
          <div className="space-y-4">
            {progs.data?.meta?.adherence && (
              <div className="card flex flex-wrap items-center gap-6 text-sm">
                <p><span className="label">Jours prévus</span><span className="capitalize">{progs.data.meta.adherence.planned_days.join(', ')}</span></p>
                <p><span className="label">Assiduité 4 semaines</span>{progs.data.meta.adherence.attended_sessions_4w} / {progs.data.meta.adherence.planned_sessions_4w} séances{progs.data.meta.adherence.rate !== null && ` (${Math.round(progs.data.meta.adherence.rate * 100)} %)`}</p>
              </div>
            )}
            {canReviewPrograms && <button className="btn-primary" onClick={() => setModal('programme')}>+ Nouveau programme</button>}
            {(progs.data?.data || []).map((p) => (
              <ProgramView
                key={p.id}
                program={p}
                actions={canReviewPrograms && (
                  <div className="flex gap-1">
                    {p.status === 'proposed' && <button className="btn-secondary text-xs" onClick={() => programAction.mutate({ action: 'approve', pid: p.id })}>Valider</button>}
                    {p.status === 'proposed' && <button className="btn-secondary text-xs text-red-600" onClick={() => setModal({ decline: p.id })}>Refuser</button>}
                    {p.is_effective && <button className="btn-ghost text-xs" onClick={() => programAction.mutate({ action: 'archive', pid: p.id })}>Archiver</button>}
                  </div>
                )}
              />
            ))}
            {progs.data?.data?.length === 0 && <p className="card text-center text-sm text-slate-500">Aucun programme.</p>}
          </div>
        ))}

        {tab === 'relances' && (rems.isLoading ? <Spinner /> : (
          <div className="card">
            <ul className="divide-y text-sm">
              {(rems.data || []).map((r) => (
                <li key={r.id} className="py-2"><p className="flex justify-between"><span>{r.type_label} - {r.channel === 'whatsapp' ? 'WhatsApp' : 'SMS'} <Badge className={r.status === 'sent' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'}>{r.status === 'sent' ? 'envoyée' : 'échec'}</Badge></span><span className="text-slate-500">{formatDateTime(r.sent_at)}</span></p><p className="mt-1 text-xs text-slate-600">{r.message}</p></li>
              ))}
              {rems.data?.length === 0 && <li className="py-6 text-center text-slate-500">Aucune relance.</li>}
            </ul>
          </div>
        ))}
      </div>

      <SubscribeModal member={m} open={modal === 'souscrire'} onClose={() => setModal(null)} onSubscribed={() => setModal('encaisser')} />
      <PaymentModal
        member={m}
        open={modal === 'encaisser'}
        onClose={() => setModal(null)}
        onPaid={(list) => {
          setModal(null)
          if (list[0]) setReceiptIds(list.map((p) => p.id))
        }}
      />
      <ReceiptModal paymentId={receiptIds?.[0]} paymentIds={receiptIds} open={Boolean(receiptIds)} onClose={() => setReceiptIds(null)} />
      <ReminderModal member={m} type={m.is_late ? 'late_payment' : 'absence'} open={modal === 'relancer'} onClose={() => setModal(null)} />
      <ConfirmReason open={Boolean(modal?.cancelSub)} title="Annuler l'abonnement" onClose={() => setModal(null)} loading={cancelSub.isPending} onConfirm={(reason) => cancelSub.mutate({ subId: modal.cancelSub, reason })} />
      <ConfirmReason open={Boolean(modal?.cancelPay)} title="Annuler le paiement" onClose={() => setModal(null)} loading={cancelPay.isPending} onConfirm={(reason) => cancelPay.mutate({ payId: modal.cancelPay, reason })} />
      <ConfirmReason open={Boolean(modal?.decline)} title="Refuser le programme" label="Motif du refus" onClose={() => setModal(null)} loading={programAction.isPending} onConfirm={(note) => programAction.mutate({ action: 'decline', pid: modal.decline, note })} />

      <Modal open={modal === 'statut'} title="Changer le statut" onClose={() => setModal(null)}>
        <form className="space-y-3" onSubmit={(e) => { e.preventDefault(); const fd = new FormData(e.currentTarget); setError(null); status.mutate({ status: fd.get('status'), reason: fd.get('reason') || undefined }) }}>
          <select name="status" className="input" defaultValue={m.status}>
            {Object.entries(STATUS_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
          </select>
          <input name="reason" className="input" placeholder="Motif (ajouté aux notes en cas de suspension)" />
          <p className="text-xs text-slate-500">"Actif" ou "Inactif" valide l'adhésion puis recalcule selon les abonnements.</p>
          <ErrorBox message={error} />
          <div className="flex justify-end gap-2"><button type="button" className="btn-secondary" onClick={() => setModal(null)}>Annuler</button><button className="btn-primary" disabled={status.isPending}>Enregistrer</button></div>
        </form>
      </Modal>

      <Modal open={modal === 'programme'} title="Nouveau programme" onClose={() => setModal(null)} wide>
        <ProgramForm onCancel={() => setModal(null)} loading={createProgram.isPending} onSubmit={(payload, impose) => createProgram.mutate({ payload, impose })} />
      </Modal>
    </div>
  )
}
