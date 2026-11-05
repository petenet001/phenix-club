import { UserPlus } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { Link, useSearchParams } from 'react-router-dom'
import { members, plans } from '../api/resources'
import { Badge, PageHeader, Pagination, Spinner } from '../components/ui'
import { formatDate, STATUS_COLORS, STATUS_LABELS } from '../utils/format'

export default function MembersPage() {
  const [params, setParams] = useSearchParams()
  const filters = {
    search: params.get('search') || '',
    status: params.get('status') || '',
    plan_id: params.get('plan_id') || '',
    late: params.get('late') || '',
    page: params.get('page') || 1,
  }

  const set = (key, value) => {
    const next = new URLSearchParams(params)
    value ? next.set(key, value) : next.delete(key)
    if (key !== 'page') next.delete('page')
    setParams(next)
  }

  const { data, isLoading } = useQuery({ queryKey: ['members', filters], queryFn: () => members.list({ ...filters, per_page: 20 }), keepPreviousData: true })
  const planList = useQuery({ queryKey: ['plans'], queryFn: () => plans.list() })

  return (
    <div>
      <PageHeader title="Adhérents" subtitle={data?.meta ? `${data.meta.total} inscrit(s)` : ''} actions={<Link to="/adherents/nouveau" className="btn-primary"><UserPlus size={16} />Nouvel adhérent</Link>} />

      <div className="card mb-4 grid gap-3 md:grid-cols-4">
        <input className="input md:col-span-2" placeholder="Rechercher : nom, téléphone, code" value={filters.search} onChange={(e) => set('search', e.target.value)} />
        <select className="input" value={filters.status} onChange={(e) => set('status', e.target.value)}>
          <option value="">Tous les statuts</option>
          {Object.entries(STATUS_LABELS).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
        </select>
        <select className="input" value={filters.plan_id} onChange={(e) => set('plan_id', e.target.value)}>
          <option value="">Toutes les formules</option>
          {(planList.data || []).map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
        </select>
        <label className="flex items-center gap-2 text-sm md:col-span-4">
          <input type="checkbox" checked={filters.late === '1'} onChange={(e) => set('late', e.target.checked ? '1' : '')} /> Uniquement les retards de paiement
        </label>
      </div>

      {isLoading ? (
        <Spinner />
      ) : (
        <div className="card overflow-x-auto p-0">
          <table className="w-full text-sm">
            <thead className="bg-slate-50 text-left text-xs uppercase text-slate-500">
              <tr>
                <th className="px-4 py-3">Adhérent</th>
                <th className="px-4 py-3">Téléphone</th>
                <th className="px-4 py-3">Statut</th>
                <th className="px-4 py-3">Abonnement</th>
                <th className="px-4 py-3">Dernière présence</th>
                <th className="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody className="divide-y">
              {(data?.data || []).map((m) => (
                <tr key={m.id} className="hover:bg-slate-50">
                  <td className="px-4 py-3">
                    <Link to={`/adherents/${m.id}`} className="font-medium hover:underline">{m.full_name}</Link>
                    <span className="block text-xs text-slate-500">{m.code}{m.registration_source === 'online' && ' - en ligne'}</span>
                  </td>
                  <td className="px-4 py-3">{m.phone}</td>
                  <td className="px-4 py-3">
                    <Badge className={STATUS_COLORS[m.status]}>{STATUS_LABELS[m.status]}</Badge>
                    {m.is_late && <Badge className="ml-1 bg-red-100 text-red-800">retard {m.days_late} j</Badge>}
                  </td>
                  <td className="px-4 py-3">
                    {m.current_subscription ? (
                      <span>{m.current_subscription.plan?.name}<span className="block text-xs text-slate-500">jusqu'au {formatDate(m.current_subscription.end_date)}{m.current_subscription.balance_due > 0 && ' - solde dû'}</span></span>
                    ) : <span className="text-slate-400">Aucun</span>}
                  </td>
                  <td className="px-4 py-3 text-slate-600">{m.last_attendance_at ? formatDate(m.last_attendance_at) : '-'}</td>
                  <td className="px-4 py-3 text-right"><Link to={`/adherents/${m.id}`} className="btn-ghost">Ouvrir</Link></td>
                </tr>
              ))}
              {data?.data?.length === 0 && <tr><td colSpan={6} className="px-4 py-8 text-center text-slate-500">Aucun adhérent ne correspond.</td></tr>}
            </tbody>
          </table>
        </div>
      )}
      <Pagination meta={data?.meta} onPage={(p) => set('page', p)} />
    </div>
  )
}
