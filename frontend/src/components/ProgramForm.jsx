import { X } from 'lucide-react'
import { useState } from 'react'
import { Field } from './ui'
import { DAYS } from '../utils/format'

const emptyTraining = { goal: '', days: [], sessions: [] }
const emptyNutrition = { goal: '', meals: [{ time: 'Petit-déjeuner', items: [] }, { time: 'Déjeuner', items: [] }, { time: 'Dîner', items: [] }], advice: '' }

/** Formulaire de programme (entraînement ou alimentation). onSubmit(payload, impose) */
export default function ProgramForm({ initial, onSubmit, onCancel, loading, canImpose = true }) {
  const [kind, setKind] = useState(initial?.kind || 'training')
  const [title, setTitle] = useState(initial?.title || '')
  const [startsOn, setStartsOn] = useState(initial?.starts_on || '')
  const [endsOn, setEndsOn] = useState(initial?.ends_on || '')
  const [notes, setNotes] = useState(initial?.notes || '')
  const [content, setContent] = useState(initial?.content || (initial?.kind === 'nutrition' ? emptyNutrition : emptyTraining))

  const switchKind = (k) => {
    setKind(k)
    setContent(k === 'nutrition' ? emptyNutrition : emptyTraining)
  }

  const toggleDay = (d) => {
    const days = content.days.includes(d) ? content.days.filter((x) => x !== d) : [...content.days, d]
    const sessions = days.map((day) => content.sessions.find((s) => s.day === day) || { day, exercises: [] })
    setContent({ ...content, days, sessions })
  }

  const updateExercise = (si, ei, patch) => {
    const sessions = content.sessions.map((s, i) => (i !== si ? s : { ...s, exercises: s.exercises.map((e, j) => (j !== ei ? e : { ...e, ...patch })) }))
    setContent({ ...content, sessions })
  }
  const addExercise = (si) => setContent({ ...content, sessions: content.sessions.map((s, i) => (i !== si ? s : { ...s, exercises: [...s.exercises, { name: '', sets: 3, reps: '12', notes: '' }] })) })
  const removeExercise = (si, ei) => setContent({ ...content, sessions: content.sessions.map((s, i) => (i !== si ? s : { ...s, exercises: s.exercises.filter((_, j) => j !== ei) })) })

  const updateMeal = (mi, patch) => setContent({ ...content, meals: content.meals.map((m, i) => (i !== mi ? m : { ...m, ...patch })) })

  const submit = (impose) => {
    const payload = { kind, title, starts_on: startsOn || null, ends_on: endsOn || null, notes: notes || null, content }
    if (kind === 'training') payload.content = { goal: content.goal, days: content.days, sessions: content.sessions.map((s) => ({ day: s.day, exercises: s.exercises.filter((e) => e.name).map((e) => ({ ...e, sets: Number(e.sets) || null })) })) }
    if (kind === 'nutrition') payload.content = { goal: content.goal, meals: content.meals.map((m) => ({ time: m.time, items: (m.items || []).filter(Boolean) })), advice: content.advice }
    onSubmit(payload, impose)
  }

  return (
    <form className="space-y-4" onSubmit={(e) => { e.preventDefault(); submit(false) }}>
      <div className="grid gap-3 md:grid-cols-2">
        <Field label="Type">
          <select className="input" value={kind} onChange={(e) => switchKind(e.target.value)} disabled={Boolean(initial?.id)}>
            <option value="training">Entraînement</option>
            <option value="nutrition">Alimentation</option>
          </select>
        </Field>
        <Field label="Titre *"><input className="input" required value={title} onChange={(e) => setTitle(e.target.value)} /></Field>
        <Field label="Début"><input className="input" type="date" value={startsOn} onChange={(e) => setStartsOn(e.target.value)} /></Field>
        <Field label="Fin"><input className="input" type="date" value={endsOn} onChange={(e) => setEndsOn(e.target.value)} /></Field>
      </div>
      <Field label="Objectif"><input className="input" value={content.goal || ''} onChange={(e) => setContent({ ...content, goal: e.target.value })} placeholder="Perte de poids, prise de masse, remise en forme..." /></Field>

      {kind === 'training' && (
        <>
          <Field label="Jours d'entraînement">
            <div className="flex flex-wrap gap-2">
              {DAYS.map((d) => (
                <button type="button" key={d} onClick={() => toggleDay(d)} className={`rounded-full px-3 py-1 text-sm capitalize ${content.days.includes(d) ? 'bg-phenix-500 text-white' : 'bg-slate-100 text-slate-700'}`}>{d}</button>
              ))}
            </div>
          </Field>
          {content.sessions.map((s, si) => (
            <div key={s.day} className="rounded-lg border p-3">
              <div className="mb-2 flex items-center justify-between">
                <p className="font-semibold capitalize">{s.day}</p>
                <button type="button" className="btn-ghost text-xs" onClick={() => addExercise(si)}>+ Exercice</button>
              </div>
              {s.exercises.length === 0 && <p className="text-xs text-slate-500">Aucun exercice.</p>}
              <div className="space-y-2">
                {s.exercises.map((e, ei) => (
                  <div key={ei} className="grid grid-cols-12 gap-2">
                    <input className="input col-span-5" placeholder="Exercice" value={e.name} onChange={(ev) => updateExercise(si, ei, { name: ev.target.value })} />
                    <input className="input col-span-2" type="number" min={1} placeholder="Séries" value={e.sets ?? ''} onChange={(ev) => updateExercise(si, ei, { sets: ev.target.value })} />
                    <input className="input col-span-2" placeholder="Reps" value={e.reps || ''} onChange={(ev) => updateExercise(si, ei, { reps: ev.target.value })} />
                    <input className="input col-span-2" placeholder="Note" value={e.notes || ''} onChange={(ev) => updateExercise(si, ei, { notes: ev.target.value })} />
                    <button type="button" className="btn-ghost col-span-1" onClick={() => removeExercise(si, ei)} aria-label="Retirer l'exercice"><X size={16} /></button>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </>
      )}

      {kind === 'nutrition' && (
        <>
          {content.meals.map((m, mi) => (
            <div key={mi} className="grid gap-2 md:grid-cols-3">
              <input className="input" value={m.time} onChange={(e) => updateMeal(mi, { time: e.target.value })} />
              <input className="input md:col-span-2" placeholder="Aliments séparés par des virgules" value={(m.items || []).join(', ')} onChange={(e) => updateMeal(mi, { items: e.target.value.split(',').map((x) => x.trim()) })} />
            </div>
          ))}
          <button type="button" className="btn-ghost text-xs" onClick={() => setContent({ ...content, meals: [...content.meals, { time: 'Collation', items: [] }] })}>+ Repas</button>
          <Field label="Conseils"><textarea className="input" rows={3} value={content.advice || ''} onChange={(e) => setContent({ ...content, advice: e.target.value })} /></Field>
        </>
      )}

      <Field label="Notes pour l'adhérent"><textarea className="input" rows={2} value={notes} onChange={(e) => setNotes(e.target.value)} /></Field>

      <div className="flex justify-end gap-2">
        <button type="button" className="btn-secondary" onClick={onCancel}>Annuler</button>
        {initial?.id ? (
          <button className="btn-primary" disabled={loading}>Enregistrer</button>
        ) : (
          <>
            <button className="btn-secondary" disabled={loading}>Proposer (à valider)</button>
            {canImpose && <button type="button" className="btn-primary" disabled={loading} onClick={() => submit(true)}>Imposer maintenant</button>}
          </>
        )}
      </div>
    </form>
  )
}
