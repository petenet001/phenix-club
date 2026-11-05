import { useEffect, useState } from 'react'
import { Eye, EyeOff, X } from 'lucide-react'
import { formatFcfa } from '../../utils/format'

export function Spinner({ label = 'Chargement...' }) {
  return (
    <div className="flex items-center gap-3 py-8 text-sm text-slate-500">
      <span className="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-phenix-500" />
      {label}
    </div>
  )
}

export function ErrorBox({ message }) {
  if (!message) return null
  return <div key={message} className="animate-shake rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{message}</div>
}

export function EmptyState({ title, hint, action }) {
  return (
    <div className="card animate-fade-up text-center">
      <p className="font-semibold text-slate-700">{title}</p>
      {hint && <p className="mt-1 text-sm text-slate-500">{hint}</p>}
      {action && <div className="mt-4">{action}</div>}
    </div>
  )
}

/** Champ mot de passe avec bouton pour l'afficher en clair ou le masquer. */
export function PasswordInput({ className = '', ...props }) {
  const [visible, setVisible] = useState(false)
  return (
    <div className="relative">
      <input {...props} type={visible ? 'text' : 'password'} className={`input pr-11 ${className}`} />
      <button
        type="button"
        onClick={() => setVisible((v) => !v)}
        className="absolute inset-y-0 right-0 flex w-10 items-center justify-center rounded-r-lg text-slate-400 transition-colors hover:text-slate-700 focus:text-phenix-600 focus:outline-none"
        aria-label={visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
        title={visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
        aria-pressed={visible}
      >
        {visible ? <EyeOff size={18} aria-hidden="true" /> : <Eye size={18} aria-hidden="true" />}
      </button>
    </div>
  )
}

export function Badge({ children, className = '' }) {
  return <span className={`badge ${className}`}>{children}</span>
}

export function Field({ label, error, children, hint }) {
  return (
    <div>
      {label && <label className="label">{label}</label>}
      {children}
      {hint && !error && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
      {error && <p className="mt-1 text-xs text-red-600">{Array.isArray(error) ? error[0] : error}</p>}
    </div>
  )
}

export function Modal({ open, title, onClose, children, wide = false }) {
  useEffect(() => {
    if (!open) return
    const onKey = (e) => e.key === 'Escape' && onClose()
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null
  return (
    <div className="fixed inset-0 z-40 flex animate-fade-in items-center justify-center bg-slate-900/50 p-4 backdrop-blur-[2px]" onClick={onClose}>
      <div className={`max-h-[90vh] w-full ${wide ? 'max-w-3xl' : 'max-w-lg'} animate-scale-in overflow-y-auto rounded-xl border border-slate-200 bg-white p-6 shadow-lg`} onClick={(e) => e.stopPropagation()}>
        <div className="mb-4 flex items-center justify-between">
          <h3 className="text-lg font-semibold">{title}</h3>
          <button type="button" className="btn-ghost px-2" onClick={onClose} aria-label="Fermer"><X size={18} /></button>
        </div>
        {children}
      </div>
    </div>
  )
}

export function StatCard({ label, value, sub, tone = 'default', money = false }) {
  const tones = {
    default: 'border-slate-200',
    good: 'border-emerald-300 bg-emerald-50',
    warn: 'border-amber-300 bg-amber-50',
    bad: 'border-red-300 bg-red-50',
    brand: 'border-phenix-300 bg-phenix-50',
  }
  return (
    <div className={`card card-hover animate-fade-up ${tones[tone]}`}>
      <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
      <p className="mt-1 text-2xl font-bold">{money ? formatFcfa(value) : value}</p>
      {sub && <p className="mt-1 text-xs text-slate-500">{sub}</p>}
    </div>
  )
}

export function Pagination({ meta, onPage }) {
  if (!meta || meta.last_page <= 1) return null
  return (
    <div className="mt-4 flex items-center justify-between text-sm text-slate-600">
      <span>{meta.total} résultat(s)</span>
      <div className="flex gap-2">
        <button className="btn-secondary" disabled={meta.current_page <= 1} onClick={() => onPage(meta.current_page - 1)}>Précédent</button>
        <span className="self-center">Page {meta.current_page} / {meta.last_page}</span>
        <button className="btn-secondary" disabled={meta.current_page >= meta.last_page} onClick={() => onPage(meta.current_page + 1)}>Suivant</button>
      </div>
    </div>
  )
}

export function PageHeader({ title, subtitle, actions }) {
  return (
    <div className="mb-6 flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 className="text-2xl font-bold text-slate-800">{title}</h1>
        {subtitle && <p className="text-sm text-slate-500">{subtitle}</p>}
      </div>
      {actions && <div className="flex gap-2">{actions}</div>}
    </div>
  )
}

export function ConfirmReason({ open, title, onClose, onConfirm, loading, label = 'Motif' }) {
  return (
    <Modal open={open} title={title} onClose={onClose}>
      <form
        onSubmit={(e) => {
          e.preventDefault()
          onConfirm(new FormData(e.currentTarget).get('reason'))
        }}
      >
        <Field label={label}>
          <input name="reason" className="input" required minLength={3} autoFocus />
        </Field>
        <div className="mt-4 flex justify-end gap-2">
          <button type="button" className="btn-secondary" onClick={onClose}>Annuler</button>
          <button type="submit" className="btn-danger" disabled={loading}>Confirmer</button>
        </div>
      </form>
    </Modal>
  )
}
