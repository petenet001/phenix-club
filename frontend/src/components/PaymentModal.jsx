import { useEffect, useMemo, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { members, payments } from '../api/resources'
import { apiError } from '../api/client'
import { ErrorBox, Field, Modal, Spinner } from '../components/ui'
import { useToast } from '../context/ToastContext'
import { formatDate, formatFcfa, METHOD_LABELS } from '../utils/format'

/**
 * Encaissement : propose automatiquement les frais d'inscription (si dus) et les abonnements à régler.
 * Chaque ligne cochée devient un paiement avec son propre reçu.
 */
export default function PaymentModal({ member, open, onClose, onPaid }) {
  const toast = useToast()
  const qc = useQueryClient()
  const [method, setMethod] = useState('cash')
  const [reference, setReference] = useState('')
  const [lines, setLines] = useState({})
  const [other, setOther] = useState({ enabled: false, amount: '', notes: '' })
  const [error, setError] = useState(null)

  const outstanding = useQuery({ queryKey: ['outstanding', member?.id], queryFn: () => members.outstanding(member.id), enabled: open && Boolean(member) })

  useEffect(() => {
    if (!outstanding.data) return
    const next = {}
    if (outstanding.data.registration_fee_due > 0) next.registration = { checked: true, amount: outstanding.data.registration_fee_due }
    outstanding.data.subscriptions.forEach((s, i) => {
      next[`sub_${s.id}`] = { checked: i === 0, amount: s.balance_due, subscription_id: s.id, max: s.balance_due }
    })
    setLines(next)
  }, [outstanding.data])

  const total = useMemo(() => {
    let t = Object.values(lines).filter((l) => l.checked).reduce((s, l) => s + Number(l.amount || 0), 0)
    if (other.enabled) t += Number(other.amount || 0)
    return t
  }, [lines, other])

  const mutation = useMutation({
    mutationFn: () => {
      const payload = {
        member_id: member.id,
        method,
        reference: reference || undefined,
        lines: [
          ...Object.entries(lines).filter(([, l]) => l.checked && Number(l.amount) > 0).map(([key, l]) => (key === 'registration' ? { type: 'registration', amount: Number(l.amount) } : { type: 'subscription', amount: Number(l.amount), subscription_id: l.subscription_id })),
          ...(other.enabled && Number(other.amount) > 0 ? [{ type: 'other', amount: Number(other.amount) }] : []),
        ],
      }
      if (other.enabled && other.notes) payload.notes = other.notes
      return payments.batch(payload)
    },
    onSuccess: (list) => {
      qc.invalidateQueries({ queryKey: ['member', String(member.id)] })
      qc.invalidateQueries({ queryKey: ['members'] })
      qc.invalidateQueries({ queryKey: ['payments'] })
      qc.invalidateQueries({ queryKey: ['member-subscriptions', member.id] })
      qc.invalidateQueries({ queryKey: ['outstanding', member.id] })
      toast.success(`${list.length} paiement(s) enregistré(s)`)
      onPaid?.(list)
    },
    onError: (err) => setError(apiError(err).message),
  })

  const update = (key, patch) => setLines((l) => ({ ...l, [key]: { ...l[key], ...patch } }))

  return (
    <Modal open={open} title={`Encaisser - ${member?.full_name || ''}`} onClose={onClose}>
      {outstanding.isLoading ? (
        <Spinner />
      ) : (
        <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); setError(null); mutation.mutate() }}>
          <div className="space-y-2">
            {lines.registration && (
              <label className="flex items-center gap-3 rounded-lg border p-3 text-sm">
                <input type="checkbox" checked={lines.registration.checked} onChange={(e) => update('registration', { checked: e.target.checked })} />
                <span className="flex-1">Frais d'inscription</span>
                <input className="input w-32 text-right" type="number" min={1} value={lines.registration.amount} onChange={(e) => update('registration', { amount: e.target.value })} />
              </label>
            )}
            {(outstanding.data?.subscriptions || []).map((s) => {
              const key = `sub_${s.id}`
              return (
                <label key={s.id} className="flex items-center gap-3 rounded-lg border p-3 text-sm">
                  <input type="checkbox" checked={lines[key]?.checked || false} onChange={(e) => update(key, { checked: e.target.checked })} />
                  <span className="flex-1">
                    {s.plan} <span className="text-xs text-slate-500">du {formatDate(s.start_date)} au {formatDate(s.end_date)}</span>
                    <span className="block text-xs text-slate-500">Prix {formatFcfa(s.price)} - déjà payé {formatFcfa(s.amount_paid)} - reste {formatFcfa(s.balance_due)}</span>
                  </span>
                  <input className="input w-32 text-right" type="number" min={1} max={s.balance_due} value={lines[key]?.amount ?? ''} onChange={(e) => update(key, { amount: e.target.value })} />
                </label>
              )
            })}
            {!lines.registration && outstanding.data?.subscriptions?.length === 0 && <p className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">Rien à encaisser : l'adhérent est à jour.</p>}
            <label className="flex items-center gap-3 rounded-lg border border-dashed p-3 text-sm">
              <input type="checkbox" checked={other.enabled} onChange={(e) => setOther({ ...other, enabled: e.target.checked })} />
              <span className="flex-1">Autre (produit, service)</span>
              <input className="input w-32 text-right" type="number" min={1} placeholder="Montant" value={other.amount} onChange={(e) => setOther({ ...other, amount: e.target.value })} disabled={!other.enabled} />
            </label>
            {other.enabled && <input className="input" placeholder="Libellé (ex : bouteille, gants)" value={other.notes} onChange={(e) => setOther({ ...other, notes: e.target.value })} />}
          </div>

          <div className="grid gap-3 md:grid-cols-2">
            <Field label="Mode de paiement">
              <select className="input" value={method} onChange={(e) => setMethod(e.target.value)}>
                {Object.entries(METHOD_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
              </select>
            </Field>
            <Field label={method === 'cash' ? 'Référence (optionnel)' : 'Référence de transaction *'}>
              <input className="input" value={reference} onChange={(e) => setReference(e.target.value)} required={method !== 'cash'} />
            </Field>
          </div>

          <div className="flex items-center justify-between rounded-lg bg-phenix-50 p-3">
            <span className="font-semibold">Total à encaisser</span>
            <span className="text-xl font-bold text-phenix-700">{formatFcfa(total)}</span>
          </div>
          <ErrorBox message={error} />
          <div className="flex justify-end gap-2">
            <button type="button" className="btn-secondary" onClick={onClose}>Annuler</button>
            <button className="btn-primary" disabled={mutation.isPending || total <= 0}>{mutation.isPending ? 'Encaissement...' : 'Encaisser et imprimer le reçu'}</button>
          </div>
        </form>
      )}
    </Modal>
  )
}
