import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { recovery } from '../api/resources'
import ReminderModal from '../components/ReminderModal'
import { PageHeader, Spinner, StatCard } from '../components/ui'
import { formatDate, formatDateTime } from '../utils/format'

export default function AbsencesPage() {
  const [days, setDays] = useState('')
  const [remind, setRemind] = useState(null)
  const { data, isLoading } = useQuery({ queryKey: ['absences', days], queryFn: () => recovery.absences(days ? { days } : {}) })

  return (
    <div>
      <PageHeader title="Absences" subtitle="Adhérents actifs sans pointage depuis le seuil d'inactivité" />
      {data && (
        <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
          <StatCard label="Adhérents absents" value={data.summary.count} tone={data.summary.count ? 'warn' : 'good'} />
          <div className="card lg:col-span-3 w-full"><label className="label">Seuil (jours sans présence)</label><input className="input" type="number" min={1} placeholder={data.summary.days} value={days} onChange={(e) => setDays(e.target.value)} /></div>
        </div>
      )}
      {isLoading ? <Spinner /> : (
        <div className="card overflow-x-auto p-0">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th className="px-4 py-3">Adhérent</th><th className="px-4 py-3">Téléphone</th><th className="px-4 py-3">Dernière présence</th><th className="px-4 py-3">Absence</th><th className="px-4 py-3">Dernière relance</th><th></th></tr></thead>
            <tbody className="divide-y">
              {(data?.data || []).map((r) => (
                <tr key={r.member.id}>
                  <td className="px-4 py-2"><Link to={`/adherents/${r.member.id}`} className="font-medium hover:underline">{r.member.full_name}</Link><span className="block text-xs text-slate-500">{r.member.code}</span></td>
                  <td className="px-4 py-2">{r.member.phone}</td>
                  <td className="px-4 py-2">{r.never_attended ? <span className="text-slate-500">Jamais venu</span> : formatDate(r.last_attendance_on)}</td>
                  <td className="px-4 py-2 font-semibold text-amber-700">{r.days_absent} j</td>
                  <td className="px-4 py-2 text-slate-500">{r.last_reminder_at ? formatDateTime(r.last_reminder_at) : '-'}</td>
                  <td className="px-4 py-2 text-right"><button className="btn-secondary text-xs" onClick={() => setRemind(r.member)}>Relancer</button></td>
                </tr>
              ))}
              {data?.data?.length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-emerald-700">Aucun absent sur ce seuil.</td></tr>}
            </tbody>
          </table>
        </div>
      )}
      <ReminderModal member={remind} type="absence" open={Boolean(remind)} onClose={() => setRemind(null)} />
    </div>
  )
}
