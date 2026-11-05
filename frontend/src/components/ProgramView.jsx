import { formatDate } from '../utils/format'

const STATUS = {
  proposed: 'bg-amber-100 text-amber-800',
  approved: 'bg-emerald-100 text-emerald-800',
  imposed: 'bg-emerald-100 text-emerald-800',
  declined: 'bg-red-100 text-red-800',
  archived: 'bg-slate-200 text-slate-600',
}

export default function ProgramView({ program, actions }) {
  const c = program.content || {}
  return (
    <div className="card">
      <div className="flex flex-wrap items-start justify-between gap-2">
        <div>
          <p className="font-semibold">{program.title} <span className={`badge ml-2 ${STATUS[program.status]}`}>{program.status_label}</span></p>
          <p className="text-xs text-slate-500">
            {program.kind_label}{c.goal && ` - ${c.goal}`}{program.starts_on && ` - du ${formatDate(program.starts_on)}`}{program.ends_on && ` au ${formatDate(program.ends_on)}`}
            {program.author && ` - par ${program.author.name}`}{program.proposed_by_member && ' - proposé par l\'adhérent'}
          </p>
        </div>
        {actions}
      </div>
      {program.kind === 'training' && (
        <div className="mt-3 grid gap-3 md:grid-cols-2">
          {(c.sessions || []).map((s) => (
            <div key={s.day} className="rounded-lg bg-slate-50 p-3 text-sm">
              <p className="font-semibold capitalize">{s.day}</p>
              <ul className="mt-1 space-y-0.5 text-slate-700">
                {(s.exercises || []).map((e, i) => <li key={i}>{e.name}{e.sets && ` - ${e.sets} x ${e.reps || ''}`}{e.notes && <span className="text-slate-500"> ({e.notes})</span>}</li>)}
                {(s.exercises || []).length === 0 && <li className="text-slate-400">Séance libre</li>}
              </ul>
            </div>
          ))}
          {(!c.sessions || c.sessions.length === 0) && c.days?.length > 0 && <p className="text-sm capitalize">Jours : {c.days.join(', ')}</p>}
        </div>
      )}
      {program.kind === 'nutrition' && (
        <div className="mt-3 space-y-2 text-sm">
          {(c.meals || []).map((m, i) => <p key={i}><span className="font-semibold">{m.time} :</span> {(m.items || []).join(', ') || '-'}</p>)}
          {c.advice && <p className="rounded-lg bg-slate-50 p-2 text-slate-700">{c.advice}</p>}
        </div>
      )}
      {program.notes && <p className="mt-2 text-sm italic text-slate-600">{program.notes}</p>}
      {program.review_note && <p className="mt-2 text-xs text-slate-500">Avis {program.reviewed_by?.name ? `de ${program.reviewed_by.name}` : ''} : {program.review_note}</p>}
    </div>
  )
}
