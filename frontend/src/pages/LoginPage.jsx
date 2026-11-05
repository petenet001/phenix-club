import { useState } from 'react'
import { useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { apiError } from '../api/client'
import { ErrorBox, Field, PasswordInput } from '../components/ui'

export default function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const [form, setForm] = useState({ email: '', password: '' })
  const [error, setError] = useState(null)
  const [loading, setLoading] = useState(false)

  const submit = async (e) => {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const user = await login(form)
      const target = location.state?.from?.pathname || (user.role === 'manager' ? '/' : '/pointage')
      navigate(target, { replace: true })
    } catch (err) {
      setError(apiError(err).message)
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="flex min-h-screen items-center justify-center bg-slate-900 p-4">
      <form onSubmit={submit} className="w-full max-w-sm animate-scale-in rounded-2xl border border-slate-800 bg-white p-8">
        <div className="mb-6 text-center">
          <img src="/logo-phenix.png" alt="Phenix Club" className="mx-auto h-20 w-20 rounded-lg" />
          <h1 className="mt-2 text-xl font-bold">Phenix Club Manager</h1>
          <p className="text-sm text-slate-500">Connectez-vous pour continuer</p>
        </div>
        <div className="space-y-4">
          <Field label="Email">
            <input className="input" type="email" required autoFocus value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
          </Field>
          <Field label="Mot de passe">
            <PasswordInput required autoComplete="current-password" value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
          </Field>
          <ErrorBox message={error} />
          <button className="btn-primary w-full" disabled={loading}>{loading ? 'Connexion...' : 'Se connecter'}</button>
        </div>
      </form>
    </div>
  )
}
