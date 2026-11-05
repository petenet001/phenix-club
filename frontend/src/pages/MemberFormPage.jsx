import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { members } from '../api/resources'
import { apiError } from '../api/client'
import { ErrorBox, Field, PageHeader, Spinner } from '../components/ui'
import { useToast } from '../context/ToastContext'

const empty = { last_name: '', first_name: '', gender: '', birth_date: '', phone: '', email: '', address: '', emergency_contact_name: '', emergency_contact_phone: '', notes: '' }

export default function MemberFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const toast = useToast()
  const qc = useQueryClient()
  const [form, setForm] = useState(empty)
  const [photo, setPhoto] = useState(null)
  const [errors, setErrors] = useState({})
  const [message, setMessage] = useState(null)

  const existing = useQuery({ queryKey: ['member', id], queryFn: () => members.get(id), enabled: Boolean(id) })

  useEffect(() => {
    if (existing.data) {
      const m = existing.data
      setForm({ ...empty, ...Object.fromEntries(Object.keys(empty).map((k) => [k, m[k] ?? ''])) })
    }
  }, [existing.data])

  const mutation = useMutation({
    mutationFn: () => {
      const fd = new FormData()
      Object.entries(form).forEach(([k, v]) => v !== '' && fd.append(k, v))
      if (photo) fd.append('photo', photo)
      return id ? members.update(id, fd) : members.create(fd)
    },
    onSuccess: (member) => {
      qc.invalidateQueries({ queryKey: ['members'] })
      qc.invalidateQueries({ queryKey: ['member', String(member.id)] })
      toast.success(id ? 'Fiche mise à jour' : `Adhérent ${member.code} créé`)
      navigate(id ? `/adherents/${member.id}` : `/adherents/${member.id}?action=souscrire`)
    },
    onError: (err) => {
      const e = apiError(err)
      setErrors(e.errors)
      setMessage(e.message)
    },
  })

  if (id && existing.isLoading) return <Spinner />

  const bind = (k) => ({ value: form[k], onChange: (e) => setForm({ ...form, [k]: e.target.value }) })

  return (
    <div className="mx-auto max-w-3xl">
      <PageHeader title={id ? 'Modifier la fiche' : 'Nouvel adhérent'} subtitle={id ? existing.data?.code : 'La demande sera validée au premier paiement'} />
      <form
        className="card space-y-5"
        onSubmit={(e) => {
          e.preventDefault()
          setErrors({})
          setMessage(null)
          mutation.mutate()
        }}
      >
        <div className="grid gap-4 md:grid-cols-2">
          <Field label="Nom *" error={errors.last_name}><input className="input" required {...bind('last_name')} /></Field>
          <Field label="Prénom *" error={errors.first_name}><input className="input" required {...bind('first_name')} /></Field>
          <Field label="Téléphone *" error={errors.phone} hint="Ex : 06 123 45 67 ou +242..."><input className="input" required {...bind('phone')} /></Field>
          <Field label="Email" error={errors.email}><input className="input" type="email" {...bind('email')} /></Field>
          <Field label="Sexe" error={errors.gender}>
            <select className="input" {...bind('gender')}><option value="">-</option><option value="M">Homme</option><option value="F">Femme</option></select>
          </Field>
          <Field label="Date de naissance" error={errors.birth_date}><input className="input" type="date" {...bind('birth_date')} /></Field>
          <Field label="Adresse / quartier" error={errors.address}><input className="input" {...bind('address')} /></Field>
          <Field label="Photo" error={errors.photo}><input className="input" type="file" accept="image/*" capture="user" onChange={(e) => setPhoto(e.target.files?.[0] || null)} /></Field>
          <Field label="Personne à contacter" error={errors.emergency_contact_name}><input className="input" {...bind('emergency_contact_name')} /></Field>
          <Field label="Téléphone du contact" error={errors.emergency_contact_phone}><input className="input" {...bind('emergency_contact_phone')} /></Field>
        </div>
        <Field label="Notes internes" error={errors.notes}><textarea className="input" rows={3} {...bind('notes')} /></Field>
        <ErrorBox message={message} />
        <div className="flex justify-end gap-2">
          <Link to={id ? `/adherents/${id}` : '/adherents'} className="btn-secondary">Annuler</Link>
          <button className="btn-primary" disabled={mutation.isPending}>{mutation.isPending ? 'Enregistrement...' : id ? 'Enregistrer' : 'Créer et souscrire'}</button>
        </div>
      </form>
    </div>
  )
}
