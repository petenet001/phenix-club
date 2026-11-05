import axios from 'axios'

const TOKEN_KEY = 'phenix_token'

export const getToken = () => localStorage.getItem(TOKEN_KEY)
export const setToken = (token) => (token ? localStorage.setItem(TOKEN_KEY, token) : localStorage.removeItem(TOKEN_KEY))

const client = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  headers: { Accept: 'application/json' },
})

client.interceptors.request.use((config) => {
  const token = getToken()
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

client.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      setToken(null)
      if (window.location.pathname !== '/login') window.location.assign('/login')
    }
    return Promise.reject(error)
  }
)

/**
 * Extrait un message lisible d'une erreur API :
 * - erreur métier { error, message }
 * - erreur de validation { message, errors: { champ: [..] } }
 */
export function apiError(error) {
  const data = error?.response?.data
  if (!data) return { message: 'Impossible de joindre le serveur.', code: 'network', errors: {} }
  const errors = data.errors || {}
  const first = Object.values(errors)[0]?.[0]
  return { message: first || data.message || 'Erreur inattendue.', code: data.error || null, errors, details: data.details || {} }
}

export default client
