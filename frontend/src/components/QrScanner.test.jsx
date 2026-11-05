import { render, screen, waitFor } from '@testing-library/react'
import { StrictMode } from 'react'
import { beforeEach, expect, it, vi } from 'vitest'
import QrScanner from './QrScanner'

const mock = vi.hoisted(() => ({ instances: [], start: null }))

vi.mock('html5-qrcode', () => ({
  Html5QrcodeScannerState: { UNKNOWN: 0, NOT_STARTED: 1, SCANNING: 2, PAUSED: 3 },
  Html5Qrcode: class {
    state = 1
    stop = vi.fn(async () => { this.state = 1 })
    clear = vi.fn()
    getState = () => this.state
    start = async () => { await mock.start(); this.state = 2 }
    constructor() { mock.instances.push(this) }
  },
}))

beforeEach(() => {
  mock.instances.length = 0
  mock.start = async () => {}
})

it('arrête la caméra si le composant est démonté pendant le démarrage', async () => {
  let finishStart
  mock.start = () => new Promise((resolve) => { finishStart = resolve })
  const { unmount } = render(<QrScanner active onScan={() => {}} />)
  await waitFor(() => expect(finishStart).toBeTypeOf('function'))
  unmount()
  expect(mock.instances[0].stop).not.toHaveBeenCalled()
  finishStart()
  await waitFor(() => expect(mock.instances[0].stop).toHaveBeenCalledTimes(1))
})

it('affiche un message si la caméra est inaccessible', async () => {
  mock.start = async () => { throw new Error('NotAllowedError') }
  const { unmount } = render(<QrScanner active onScan={() => {}} />)
  expect(await screen.findByText('Caméra indisponible, utilisez la recherche')).toBeInTheDocument()
  unmount()
  await waitFor(() => expect(mock.instances[0].stop).not.toHaveBeenCalled())
})

it('ne démarre qu\'un scanner en StrictMode et l\'arrête au démontage', async () => {
  const { unmount } = render(<StrictMode><QrScanner active onScan={() => {}} /></StrictMode>)
  await waitFor(() => expect(mock.instances[0]?.state).toBe(2))
  expect(mock.instances).toHaveLength(1)
  unmount()
  await waitFor(() => expect(mock.instances[0].stop).toHaveBeenCalledTimes(1))
})
