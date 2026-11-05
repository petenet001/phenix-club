import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { members, plans, subscriptions } from '../api/resources'
import { apiError } from '../api/client'
import { ErrorBox, Field, Modal } from '../components/ui'
import { useToast } from '../context/ToastContext'
import { formatFcfa } from '../utils/format'
import dayjs from 'dayjs'

/** Souscription ou renouvellement, puis proposition d'encaissement. */
export default function SubscribeModal({ member, open, onClose, onSubscribed }) {
  const toast = useToast()
  const qc = useQueryClient()
  const [planId, setPlanId] = useState('')
  const [startDate, setStartDate] = useState('')
  const [error, setError] = useState(null)

  const planList = useQuery({ queryKey: ['plans', 'active'], queryFn: () => plans.list({ active: 1 }), enabled: open })
  const suggested = useQuery({ queryKey: ['suggested-start', member?.id], queryFn: () => members.suggestedStart(member.id), enabled: open && Boolean(member) })

  useEffect(() => {
    if (suggested.data) setStartDate(suggested.data.suggested_start_date)
  }, [suggested.data])

  useEffect(() => {
    if (planList.data?.length && !planId) setPlanId(String(planList.data[0].id))
  }, [planList.data, planId])

  const plan = planList.data?.find((p) => String(p.id) === planId)
  const endDate = plan && startDate ? dayjs(startDate).add(plan.duration_days - 1, 'day').format('DD/MM/YYYY') : null

  const mutation = useMutation({
    mutationFn: () => subscriptions.create({ member_id: member.id, subscription_plan_id: Number(planId), start_date: startDate || undefined }),
    onSuccess: (sub) => {
      qc.invalidateQueries({ queryKey: ['member', String(member.id)] })
      qc.invalidateQueries({ queryKey: ['members'] })
      qc.invalidateQueries({ queryKey: ['member-subscriptions', member.id] })
      qc.invalidateQueries({ queryKey: ['outstanding', member.id] })
      toast.success('Abonnement enregistré')
      onSubscribed?.(sub)
    },
    onError: (err) => {
      const e = apiError(err)
      setError(e.message)
      if (e.details?.suggested_start_date) setStartDate(e.details.suggested_start_date)
    },
  })

  return (
    <Modal open={open} title={`Souscrire - ${member?.full_name || ''}`} onClose={onClose}>
      <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); setError(null); mutation.mutate() }}>
        <Field label="Formule">
          <select className="input" value={planId} onChange={(e) => setPlanId(e.target.value)} required>
            {(planList.data || []).map((p) => (
              <option key={p.id} value={p.id}>{p.name} - {formatFcfa(p.price)} - {p.duration_days} j{p.sessions_count ? ` - ${p.sessions_count} séances` : ''}</option>
            ))}
          </select>
        </Field>
        <Field label="Date de début" hint={suggested.data ? `Proposée : ${dayjs(suggested.data.suggested_start_date).format('DD/MM/YYYY')} (continuité de l'abonnement en cours)` : ''}>
          <input className="input" type="date" value={startDate} onChange={(e) => setStartDate(e.target.value)} required />
        </Field>
        {plan && (
          <div className="rounded-lg bg-slate-50 p-3 text-sm">
            <p>Fin de validité : <strong>{endDate}</strong></p>
            <p>Prix : <strong>{formatFcfa(plan.price)}</strong>{plan.effective_registration_fee > 0 && ` + frais d'inscription ${formatFcfa(plan.effective_registration_fee)} si première adhésion`}</p>
          </div>
        )}
        <ErrorBox message={error} />
        <div className="flex justify-end gap-2">
          <button type="button" className="btn-secondary" onClick={onClose}>Annuler</button>
          <button className="btn-primary" disabled={mutation.isPending || !planId}>Souscrire</button>
        </div>
      </form>
    </Modal>
  )
}
