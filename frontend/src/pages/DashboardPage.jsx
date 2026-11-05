import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import dayjs from 'dayjs'
import { dashboard } from '../api/resources'
import { ErrorBox, PageHeader, Spinner, StatCard } from '../components/ui'
import { apiError } from '../api/client'
import { formatFcfa, METHOD_LABELS } from '../utils/format'

function AttendanceChart({ data }) {
  const max = Math.max(1, ...data.map((d) => d.count))
  return (
    <div className="flex h-40 items-end gap-1">
      {data.map((d, i) => (
        <div key={d.date} className="group relative flex flex-1 flex-col items-center justify-end">
          <div className="w-full origin-bottom animate-grow-up rounded-t bg-phenix-400 transition-colors group-hover:bg-phenix-600" style={{ height: `${(d.count / max) * 100}%`, minHeight: d.count ? 4 : 1, animationDelay: `${i * 15}ms` }} />
          <span className="pointer-events-none absolute -top-7 hidden animate-fade-in rounded bg-slate-800 px-2 py-0.5 text-xs text-white group-hover:block">
            {dayjs(d.date).format('DD/MM')} : {d.count}
          </span>
        </div>
      ))}
    </div>
  )
}

export default function DashboardPage() {
  const { data, isLoading, error } = useQuery({ queryKey: ['dashboard'], queryFn: () => dashboard.get() })

  if (isLoading) return <Spinner />
  if (error) return <ErrorBox message={apiError(error).message} />

  const { members, attendance, revenue, late_payments: late, programs } = data

  return (
    <div>
      <PageHeader title="Tableau de bord" subtitle={dayjs().format('dddd D MMMM YYYY')} />

      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatCard label="Adhérents actifs" value={members.active} sub={`${members.new_this_month} nouveaux ce mois`} tone="brand" />
        <StatCard label="Présences aujourd'hui" value={attendance.today} sub={`${attendance.this_week} cette semaine`} />
        <StatCard label="Encaissé ce mois" value={revenue.this_month} money sub={`Aujourd'hui : ${formatFcfa(revenue.today)}`} tone="good" />
        <StatCard label="Retards de paiement" value={late.count} sub={formatFcfa(late.amount)} tone={late.count ? 'bad' : 'default'} />
      </div>

      <div className="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <Link to="/adherents?status=pending" className="card card-hover hover:border-amber-400">
          <p className="text-xs font-semibold uppercase text-slate-500">Demandes à valider</p>
          <p className="mt-1 text-2xl font-bold text-amber-600">{members.pending}</p>
        </Link>
        <div className="card">
          <p className="text-xs font-semibold uppercase text-slate-500">Échéances sous 7 jours</p>
          <p className="mt-1 text-2xl font-bold">{members.expiring_within_7_days}</p>
        </div>
        <div className="card">
          <p className="text-xs font-semibold uppercase text-slate-500">Dépenses du mois</p>
          <p className="mt-1 text-2xl font-bold">{formatFcfa(revenue.expenses_this_month)}</p>
          <p className={`text-xs ${revenue.balance_this_month >= 0 ? 'text-emerald-600' : 'text-red-600'}`}>Solde : {formatFcfa(revenue.balance_this_month)}</p>
        </div>
        <Link to="/programmes" className="card card-hover hover:border-phenix-400">
          <p className="text-xs font-semibold uppercase text-slate-500">Programmes à traiter</p>
          <p className="mt-1 text-2xl font-bold">{programs.pending_review}</p>
        </Link>
      </div>

      <div className="mt-6 grid gap-4 lg:grid-cols-3">
        <div className="card lg:col-span-2">
          <h2 className="mb-4 font-semibold">Fréquentation sur 30 jours</h2>
          <AttendanceChart data={attendance.last_30_days} />
          <p className="mt-2 text-xs text-slate-500">{attendance.this_month} présences ce mois</p>
        </div>
        <div className="card">
          <h2 className="mb-4 font-semibold">Encaissements par mode</h2>
          <ul className="space-y-3 text-sm">
            {Object.entries(revenue.by_method).map(([m, v]) => (
              <li key={m} className="flex items-center justify-between">
                <span>{METHOD_LABELS[m]}</span>
                <span className="font-semibold">{formatFcfa(v)}</span>
              </li>
            ))}
          </ul>
          <div className="mt-4 border-t pt-3 text-sm">
            <p className="flex justify-between"><span>Taux d'impayés</span><span className="font-semibold">{Math.round(late.rate * 100)} %</span></p>
            <p className="mt-1 flex justify-between text-slate-500"><span>Inactifs / suspendus</span><span>{members.inactive} / {members.suspended}</span></p>
          </div>
        </div>
      </div>
    </div>
  )
}
