import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import { auth } from '../api/resources'
import { getToken, setToken } from '../api/client'

const AuthContext = createContext(null)

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null)
  const [loading, setLoading] = useState(Boolean(getToken()))

  useEffect(() => {
    if (!getToken()) return
    auth
      .me()
      .then((data) => setUser(data.data || data))
      .catch(() => setToken(null))
      .finally(() => setLoading(false))
  }, [])

  const login = useCallback(async (credentials) => {
    const data = await auth.login(credentials)
    setToken(data.token)
    setUser(data.user)
    return data.user
  }, [])

  const logout = useCallback(async () => {
    try {
      await auth.logout()
    } catch {
      // token déjà invalide
    }
    setToken(null)
    setUser(null)
  }, [])

  const value = useMemo(
    () => ({
      user,
      loading,
      login,
      logout,
      isManager: user?.role === 'manager',
      isCoach: user?.role === 'coach',
      canReviewPrograms: user?.role === 'manager' || user?.role === 'coach',
    }),
    [user, loading, login, logout]
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth() {
  const ctx = useContext(AuthContext)
  if (!ctx) throw new Error('useAuth doit être utilisé dans AuthProvider')
  return ctx
}
