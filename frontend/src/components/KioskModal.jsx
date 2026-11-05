import { useCallback, useEffect, useRef, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import QRCode from 'qrcode'
import { Maximize2, Minimize2, X } from 'lucide-react'
import { attendances, kiosk } from '../api/resources'
import { formatTime } from '../utils/format'

/** Anneau de compte à rebours autour du QR code. */
function Countdown({ deadline, period }) {
  const [now, setNow] = useState(Date.now())
  useEffect(() => {
    const id = setInterval(() => setNow(Date.now()), 250)
    return () => clearInterval(id)
  }, [])
  const left = Math.max(0, Math.ceil((deadline - now) / 1000))
  const ratio = Math.min(1, Math.max(0, (deadline - now) / (period * 1000)))
  const r = 22
  const c = 2 * Math.PI * r
  return (
    <div className="flex items-center gap-3 text-slate-300">
      <svg viewBox="0 0 50 50" className="h-8 w-8 -rotate-90" aria-hidden="true">
        <circle cx="25" cy="25" r={r} fill="none" stroke="currentColor" strokeOpacity=".2" strokeWidth="4" />
        <circle cx="25" cy="25" r={r} fill="none" stroke="#D5772F" strokeWidth="4" strokeLinecap="round" strokeDasharray={c} strokeDashoffset={c * (1 - ratio)} style={{ transition: 'stroke-dashoffset .25s linear' }} />
      </svg>
      <span className="text-sm">Nouveau code dans <strong className="text-white">{left} s</strong></span>
    </div>
  )
}

/**
 * Borne de pointage, ouverte depuis la page Pointage : écran à laisser affiché à l'accueil (idéalement
 * en plein écran). Les adhérents scannent le QR code avec l'application Phenix Club ; il change toutes
 * les 30 secondes, une photo ne sert donc pas longtemps.
 */
export default function KioskModal({ open, onClose }) {
  if (!open) return null
  return <Kiosk onClose={onClose} />
}

function Kiosk({ onClose }) {
  const [qr, setQr] = useState(null)
  const [deadline, setDeadline] = useState(0)
  const [welcome, setWelcome] = useState(null)
  const lastSeen = useRef(null)

  const token = useQuery({
    queryKey: ['kiosk-token'],
    queryFn: () => kiosk.token(),
    // Recharge juste après l'expiration annoncée par le serveur
    refetchInterval: (query) => ((query.state.data?.expires_in ?? 30) * 1000) + 300,
    refetchIntervalInBackground: true,
    staleTime: 0,
    gcTime: 0,
  })

  const today = useQuery({
    queryKey: ['attendances', 'today', 'kiosk'],
    queryFn: () => attendances.list({ per_page: 8 }),
    refetchInterval: 4000,
    refetchIntervalInBackground: true,
  })

  useEffect(() => {
    if (!token.data) return
    setDeadline(Date.now() + token.data.expires_in * 1000)
    QRCode.toDataURL(token.data.payload, { width: 640, margin: 1, errorCorrectionLevel: 'M', color: { dark: '#0f172a', light: '#ffffff' } })
      .then(setQr)
      .catch(() => setQr(null))
  }, [token.data])

  // Message de bienvenue quand un nouveau pointage arrive
  const latest = today.data?.data?.[0]
  useEffect(() => {
    if (!latest) return
    if (lastSeen.current !== null && latest.id !== lastSeen.current) {
      setWelcome(latest)
      const id = setTimeout(() => setWelcome(null), 4000)
      lastSeen.current = latest.id
      return () => clearTimeout(id)
    }
    lastSeen.current = latest.id
  }, [latest])

  const panel = useRef(null)
  const [fullscreen, setFullscreen] = useState(false)
  const close = useCallback(() => {
    if (document.fullscreenElement) document.exitFullscreen?.().catch(() => {})
    onClose()
  }, [onClose])

  useEffect(() => {
    const onChange = () => setFullscreen(document.fullscreenElement === panel.current)
    // Échap quitte d'abord le plein écran (géré par le navigateur), puis ferme la borne
    const onKey = (e) => { if (e.key === 'Escape' && !document.fullscreenElement) close() }
    document.addEventListener('fullscreenchange', onChange)
    window.addEventListener('keydown', onKey)
    return () => {
      document.removeEventListener('fullscreenchange', onChange)
      window.removeEventListener('keydown', onKey)
    }
  }, [close])

  // Le plein écran porte sur la fenêtre de la borne seule, pas sur toute l'application
  const toggleFullscreen = () => {
    if (document.fullscreenElement) document.exitFullscreen?.().catch(() => {})
    else panel.current?.requestFullscreen?.().catch(() => {})
  }

  const list = (today.data?.data || []).slice(0, fullscreen ? 8 : 5)
  const qrSize = fullscreen ? 'h-[min(60vh,440px)] w-[min(60vh,440px)]' : 'h-56 w-56'

  return (
    <div className="fixed inset-0 z-50 flex animate-fade-in items-center justify-center bg-slate-950/60 p-4" onClick={close}>
      <div
        ref={panel}
        role="dialog"
        aria-modal="true"
        aria-label="Borne de pointage"
        onClick={(e) => e.stopPropagation()}
        className={`relative flex animate-scale-in flex-col overflow-y-auto bg-slate-950 text-white ${fullscreen ? 'h-full w-full' : 'max-h-[92vh] w-full max-w-3xl rounded-2xl border border-slate-800'}`}
      >
        <header className={`flex items-center justify-between border-b border-slate-800 ${fullscreen ? 'px-8 py-5' : 'px-5 py-3'}`}>
          <div className="flex items-center gap-3">
            <img src="/logo-phenix.png" alt="" className={`${fullscreen ? 'h-12 w-12' : 'h-9 w-9'} rounded-lg`} />
            <div>
              <p className="font-semibold">Phenix Club</p>
              <p className="text-xs text-slate-400">Borne de pointage</p>
            </div>
          </div>
          <div className="flex gap-1">
            <button onClick={toggleFullscreen} className="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-300 transition-colors hover:bg-slate-800 hover:text-white">
              {fullscreen ? <Minimize2 size={16} /> : <Maximize2 size={16} />}
              {fullscreen ? 'Réduire' : 'Plein écran'}
            </button>
            <button onClick={close} aria-label="Fermer la borne" title="Fermer la borne" className="rounded-lg p-2 text-slate-300 transition-colors hover:bg-slate-800 hover:text-white">
              <X size={18} />
            </button>
          </div>
        </header>

        <main className={`grid flex-1 items-center gap-6 ${fullscreen ? 'p-8 lg:grid-cols-[1fr_380px] lg:gap-10' : 'p-5 md:grid-cols-[1fr_260px]'}`}>
          <section className="flex flex-col items-center text-center">
            <h1 className={`font-bold ${fullscreen ? 'text-3xl lg:text-4xl' : 'text-xl'}`}>Scannez pour pointer</h1>
            <p className={`mt-1 text-slate-400 ${fullscreen ? '' : 'text-sm'}`}>Ouvrez l'application <strong className="font-semibold text-slate-200">Phenix Club</strong>, touchez « Pointer » et visez ce code.</p>

            <div className={`rounded-2xl bg-white ${fullscreen ? 'mt-8 p-5' : 'mt-4 p-3'}`}>
              {qr ? (
                <img key={qr} src={qr} alt="QR code de pointage" className={`${qrSize} animate-fade-in`} />
              ) : (
                <div className={`flex ${qrSize} items-center justify-center text-sm text-slate-500`}>
                  {token.isError ? 'Connexion au serveur perdue, nouvel essai…' : 'Chargement…'}
                </div>
              )}
            </div>

            <div className={fullscreen ? 'mt-6' : 'mt-3'}>{token.data && <Countdown deadline={deadline} period={token.data.period} />}</div>
            <p className="mt-2 text-xs text-slate-500">Pas d'application ? Présentez-vous à l'accueil.</p>
          </section>

          <aside className="rounded-xl border border-slate-800 bg-slate-900 p-4">
            <h2 className="mb-3 flex items-center justify-between text-sm font-semibold">
              Derniers pointages
              <span className="flex items-center gap-2 text-xs font-normal text-slate-400"><span className="h-2 w-2 animate-pulse rounded-full bg-emerald-400" />en direct</span>
            </h2>
            <ul className="space-y-1.5">
              {list.map((a) => (
                <li key={a.id} className="flex animate-slide-in-right items-center gap-3 rounded-lg bg-slate-800/60 px-3 py-2">
                  {a.member.photo_url
                    ? <img src={a.member.photo_url} alt="" className="h-8 w-8 rounded-full object-cover" />
                    : <span className="flex h-8 w-8 items-center justify-center rounded-full bg-slate-700 text-sm font-semibold text-slate-200">{a.member.full_name[0]}</span>}
                  <div className="min-w-0 flex-1">
                    <p className="truncate text-sm font-medium">{a.member.full_name}</p>
                    <p className="text-xs text-slate-400">{formatTime(a.checked_in_at)} · {a.method === 'app' ? 'application' : a.method === 'qr' ? 'carte' : 'accueil'}</p>
                  </div>
                </li>
              ))}
              {list.length === 0 && <li className="py-6 text-center text-sm text-slate-500">Aucun pointage aujourd'hui.</li>}
            </ul>
          </aside>
        </main>

        {welcome && (
          <div className="pointer-events-none absolute inset-x-0 bottom-6 flex justify-center">
            <div className="animate-pop rounded-xl bg-emerald-600 px-6 py-3 text-center font-semibold">
              Bienvenue {welcome.member.full_name.split(' ')[0]}, bonne séance !
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
