import { useEffect, useRef, useState } from 'react'

// Chargée une seule fois, même si StrictMode exécute l'effet deux fois.
let library = null
const loadLibrary = () => (library ??= import('html5-qrcode'))

/** Arrête la caméra si elle tourne ; ne lève jamais d'erreur (nettoyage de composant). */
async function stopScanner(scanner, States) {
  try {
    const state = scanner.getState()
    if (state === States.SCANNING || state === States.PAUSED) await scanner.stop()
    scanner.clear()
  } catch {
    // déjà arrêté ou élément retiré du DOM
  }
}

/** Lecteur QR (caméra de la tablette). onScan(texte) est appelé à chaque lecture. */
export default function QrScanner({ onScan, active }) {
  const containerRef = useRef(null)
  const startedRef = useRef(false)
  const stoppingRef = useRef(Promise.resolve())
  const onScanRef = useRef(onScan)
  const lastRef = useRef({ text: null, at: 0 })
  const [unavailable, setUnavailable] = useState(false)

  onScanRef.current = onScan

  useEffect(() => {
    if (!active || !containerRef.current || startedRef.current) return
    startedRef.current = true
    setUnavailable(false)
    let cancelled = false

    const handleScan = (text) => {
      const now = Date.now()
      if (lastRef.current.text === text && now - lastRef.current.at < 4000) return
      lastRef.current = { text, at: now }
      onScanRef.current(text)
    }

    const session = Promise.all([loadLibrary(), stoppingRef.current]).then(async ([{ Html5Qrcode, Html5QrcodeScannerState }]) => {
      if (cancelled) return null
      const scanner = new Html5Qrcode('qr-reader')
      const running = { scanner, States: Html5QrcodeScannerState }
      await scanner.start({ facingMode: 'environment' }, { fps: 8, qrbox: 220 }, handleScan, () => {})
      return running
    }).catch(() => {
      if (!cancelled) setUnavailable(true)
      return null
    })

    return () => {
      cancelled = true
      startedRef.current = false
      // Le démarrage est asynchrone : on attend sa fin pour ne pas laisser la caméra allumée.
      stoppingRef.current = session.then((running) => running && stopScanner(running.scanner, running.States))
    }
  }, [active])

  return (
    <>
      {unavailable && <p className="rounded-lg bg-amber-50 p-3 text-center text-sm text-amber-800">Caméra indisponible, utilisez la recherche</p>}
      <div id="qr-reader" ref={containerRef} className={`mx-auto w-full max-w-xs overflow-hidden rounded-lg bg-black ${unavailable ? 'hidden' : ''}`} />
    </>
  )
}
