import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { recovery } from '../api/resources'
import { apiError } from '../api/client'
import { ErrorBox, Field, Modal } from '../components/ui'
import { useAuth } from '../context/AuthContext'
import { useToast } from '../context/ToastContext'

const TYPES = { late_payment: 'Retard de paiement', absence: 'Absence', expiry_soon: 'Échéance proche' }

export default function ReminderModal({ member, type: initialType = 'late_payment', open, onClose }) {
  const { isManager } = useAuth()
  const toast = useToast()
  const qc = useQueryClient()
  const [type, setType] = useState(initialType)
  const [channel, setChannel] = useState('whatsapp')
  const [message, setMessage] = useState('')
  const [error, setError] = useState(null)
  const [link, setLink] = useState(null)

  useEffect(() => setType(initialType), [initialType, open])

  const preview = useQuery({ queryKey: ['reminder-preview', member?.id, type], queryFn: () => recovery.reminderPreview({ member_id: member.id, type }), enabled: open && Boolean(member) })
  useEffect(() => { if (preview.data) setMessage(preview.data.message) }, [preview.data])

  const mutation = useMutation({
    mutationFn: (override) => recovery.sendReminder({ member_id: member.id, type, channel, message, override }),
    onSuccess: (r) => {
      qc.invalidateQueries({ queryKey: ['late-payments'] })
      qc.invalidateQueries({ queryKey: ['absences'] })
      qc.invalidateQueries({ queryKey: ['member-reminders', member.id] })
      toast.success('Relance enregistrée')
      if (r.link) {
        setLink(r.link)
        window.open(r.link, '_blank')
      } else {
        onClose()
      }
    },
    onError: (err) => {
      const e = apiError(err)
      setError(e.code === 'reminder_too_recent' && isManager ? `${e.message} Vous pouvez forcer l'envoi.` : e.message)
      if (e.code === 'reminder_too_recent' && isManager) setLink('override')
    },
  })

  return (
    <Modal open={open} title={`Relancer ${member?.full_name || ''}`} onClose={() => { setLink(null); setError(null); onClose() }}>
      <div className="space-y-4">
        <div className="grid gap-3 md:grid-cols-2">
          <Field label="Type"><select className="input" value={type} onChange={(e) => setType(e.target.value)}>{Object.entries(TYPES).map(([k, v]) => <option key={k} value={k}>{v}</option>)}</select></Field>
          <Field label="Canal"><select className="input" value={channel} onChange={(e) => setChannel(e.target.value)}><option value="whatsapp">WhatsApp</option><option value="sms">SMS</option></select></Field>
        </div>
        <Field label="Message"><textarea className="input" rows={5} value={message} onChange={(e) => setMessage(e.target.value)} /></Field>
        <ErrorBox message={error} />
        {link && link !== 'override' && <a href={link} target="_blank" rel="noreferrer" className="btn-secondary w-full">Ouvrir {channel === 'whatsapp' ? 'WhatsApp' : 'les SMS'} avec le message</a>}
        <div className="flex justify-end gap-2">
          <button type="button" className="btn-secondary" onClick={onClose}>Fermer</button>
          {link === 'override' ? (
            <button className="btn-danger" onClick={() => mutation.mutate(true)} disabled={mutation.isPending}>Forcer l'envoi</button>
          ) : (
            <button className="btn-primary" onClick={() => mutation.mutate(false)} disabled={mutation.isPending || !message}>Envoyer</button>
          )}
        </div>
      </div>
    </Modal>
  )
}
