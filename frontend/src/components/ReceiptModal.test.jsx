import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { fireEvent, render, screen } from '@testing-library/react'
import { beforeEach, expect, it, vi } from 'vitest'
import ReceiptModal from './ReceiptModal'
import { payments } from '../api/resources'

vi.mock('../api/resources', () => ({ payments: { receipt: vi.fn(), receiptPdf: vi.fn() } }))

const receipt = (overrides = {}) => ({
  id: 1,
  receipt_number: 'RC-2026-000001',
  paid_at: '2026-11-01T10:00:00+01:00',
  member: { full_name: 'Alain Mbemba', code: 'PHX-000001', phone: '+242061000001' },
  type_label: 'Abonnement',
  plan: 'Mensuel',
  period_start: '2026-11-01',
  period_end: '2026-11-30',
  amount: 15000,
  amount_in_words: 'quinze mille',
  method_label: 'Espèces',
  reference: null,
  balance_due: 0,
  received_by: 'Accueil',
  notes: null,
  cancelled_at: null,
  club: { name: 'Phenix Club', address: 'Brazzaville', phone: '', logo_url: '/logo.png' },
  ...overrides,
})

const show = (props) => render(
  <QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
    <ReceiptModal open onClose={() => {}} {...props} />
  </QueryClientProvider>
)

beforeEach(() => {
  vi.clearAllMocks()
  payments.receipt.mockImplementation(async (id) => receipt({ id, receipt_number: `RC-2026-00000${id}` }))
})

it('affiche le numéro de reçu et le bouton Imprimer', async () => {
  show({ paymentId: 1 })

  expect(await screen.findByText('REÇU N° RC-2026-000001')).toBeInTheDocument()
  expect(screen.getByRole('button', { name: 'Imprimer' })).toBeEnabled()
  expect(screen.queryByText('ANNULÉ')).not.toBeInTheDocument()
})

it('porte la mention ANNULÉ sur un reçu annulé', async () => {
  payments.receipt.mockResolvedValue(receipt({ cancelled_at: '2026-11-01T11:00:00+01:00' }))
  show({ paymentId: 1 })

  expect(await screen.findByText('ANNULÉ')).toBeInTheDocument()
})

it('propose un sélecteur quand un encaissement produit plusieurs reçus', async () => {
  show({ paymentId: 1, paymentIds: [1, 2] })
  await screen.findByText('REÇU N° RC-2026-000001')

  fireEvent.click(screen.getByRole('button', { name: 'Reçu 2 / 2' }))

  expect(await screen.findByText('REÇU N° RC-2026-000002')).toBeInTheDocument()
})

it('renvoie vers Imprimer si le PDF est indisponible', async () => {
  payments.receiptPdf.mockRejectedValue({ response: { data: { error: 'pdf_unavailable', message: 'PDF indisponible.' } } })
  show({ paymentId: 1 })
  await screen.findByText('REÇU N° RC-2026-000001')

  fireEvent.click(screen.getByRole('button', { name: 'Télécharger PDF' }))

  expect(await screen.findByText('PDF indisponible, utilisez Imprimer')).toBeInTheDocument()
})
