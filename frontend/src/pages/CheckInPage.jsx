import { Check, MonitorSmartphone, ScanLine, X } from 'lucide-react'
import { useCallback, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { attendances } from '../api/resources'
import { apiError } from '../api/client'
import MemberSearch from '../components/MemberSearch'
import QrScanner from '../components/QrScanner'
import KioskModal from '../components/KioskModal'
import { Field, Modal, PageHeader, Spinner } from '../components/ui'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'
import { formatDate, formatTime, STATUS_COLORS, STATUS_LABELS } from '../utils/format'

const RED_CODES = ['membership_pending', 'member_suspended', 'no_valid_subscription', 'subscription_expired', 'subscription_not_started', 'sessions_exhausted']

export default function CheckInPage() {
  const { isManager, isCoach } = useAuth()
  const toast = useToast()
  const qc = useQueryClient()
  const [scanning, setScanning] = useState(false)
  const [kiosk, setKiosk] = useState(false)
  const [result, setResult] = useState(null) // { kind: 'ok'|'warn'|'refused', member, message, code, forceable }
  const [forceModal, setForceModal] = useState(null)

  const todayList = useQuery({ queryKey: ['attendances', 'today'], queryFn: () => attendances.list({ per_page: 100 }) })

  const checkIn = useMutation({
    mutationFn: (payload) => attendances.create(payload),
    onSuccess: (data) => {
      qc.invalidateQueries({ queryKey: ['attendances'] })
      qc.invalidateQueries({ queryKey: ['members'] })
      setResult({ kind: data.warning ? 'warn' : 'ok', member: data.data.member, message: data.warning || 'Présence enregistrée', attendance: data.data })
      setForceModal(null)
    },
    onError: (err, payload) => {
      const e = apiError(err)
      setResult({ kind: 'refused', message: e.message, code: e.code, forceable: e.details?.forceable, memberId: payload.member_id, qrToken: payload.qr_token })
    },
  })

  const identify = useCallback(
    async (payload) => {
      try {
        const preview = await attendances.check(payload)
        if (preview.allowed) {
          checkIn.mutate({ ...payload, method: payload.qr_token ? 'qr' : 'manual' })
        } else {
          setResult({ kind: 'refused', member: preview.member, message: preview.message, code: preview.error, forceable: !['already_checked_in_today', 'membership_pending'].includes(preview.error), memberId: preview.member?.id })
        }
      } catch (err) {
        toast.error(apiError(err).message)
      }
    },
    [checkIn, toast]
  )

  const onScan = useCallback((text) => identify({ qr_token: text }), [identify])

  const colors = { ok: 'border-emerald-400 bg-emerald-50', warn: 'border-amber-400 bg-amber-50', refused: 'border-red-400 bg-red-50' }
  const titles = { ok: 'Accès autorisé', warn: 'Accès autorisé - paiement attendu', refused: 'Accès refusé' }

  return (
    <div>
      <PageHeader
        title="Pointage"
        subtitle="Borne pour l'application adhérent, ou recherche de l'adhérent à l'accueil"
        actions={
          <>
            {!isCoach && <button className="btn-primary" onClick={() => { setScanning(false); setKiosk(true) }}><MonitorSmartphone size={16} />Ouvrir la borne de pointage</button>}
            <button className="btn-secondary" onClick={() => setScanning((s) => !s)}><ScanLine size={16} />{scanning ? 'Arrêter la caméra' : 'Scanner une carte membre'}</button>
          </>
        }
      />
      <KioskModal open={kiosk} onClose={() => { setKiosk(false); qc.invalidateQueries({ queryKey: ['attendances'] }) }} />

      <div className="grid gap-6 lg:grid-cols-3">
        <div className="space-y-4 lg:col-span-2">
          <div className="card">
            <MemberSearch autoFocus onSelect={(m) => identify({ member_id: m.id })} />
            {scanning && <div className="mt-4"><QrScanner active={scanning} onScan={onScan} /></div>}
          </div>

          {result && (
            <div key={`${result.kind}-${result.member?.id ?? result.message}-${result.attendance?.id ?? ''}`} className={`card border-2 ${result.kind === 'refused' ? 'animate-shake' : 'animate-pop'} ${colors[result.kind]}`}>
              <div className="flex items-start gap-4">
                {result.member?.photo_url ? <img src={result.member.photo_url} alt="" className="h-20 w-20 rounded-xl object-cover" /> : <div className="flex h-20 w-20 items-center justify-center rounded-xl bg-white">{result.kind === 'refused' ? <X size={36} className="text-red-600" /> : <Check size={36} className={result.kind === 'warn' ? 'text-amber-600' : 'text-emerald-600'} />}</div>}
                <div className="flex-1">
                  <p className="text-lg font-bold">{titles[result.kind]}</p>
                  {result.member && (
                    <p className="text-sm">
                      <Link to={`/adherents/${result.member.id}`} className="font-semibold underline">{result.member.full_name}</Link> - {result.member.code}
                      {result.member.status && <span className={`badge ml-2 ${STATUS_COLORS[result.member.status]}`}>{STATUS_LABELS[result.member.status]}</span>}
                    </p>
                  )}
                  <p className="mt-1 text-sm">{result.message}</p>
                  {result.attendance?.subscription && (
                    <p className="mt-1 text-xs text-slate-600">
                      {result.attendance.subscription.plan} - fin le {formatDate(result.attendance.subscription.end_date)}
                      {result.attendance.subscription.sessions_count && ` - ${result.attendance.subscription.sessions_count - result.attendance.subscription.sessions_used} séance(s) restante(s)`}
                    </p>
                  )}
                  {result.member?.current_subscription && result.kind === 'refused' && (
                    <p className="mt-1 text-xs text-slate-600">Dernier abonnement : {result.member.current_subscription.plan?.name} jusqu'au {formatDate(result.member.current_subscription.end_date)}</p>
                  )}
                  <div className="mt-3 flex flex-wrap gap-2">
                    {result.member && (result.kind !== 'ok') && <Link to={`/adherents/${result.member.id}?action=encaisser`} className="btn-primary">Encaisser</Link>}
                    {result.member && result.code === 'no_valid_subscription' && <Link to={`/adherents/${result.member.id}?action=souscrire`} className="btn-secondary">Souscrire</Link>}
                    {result.kind === 'refused' && result.forceable && isManager && <button className="btn-secondary" onClick={() => setForceModal(result)}>Forcer le pointage</button>}
                    <button className="btn-ghost" onClick={() => setResult(null)}>Fermer</button>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>

        <div className="card">
          <h2 className="mb-3 font-semibold">Présents aujourd'hui <span className="badge bg-slate-200 text-slate-700">{todayList.data?.data?.length ?? 0}</span></h2>
          {todayList.isLoading ? (
            <Spinner />
          ) : (
            <ul className="divide-y text-sm">
              {(todayList.data?.data || []).map((a) => (
                <li key={a.id} className="flex items-center justify-between py-2">
                  <Link to={`/adherents/${a.member.id}`} className="font-medium hover:underline">{a.member.full_name}</Link>
                  <span className="text-xs text-slate-500">{formatTime(a.checked_in_at)} {a.method === 'app' && <span className="badge bg-phenix-100 text-phenix-700">appli</span>} {a.forced && <span className="badge bg-red-100 text-red-700">forcé</span>}</span>
                </li>
              ))}
              {todayList.data?.data?.length === 0 && <li className="py-4 text-center text-slate-500">Aucun pointage pour le moment.</li>}
            </ul>
          )}
        </div>
      </div>

      <Modal open={Boolean(forceModal)} title="Forcer le pointage" onClose={() => setForceModal(null)}>
        <form
          onSubmit={(e) => {
            e.preventDefault()
            const reason = new FormData(e.currentTarget).get('reason')
            checkIn.mutate({ member_id: forceModal.memberId || forceModal.member?.id, method: 'manual', force: true, force_reason: reason })
          }}
        >
          <p className="mb-3 text-sm text-slate-600">{forceModal?.message}</p>
          <Field label="Motif (obligatoire)">
            <input name="reason" className="input" required minLength={3} autoFocus />
          </Field>
          <div className="mt-4 flex justify-end gap-2">
            <button type="button" className="btn-secondary" onClick={() => setForceModal(null)}>Annuler</button>
            <button className="btn-danger" disabled={checkIn.isPending}>Forcer</button>
          </div>
        </form>
      </Modal>
    </div>
  )
}
