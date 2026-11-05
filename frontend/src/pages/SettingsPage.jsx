import { useEffect, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { cashbook, plans, settings, users } from '../api/resources'
import { apiError } from '../api/client'
import { Badge, ErrorBox, Field, Modal, PageHeader, PasswordInput, Spinner } from '../components/ui'
import { useToast } from '../context/ToastContext'
import { formatFcfa } from '../utils/format'

const RULE_FIELDS = [
  ['club_name', 'Nom de la salle', 'text'],
  ['club_phone', 'Téléphone (reçus)', 'text'],
  ['club_address', 'Adresse (reçus)', 'text'],
  ['receipt_prefix', 'Préfixe des reçus', 'text'],
  ['registration_fee', "Frais d'inscription (FCFA)", 'number'],
  ['checkin_tolerance_days', 'Tolérance de pointage après échéance (jours)', 'number'],
  ['late_payment_days', 'Seuil de retard de paiement (jours après échéance)', 'number'],
  ['inactivity_days', "Seuil d'absence (jours sans pointage)", 'number'],
  ['expiry_soon_days', "Rappel d'échéance proche (jours avant)", 'number'],
]

function RulesTab() {
  const toast = useToast()
  const qc = useQueryClient()
  const { data, isLoading } = useQuery({ queryKey: ['settings'], queryFn: () => settings.get() })
  const [form, setForm] = useState(null)
  const [error, setError] = useState(null)
  useEffect(() => { if (data) setForm(data.data) }, [data])
  const save = useMutation({ mutationFn: (payload) => settings.update(payload), onSuccess: () => { qc.invalidateQueries({ queryKey: ['settings'] }); toast.success('Paramètres enregistrés') }, onError: (e) => setError(apiError(e).message) })
  if (isLoading || !form) return <Spinner />
  return (
    <form className="card space-y-4" onSubmit={(e) => { e.preventDefault(); setError(null); const payload = {}; RULE_FIELDS.forEach(([k, , t]) => { payload[k] = t === 'number' ? Number(form[k]) : form[k] ?? '' }); payload.online_registration_enabled = Boolean(form.online_registration_enabled); save.mutate(payload) }}>
      <div className="grid gap-4 md:grid-cols-2">
        {RULE_FIELDS.map(([k, label, type]) => (
          <Field key={k} label={label}><input className="input" type={type} min={0} value={form[k] ?? ''} onChange={(e) => setForm({ ...form, [k]: e.target.value })} /></Field>
        ))}
      </div>
      <label className="flex items-center gap-2 text-sm"><input type="checkbox" checked={Boolean(form.online_registration_enabled)} onChange={(e) => setForm({ ...form, online_registration_enabled: e.target.checked })} /> Autoriser les demandes d'adhésion depuis le site web</label>
      <ErrorBox message={error} />
      <div className="flex justify-end"><button className="btn-primary" disabled={save.isPending}>Enregistrer</button></div>
    </form>
  )
}

function PlansTab() {
  const toast = useToast()
  const qc = useQueryClient()
  const { data, isLoading } = useQuery({ queryKey: ['plans', 'all'], queryFn: () => plans.list() })
  const [edit, setEdit] = useState(null)
  const [error, setError] = useState(null)
  const save = useMutation({
    mutationFn: (payload) => (edit?.id ? plans.update(edit.id, payload) : plans.create(payload)),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['plans'] }); setEdit(null); toast.success('Formule enregistrée') },
    onError: (e) => setError(apiError(e).message),
  })
  if (isLoading) return <Spinner />
  return (
    <div>
      <div className="mb-3 flex justify-end"><button className="btn-primary" onClick={() => setEdit({})}>+ Formule</button></div>
      <div className="card overflow-x-auto p-0">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Nom</th><th className="px-4 py-3">Durée</th><th className="px-4 py-3">Prix</th><th className="px-4 py-3">Séances</th><th className="px-4 py-3">Frais d'inscription</th><th className="px-4 py-3">Abonnés</th><th className="px-4 py-3">Statut</th><th></th></tr></thead>
          <tbody className="divide-y">
            {(data || []).map((p) => (
              <tr key={p.id}>
                <td className="px-4 py-2 font-medium">{p.name}<span className="block text-xs text-slate-500">{p.description}</span></td>
                <td className="px-4 py-2">{p.duration_days} j</td>
                <td className="px-4 py-2 font-semibold">{formatFcfa(p.price)}</td>
                <td className="px-4 py-2">{p.sessions_count ?? 'Illimité'}</td>
                <td className="px-4 py-2">{formatFcfa(p.effective_registration_fee)}{p.registration_fee === null && <span className="block text-xs text-slate-500">paramètre global</span>}</td>
                <td className="px-4 py-2">{p.subscriptions_count}</td>
                <td className="px-4 py-2"><Badge className={p.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'}>{p.is_active ? 'Active' : 'Désactivée'}</Badge></td>
                <td className="px-4 py-2 text-right"><button className="btn-ghost text-xs" onClick={() => setEdit(p)}>Modifier</button></td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Modal open={Boolean(edit)} title={edit?.id ? 'Modifier la formule' : 'Nouvelle formule'} onClose={() => setEdit(null)}>
        {edit && (
          <form className="space-y-3" onSubmit={(e) => { e.preventDefault(); setError(null); const fd = new FormData(e.currentTarget); save.mutate({ name: fd.get('name'), duration_days: Number(fd.get('duration_days')), price: Number(fd.get('price')), sessions_count: fd.get('sessions_count') ? Number(fd.get('sessions_count')) : null, registration_fee: fd.get('registration_fee') !== '' ? Number(fd.get('registration_fee')) : null, description: fd.get('description') || null, is_active: fd.get('is_active') === 'on', sort_order: Number(fd.get('sort_order') || 0) }) }}>
            <Field label="Nom *"><input name="name" className="input" required defaultValue={edit.name || ''} /></Field>
            <div className="grid gap-3 md:grid-cols-2">
              <Field label="Durée (jours) *"><input name="duration_days" className="input" type="number" min={1} required defaultValue={edit.duration_days || 30} /></Field>
              <Field label="Prix (FCFA) *"><input name="price" className="input" type="number" min={0} required defaultValue={edit.price ?? ''} /></Field>
              <Field label="Nombre de séances" hint="Vide = illimité"><input name="sessions_count" className="input" type="number" min={1} defaultValue={edit.sessions_count ?? ''} /></Field>
              <Field label="Frais d'inscription spécifiques" hint="Vide = paramètre global"><input name="registration_fee" className="input" type="number" min={0} defaultValue={edit.registration_fee ?? ''} /></Field>
              <Field label="Ordre d'affichage"><input name="sort_order" className="input" type="number" min={0} defaultValue={edit.sort_order ?? 0} /></Field>
            </div>
            <Field label="Description"><input name="description" className="input" defaultValue={edit.description || ''} /></Field>
            <label className="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" defaultChecked={edit.is_active ?? true} /> Formule proposée à la souscription</label>
            <p className="text-xs text-slate-500">Un changement de prix ne modifie pas les abonnements déjà souscrits.</p>
            <ErrorBox message={error} />
            <div className="flex justify-end gap-2"><button type="button" className="btn-secondary" onClick={() => setEdit(null)}>Annuler</button><button className="btn-primary" disabled={save.isPending}>Enregistrer</button></div>
          </form>
        )}
      </Modal>
    </div>
  )
}

function UsersTab() {
  const toast = useToast()
  const qc = useQueryClient()
  const { data, isLoading } = useQuery({ queryKey: ['users'], queryFn: () => users.list() })
  const [edit, setEdit] = useState(null)
  const [error, setError] = useState(null)
  const save = useMutation({ mutationFn: (payload) => (edit?.id ? users.update(edit.id, payload) : users.create(payload)), onSuccess: () => { qc.invalidateQueries({ queryKey: ['users'] }); setEdit(null); toast.success('Compte enregistré') }, onError: (e) => setError(apiError(e).message) })
  const deactivate = useMutation({ mutationFn: (id) => users.deactivate(id), onSuccess: () => { qc.invalidateQueries({ queryKey: ['users'] }); toast.success('Compte désactivé') }, onError: (e) => toast.error(apiError(e).message) })
  if (isLoading) return <Spinner />
  return (
    <div>
      <div className="mb-3 flex justify-end"><button className="btn-primary" onClick={() => setEdit({})}>+ Compte</button></div>
      <div className="card overflow-x-auto p-0">
        <table className="w-full text-sm">
          <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Nom</th><th className="px-4 py-3">Email</th><th className="px-4 py-3">Rôle</th><th className="px-4 py-3">Statut</th><th></th></tr></thead>
          <tbody className="divide-y">
            {(data || []).map((u) => (
              <tr key={u.id}>
                <td className="px-4 py-2 font-medium">{u.name}</td>
                <td className="px-4 py-2">{u.email}</td>
                <td className="px-4 py-2">{u.role_label}</td>
                <td className="px-4 py-2"><Badge className={u.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600'}>{u.is_active ? 'Actif' : 'Désactivé'}</Badge></td>
                <td className="px-4 py-2 text-right whitespace-nowrap"><button className="btn-ghost text-xs" onClick={() => setEdit(u)}>Modifier</button>{u.is_active && <button className="btn-ghost text-xs text-red-600" onClick={() => deactivate.mutate(u.id)}>Désactiver</button>}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <Modal open={Boolean(edit)} title={edit?.id ? 'Modifier le compte' : 'Nouveau compte'} onClose={() => setEdit(null)}>
        {edit && (
          <form className="space-y-3" onSubmit={(e) => { e.preventDefault(); setError(null); const fd = new FormData(e.currentTarget); const payload = { name: fd.get('name'), email: fd.get('email'), role: fd.get('role'), is_active: fd.get('is_active') === 'on' }; if (fd.get('password')) payload.password = fd.get('password'); save.mutate(payload) }}>
            <Field label="Nom *"><input name="name" className="input" required defaultValue={edit.name || ''} /></Field>
            <Field label="Email *"><input name="email" className="input" type="email" required defaultValue={edit.email || ''} /></Field>
            <Field label={edit.id ? 'Nouveau mot de passe (laisser vide pour conserver)' : 'Mot de passe *'}><PasswordInput name="password" autoComplete="new-password" minLength={8} required={!edit.id} /></Field>
            <Field label="Rôle"><select name="role" className="input" defaultValue={edit.role || 'agent'}><option value="agent">Agent d'accueil</option><option value="coach">Coach</option><option value="manager">Gérant</option></select></Field>
            <label className="flex items-center gap-2 text-sm"><input name="is_active" type="checkbox" defaultChecked={edit.is_active ?? true} /> Compte actif</label>
            <ErrorBox message={error} />
            <div className="flex justify-end gap-2"><button type="button" className="btn-secondary" onClick={() => setEdit(null)}>Annuler</button><button className="btn-primary" disabled={save.isPending}>Enregistrer</button></div>
          </form>
        )}
      </Modal>
    </div>
  )
}

function CategoriesTab() {
  const toast = useToast()
  const qc = useQueryClient()
  const { data, isLoading } = useQuery({ queryKey: ['expense-categories', 'all'], queryFn: () => cashbook.categories() })
  const [name, setName] = useState('')
  const create = useMutation({ mutationFn: () => cashbook.createCategory({ name }), onSuccess: () => { qc.invalidateQueries({ queryKey: ['expense-categories'] }); setName(''); toast.success('Catégorie ajoutée') }, onError: (e) => toast.error(apiError(e).message) })
  const toggle = useMutation({ mutationFn: (c) => cashbook.updateCategory(c.id, { name: c.name, is_active: !c.is_active }), onSuccess: () => qc.invalidateQueries({ queryKey: ['expense-categories'] }) })
  if (isLoading) return <Spinner />
  return (
    <div className="card">
      <form className="mb-4 flex gap-2" onSubmit={(e) => { e.preventDefault(); create.mutate() }}><input className="input" placeholder="Nouvelle catégorie de dépense" value={name} onChange={(e) => setName(e.target.value)} required /><button className="btn-primary">Ajouter</button></form>
      <ul className="divide-y text-sm">{(data || []).map((c) => <li key={c.id} className="flex items-center justify-between py-2"><span className={c.is_active ? '' : 'text-slate-400 line-through'}>{c.name} <span className="text-xs text-slate-500">({c.expenses_count} dépense(s))</span></span><button className="btn-ghost text-xs" onClick={() => toggle.mutate(c)}>{c.is_active ? 'Désactiver' : 'Réactiver'}</button></li>)}</ul>
    </div>
  )
}

export default function SettingsPage() {
  const [tab, setTab] = useState('regles')
  return (
    <div>
      <PageHeader title="Paramètres" subtitle="Règles de gestion, formules, comptes, catégories de dépenses" />
      <div className="mb-4 flex gap-1 border-b">
        {[['regles', 'Règles et club'], ['formules', 'Formules et tarifs'], ['comptes', 'Comptes utilisateurs'], ['categories', 'Catégories de dépenses']].map(([k, v]) => <button key={k} className={`px-4 py-2 text-sm font-medium ${tab === k ? 'border-b-2 border-phenix-500 text-phenix-700' : 'text-slate-500'}`} onClick={() => setTab(k)}>{v}</button>)}
      </div>
      {tab === 'regles' && <RulesTab />}
      {tab === 'formules' && <PlansTab />}
      {tab === 'comptes' && <UsersTab />}
      {tab === 'categories' && <CategoriesTab />}
    </div>
  )
}
