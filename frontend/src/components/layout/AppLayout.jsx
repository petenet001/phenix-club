import { NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom'
import { useState } from 'react'
import { Banknote, CalendarClock, Dumbbell, LayoutDashboard, LogOut, Menu, ScanLine, Settings, UserX, Users, Wallet } from 'lucide-react'
import { useAuth } from '../../context/AuthContext'

const nav = [
  { to: '/', label: 'Tableau de bord', icon: LayoutDashboard, roles: ['manager'] },
  { to: '/pointage', label: 'Pointage', icon: ScanLine, roles: ['manager', 'agent', 'coach'] },
  { to: '/adherents', label: 'Adhérents', icon: Users, roles: ['manager', 'agent', 'coach'] },
  { to: '/paiements', label: 'Paiements', icon: Wallet, roles: ['manager', 'agent'] },
  { to: '/retards', label: 'Retards', icon: CalendarClock, roles: ['manager', 'agent'] },
  { to: '/absences', label: 'Absences', icon: UserX, roles: ['manager', 'agent', 'coach'] },
  { to: '/caisse', label: 'Caisse', icon: Banknote, roles: ['manager', 'agent'] },
  { to: '/programmes', label: 'Programmes', icon: Dumbbell, roles: ['manager', 'coach'] },
  { to: '/parametres', label: 'Paramètres', icon: Settings, roles: ['manager'] },
]

export default function AppLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [open, setOpen] = useState(false)

  const items = nav.filter((n) => n.roles.includes(user?.role))

  const handleLogout = async () => {
    await logout()
    navigate('/login')
  }

  return (
    <div className="flex min-h-screen">
      <aside className={`fixed inset-y-0 left-0 z-30 w-64 transform bg-slate-900 text-slate-100 transition-transform duration-300 ease-out lg:static lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}>
        <div className="flex h-16 items-center gap-2 border-b border-slate-800 px-5">
          <img src="/logo-phenix.png" alt="Phenix Club" className="h-10 w-10 rounded" />
          <div>
            <p className="font-bold leading-tight">Phenix Club</p>
            <p className="text-xs text-slate-400">Manager</p>
          </div>
        </div>
        <nav className="flex flex-col gap-1 p-3">
          {items.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              onClick={() => setOpen(false)}
              className={({ isActive }) =>
                `group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors duration-150 ${isActive ? 'bg-phenix-500 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white'}`
              }
            >
              <item.icon size={18} strokeWidth={1.75} className="shrink-0" aria-hidden="true" />
              {item.label}
            </NavLink>
          ))}
        </nav>
        <div className="absolute bottom-0 w-full border-t border-slate-800 p-4 text-sm">
          <p className="font-semibold">{user?.name}</p>
          <p className="text-xs text-slate-400">{user?.role_label}</p>
          <button className="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-800 px-3 py-2 text-xs transition-colors hover:bg-slate-700" onClick={handleLogout}><LogOut size={14} />Se déconnecter</button>
        </div>
      </aside>
      {open && <div className="fixed inset-0 z-20 animate-fade-in bg-black/40 lg:hidden" onClick={() => setOpen(false)} />}
      <div className="flex flex-1 flex-col">
        <header className="flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:hidden">
          <button className="btn-ghost" onClick={() => setOpen(true)} aria-label="Menu"><Menu size={20} /></button>
          <span className="font-semibold">Phenix Club Manager</span>
        </header>
        <main className="flex-1 p-4 lg:p-8">
          {/* La clé rejoue l'animation d'entrée à chaque changement de page */}
          <div key={location.pathname} className="animate-fade-up">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  )
}
