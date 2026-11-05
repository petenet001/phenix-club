import { Navigate, useLocation } from 'react-router-dom'
import { useAuth } from '../../context/AuthContext'
import { Spinner } from '../ui'

export function RequireAuth({ children }) {
  const { user, loading } = useAuth()
  const location = useLocation()
  if (loading) return <div className="p-8"><Spinner /></div>
  if (!user) return <Navigate to="/login" state={{ from: location }} replace />
  return children
}

export function RequireRole({ roles, children }) {
  const { user } = useAuth()
  if (!roles.includes(user?.role)) return <Navigate to="/pointage" replace />
  return children
}
