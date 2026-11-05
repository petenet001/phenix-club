import { Component } from 'react'

/** Écran de repli en cas d'erreur : autour d'enfants, ou seul comme errorElement du routeur. */
export default class ErrorBoundary extends Component {
  state = { hasError: false }

  static getDerivedStateFromError() {
    return { hasError: true }
  }

  render() {
    if (this.props.children && !this.state.hasError) return this.props.children

    return (
      <div className="flex min-h-screen items-center justify-center bg-slate-100 p-4">
        <div className="w-full max-w-sm rounded-2xl border border-slate-200 bg-white p-8 text-center">
          <h1 className="text-lg font-bold">Une erreur est survenue</h1>
          <p className="mt-2 text-sm text-slate-500">La page n'a pas pu s'afficher. Rechargez-la pour continuer.</p>
          <button className="btn-primary mt-6" onClick={() => window.location.reload()}>Recharger</button>
        </div>
      </div>
    )
  }
}
