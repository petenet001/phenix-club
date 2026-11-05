import { Download, Printer } from 'lucide-react'
import { useEffect, useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { payments } from '../api/resources'
import { apiError } from '../api/client'
import { ErrorBox, Modal, Spinner } from './ui'
import { formatDate, formatDateTime, formatFcfa, saveBlob } from '../utils/format'

function Row({ label, children }) {
  return (
    <div className="flex gap-3 py-1">
      <dt className="w-2/5 shrink-0 text-slate-500">{label}</dt>
      <dd className="min-w-0">{children}</dd>
    </div>
  )
}

function Receipt({ receipt }) {
  const { club, member } = receipt
  return (
    <div className="receipt-print relative mx-auto max-w-[148mm] overflow-hidden rounded-lg border border-slate-200 bg-white p-6 text-sm text-slate-900">
      {receipt.cancelled_at && (
        <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
          <span className="-rotate-12 text-6xl font-bold text-red-600 opacity-30">ANNULÉ</span>
        </div>
      )}
      <header className="border-b-2 border-slate-900 pb-3 text-center">
        <img src={club.logo_url} alt={club.name} className="mx-auto mb-2 h-16 w-16" />
        <h2 className="text-lg font-bold">{club.name}</h2>
        <p className="text-xs">{club.address}{club.phone && ` - Tél. ${club.phone}`}</p>
      </header>
      <p className="my-3 text-center font-bold">REÇU N° {receipt.receipt_number}</p>
      <dl>
        <Row label="Date">{formatDateTime(receipt.paid_at)}</Row>
        <Row label="Reçu de"><strong>{member.full_name}</strong> ({member.code})</Row>
        <Row label="Téléphone">{member.phone}</Row>
        <Row label="Objet">{receipt.type_label}{receipt.plan && ` - ${receipt.plan}`}</Row>
        {receipt.period_start && <Row label="Période couverte">du {formatDate(receipt.period_start)} au {formatDate(receipt.period_end)}</Row>}
        <Row label="Mode de paiement">{receipt.method_label}{receipt.reference && ` - réf. ${receipt.reference}`}</Row>
        {receipt.balance_due > 0 && <Row label="Reste à payer">{formatFcfa(receipt.balance_due)}</Row>}
      </dl>
      <p className="mt-3 border border-slate-900 p-2 text-center text-xl font-bold">{formatFcfa(receipt.amount)}</p>
      <p className="mt-2 text-center italic">Arrêté le présent reçu à la somme de {receipt.amount_in_words} francs CFA</p>
      {receipt.notes && <p className="mt-2 italic">{receipt.notes}</p>}
      <footer className="mt-5 flex justify-between text-xs text-slate-500">
        <span>Encaissé par : {receipt.received_by ?? '-'}</span>
        <span>Signature et cachet</span>
      </footer>
    </div>
  )
}

/**
 * Reçu affiché dans l'application. paymentIds (optionnel) liste les reçus d'un même encaissement.
 */
export default function ReceiptModal({ paymentId, paymentIds, open, onClose }) {
  const ids = paymentIds?.length ? paymentIds : [paymentId]
  const [selected, setSelected] = useState(paymentId)
  const [pdfError, setPdfError] = useState(null)
  const [downloading, setDownloading] = useState(false)

  useEffect(() => { setSelected(paymentId) }, [paymentId, open])
  useEffect(() => { setPdfError(null) }, [selected, open])

  // staleTime 0 : un reçu annulé entre-temps doit porter la mention ANNULÉ
  const query = useQuery({
    queryKey: ['receipt', selected],
    queryFn: () => payments.receipt(selected),
    enabled: open && Boolean(selected),
    staleTime: 0,
  })
  const receipt = query.data

  const downloadPdf = async () => {
    setPdfError(null)
    setDownloading(true)
    try {
      saveBlob(await payments.receiptPdf(selected), `recu-${receipt.receipt_number}.pdf`)
    } catch (e) {
      const error = apiError(e)
      setPdfError(error.code === 'pdf_unavailable' ? 'PDF indisponible, utilisez Imprimer' : error.message)
    } finally {
      setDownloading(false)
    }
  }

  return (
    <Modal open={open} title="Reçu" onClose={onClose} wide>
      {ids.length > 1 && (
        <div className="mb-3 flex flex-wrap justify-center gap-2">
          {ids.map((id, index) => (
            <button key={id} type="button" className={id === selected ? 'btn-primary' : 'btn-secondary'} onClick={() => setSelected(id)}>
              Reçu {index + 1} / {ids.length}
            </button>
          ))}
        </div>
      )}
      {query.isLoading && <Spinner />}
      {query.isError && <ErrorBox message={apiError(query.error).message} />}
      {receipt && <Receipt receipt={receipt} />}
      {pdfError && <div className="mt-3"><ErrorBox message={pdfError} /></div>}
      <div className="mt-4 flex flex-wrap justify-end gap-2">
        <button type="button" className="btn-ghost" onClick={onClose}>Fermer</button>
        <button type="button" className="btn-secondary" disabled={!receipt || downloading} onClick={downloadPdf}><Download size={16} />Télécharger PDF</button>
        <button type="button" className="btn-primary" disabled={!receipt} onClick={() => window.print()}><Printer size={16} />Imprimer</button>
      </div>
    </Modal>
  )
}
