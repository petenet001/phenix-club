import { useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { members } from '../api/resources'
import { apiError } from '../api/client'
import { Modal } from './ui'
import { useToast } from '../context/ToastContext'
import { formatDateTime } from '../utils/format'

/** Accès de l'adhérent à l'application mobile : génération du code (affiché une fois) et retrait. */
export default function AppAccessCard({ member }) {
  const toast = useToast()
  const qc = useQueryClient()
  const [granted, setGranted] = useState(null)
  const refresh = () => qc.invalidateQueries({ queryKey: ['member', String(member.id)] })

  const grant = useMutation({
    mutationFn: () => members.grantAppAccess(member.id),
    onSuccess: (data) => { setGranted(data); refresh() },
    onError: (e) => toast.error(apiError(e).message),
  })
  const revoke = useMutation({
    mutationFn: () => members.revokeAppAccess(member.id),
    onSuccess: () => { refresh(); toast.success('Accès à l\'application retiré') },
    onError: (e) => toast.error(apiError(e).message),
  })

  const onGrant = () => {
    if (member.app_access && !window.confirm('Un nouveau code remplacera l\'ancien et déconnectera ses appareils. Continuer ?')) return
    grant.mutate()
  }

  const whatsapp = granted && `https://wa.me/${granted.phone.replace(/\D/g, '')}?text=${encodeURIComponent(granted.message)}`

  return (
    <div className="card text-sm">
      <p className="label">Application mobile</p>
      {member.app_access ? (
        <>
          <p className="font-semibold text-emerald-700">Accès activé</p>
          <p className="text-xs text-slate-500">
            {member.app_last_login_at ? `Dernière connexion : ${formatDateTime(member.app_last_login_at)}` : 'Jamais connecté'}
          </p>
        </>
      ) : (
        <p className="text-slate-500">Pas encore d'accès. Générez un code pour qu'il suive son abonnement et pointe depuis son téléphone.</p>
      )}
      <div className="mt-3 flex flex-wrap gap-2">
        <button className="btn-secondary text-xs" onClick={onGrant} disabled={grant.isPending}>{member.app_access ? 'Nouveau code' : 'Activer l\'accès'}</button>
        {member.app_access && <button className="btn-ghost text-xs text-red-600" onClick={() => window.confirm('Retirer l\'accès et déconnecter ses appareils ?') && revoke.mutate()} disabled={revoke.isPending}>Retirer</button>}
      </div>

      <Modal open={Boolean(granted)} title="Code d'accès à l'application" onClose={() => setGranted(null)}>
        {granted && (
          <div className="space-y-4 text-center">
            <p className="text-sm text-slate-600">À communiquer à {member.first_name}. Il ne sera plus affiché ensuite.</p>
            <div className="rounded-xl bg-slate-50 p-4">
              <p className="text-xs uppercase tracking-wide text-slate-500">Téléphone</p>
              <p className="font-semibold">{granted.phone}</p>
              <p className="mt-3 text-xs uppercase tracking-wide text-slate-500">Code</p>
              <p className="animate-pop font-mono text-4xl font-bold tracking-[.3em] text-phenix-600">{granted.code}</p>
            </div>
            <div className="flex flex-wrap justify-center gap-2">
              <a className="btn-primary" href={whatsapp} target="_blank" rel="noreferrer">Envoyer par WhatsApp</a>
              <button className="btn-secondary" onClick={() => navigator.clipboard?.writeText(granted.message).then(() => toast.success('Message copié'))}>Copier le message</button>
            </div>
          </div>
        )}
      </Modal>
    </div>
  )
}
