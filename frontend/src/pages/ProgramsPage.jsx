import { useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { programs } from '../api/resources'
import { apiError } from '../api/client'
import MemberSearch from '../components/MemberSearch'
import ProgramForm from '../components/ProgramForm'
import ProgramView from '../components/ProgramView'
import { ConfirmReason, Modal, PageHeader, Spinner } from '../components/ui'
import { useToast } from '../context/ToastContext'

export default function ProgramsPage() {
  const toast = useToast()
  const qc = useQueryClient()
  const [status, setStatus] = useState('proposed')
  const [member, setMember] = useState(null)
  const [decline, setDecline] = useState(null)

  const list = useQuery({ queryKey: ['programs', status], queryFn: () => programs.list({ status }) })
  const action = useMutation({
    mutationFn: ({ act, id, note }) => (act === 'approve' ? programs.approve(id, note) : act === 'decline' ? programs.decline(id, note) : programs.archive(id)),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['programs'] }); qc.invalidateQueries({ queryKey: ['dashboard'] }); setDecline(null); toast.success('Programme mis à jour') },
    onError: (e) => toast.error(apiError(e).message),
  })
  const create = useMutation({
    mutationFn: ({ payload, impose }) => programs.create(member.id, { ...payload, impose }),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['programs'] }); setMember(null); toast.success('Programme enregistré') },
    onError: (e) => toast.error(apiError(e).message),
  })

  return (
    <div>
      <PageHeader title="Programmes" subtitle="Entraînement et alimentation : propositions à traiter, programmes en vigueur" />
      <div className="card mb-4">
        <p className="label">Créer un programme pour un adhérent</p>
        <MemberSearch onSelect={setMember} />
      </div>
      <div className="mb-4 flex gap-1 border-b">
        {[['proposed', 'À traiter'], ['approved', 'Validés'], ['imposed', 'Imposés'], ['declined', 'Refusés'], ['archived', 'Archivés']].map(([k, v]) => <button key={k} className={`px-4 py-2 text-sm font-medium ${status === k ? 'border-b-2 border-phenix-500 text-phenix-700' : 'text-slate-500'}`} onClick={() => setStatus(k)}>{v}</button>)}
      </div>
      {list.isLoading ? <Spinner /> : (
        <div className="space-y-4">
          {(list.data?.data || []).map((p) => (
            <ProgramView
              key={p.id}
              program={p}
              actions={
                <div className="flex items-center gap-2">
                  <Link to={`/adherents/${p.member.id}`} className="text-sm font-medium underline">{p.member.full_name}</Link>
                  {p.status === 'proposed' && <button className="btn-primary text-xs" onClick={() => action.mutate({ act: 'approve', id: p.id })}>Valider</button>}
                  {p.status === 'proposed' && <button className="btn-secondary text-xs text-red-600" onClick={() => setDecline(p.id)}>Refuser</button>}
                  {p.is_effective && <button className="btn-ghost text-xs" onClick={() => action.mutate({ act: 'archive', id: p.id })}>Archiver</button>}
                </div>
              }
            />
          ))}
          {list.data?.data?.length === 0 && <p className="card text-center text-sm text-slate-500">Aucun programme dans cette catégorie.</p>}
        </div>
      )}
      <Modal open={Boolean(member)} title={`Nouveau programme - ${member?.full_name || ''}`} onClose={() => setMember(null)} wide>
        <ProgramForm onCancel={() => setMember(null)} loading={create.isPending} onSubmit={(payload, impose) => create.mutate({ payload, impose })} />
      </Modal>
      <ConfirmReason open={Boolean(decline)} title="Refuser le programme" label="Motif du refus" onClose={() => setDecline(null)} loading={action.isPending} onConfirm={(note) => action.mutate({ act: 'decline', id: decline, note })} />
    </div>
  )
}
