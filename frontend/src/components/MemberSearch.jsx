import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { members } from '../api/resources'
import { STATUS_COLORS, STATUS_LABELS } from '../utils/format'

/**
 * Champ de recherche d'adhérent avec résultats en direct (nom, téléphone, code).
 * onSelect(member) est appelé au clic sur un résultat.
 */
export default function MemberSearch({ onSelect, autoFocus = false, placeholder = 'Nom, téléphone ou code adhérent...' }) {
  const [term, setTerm] = useState('')
  const [debounced, setDebounced] = useState('')

  useEffect(() => {
    const t = setTimeout(() => setDebounced(term.trim()), 250)
    return () => clearTimeout(t)
  }, [term])

  const { data, isFetching } = useQuery({
    queryKey: ['members', 'search', debounced],
    queryFn: () => members.list({ search: debounced, per_page: 8 }),
    enabled: debounced.length >= 2,
  })

  const results = debounced.length >= 2 ? data?.data || [] : []

  return (
    <div className="relative">
      <input className="input text-base" value={term} autoFocus={autoFocus} placeholder={placeholder} onChange={(e) => setTerm(e.target.value)} />
      {debounced.length >= 2 && (
        <div className="absolute z-10 mt-1 w-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-md">
          {isFetching && <p className="px-4 py-2 text-xs text-slate-500">Recherche...</p>}
          {!isFetching && results.length === 0 && <p className="px-4 py-3 text-sm text-slate-500">Aucun adhérent trouvé.</p>}
          {results.map((m) => (
            <button
              key={m.id}
              type="button"
              className="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50"
              onClick={() => {
                onSelect(m)
                setTerm('')
              }}
            >
              {m.photo_url ? <img src={m.photo_url} alt="" className="h-9 w-9 rounded-full object-cover" /> : <span className="flex h-9 w-9 items-center justify-center rounded-full bg-slate-200 text-sm font-bold">{m.first_name[0]}{m.last_name[0]}</span>}
              <span className="flex-1">
                <span className="block font-medium">{m.full_name}</span>
                <span className="block text-xs text-slate-500">{m.code} - {m.phone}</span>
              </span>
              <span className={`badge ${STATUS_COLORS[m.status]}`}>{STATUS_LABELS[m.status]}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  )
}
